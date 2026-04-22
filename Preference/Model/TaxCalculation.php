<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Preference\Model;

use ATF\Zamp\Model\Calculate;
use ATF\Zamp\Model\Configurations;
use ATF\Zamp\Model\Service\TaxExemptCodeResolver;
use ATF\Zamp\Preference\Model\Calculation\CalculatorFactory as ZampCalculatorFactory;
use ATF\Zamp\Services\Quote as QuoteService;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\AbstractCalculator;
use Magento\Tax\Model\Calculation\CalculatorFactory;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\TaxDetails\TaxDetails;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TaxCalculation extends \Magento\Tax\Model\TaxCalculation
{
    private const QUOTE_CURRENCY_CODE = 'zamp_quote_currency_code';

    private const SESSION_ZAMP_PAYLOAD = 'zamp_payload';

    /**
     * Item code to Item object array.
     *
     * @var QuoteDetailsItemInterface[]
     */
    private $keyedItems;

    /**
     * Parent item code to children item array.
     *
     * @var QuoteDetailsItemInterface[][]
     */
    private $parentToChildren;

    /**
     * @var Configurations
     */
    private $zampConfigurations;

    /**
     * @var Calculate
     */
    private Calculate $zampCalculate;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @var QuoteService
     */
    private QuoteService $quoteService;

    /**
     * @var TaxExemptCodeResolver
     */
    private TaxExemptCodeResolver $taxExemptCodeResolver;

    /**
     * @var bool
     */
    private bool $zampCalculation;

    /**
     * @param Calculation $calculation
     * @param CalculatorFactory $calculatorFactory
     * @param Config $config
     * @param TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory
     * @param TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory
     * @param StoreManagerInterface $storeManager
     * @param TaxClassManagementInterface $taxClassManagement
     * @param DataObjectHelper $dataObjectHelper
     * @param Configurations $zampConfigurations
     * @param Calculate $zampCalculate
     * @param Json $jsonSerializer
     * @param QuoteService $quoteService
     * @param TaxExemptCodeResolver $taxExemptCodeResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Calculation                    $calculation,
        CalculatorFactory              $calculatorFactory,
        Config                         $config,
        TaxDetailsInterfaceFactory     $taxDetailsDataObjectFactory,
        TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory,
        StoreManagerInterface          $storeManager,
        TaxClassManagementInterface    $taxClassManagement,
        DataObjectHelper               $dataObjectHelper,
        Configurations                 $zampConfigurations,
        Calculate                      $zampCalculate,
        Json                           $jsonSerializer,
        QuoteService                   $quoteService,
        TaxExemptCodeResolver          $taxExemptCodeResolver,
    ) {
        parent::__construct(
            $calculation,
            $calculatorFactory,
            $config,
            $taxDetailsDataObjectFactory,
            $taxDetailsItemDataObjectFactory,
            $storeManager,
            $taxClassManagement,
            $dataObjectHelper
        );

        $this->zampConfigurations = $zampConfigurations;
        $this->zampCalculate = $zampCalculate;
        $this->jsonSerializer = $jsonSerializer;
        $this->quoteService = $quoteService;
        $this->taxExemptCodeResolver = $taxExemptCodeResolver;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function calculateTax(
        QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true
    ) {
        $shippingAddress = $quoteDetails->getShippingAddress();
        $doZampCalc = $this->zampConfigurations->isModuleEnabled()
            && $this->zampConfigurations->isCalculationEnabled()
            && is_object($shippingAddress)
            && (string)$shippingAddress->getCountryId() !== '';

        if (!$doZampCalc) {
            return parent::calculateTax($quoteDetails, $storeId, $round);
        }

        $this->setZampCalculation(true);

        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }

        // initial TaxDetails data
        $taxDetailsData = [
            TaxDetails::KEY_SUBTOTAL => 0.0,
            TaxDetails::KEY_TAX_AMOUNT => 0.0,
            TaxDetails::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT => 0.0,
            TaxDetails::KEY_APPLIED_TAXES => [],
            TaxDetails::KEY_ITEMS => [],
        ];
        $items = $quoteDetails->getItems();
        if (empty($items)) {
            return $this->taxDetailsDataObjectFactory->create()
                ->setSubtotal(0.0)
                ->setTaxAmount(0.0)
                ->setDiscountTaxCompensationAmount(0.0)
                ->setAppliedTaxes([])
                ->setItems([]);
        }
        $this->computeRelationships($items);

        $calculator = $this->calculatorFactory->create(
            ZampCalculatorFactory::CALC_ZAMP,
            $storeId,
            $quoteDetails->getBillingAddress(),
            $shippingAddress,
            $this->taxClassManagement->getTaxClassId($quoteDetails->getCustomerTaxClassKey(), 'customer'),
            $quoteDetails->getCustomerId()
        );

        $isShipping = $this->checkIfShippingItems();
        $extractedItems = $isShipping
            ? $this->extractZampItemsFromShipping($quoteDetails)
            : $this->extractZampItems();

        $request = $this->buildDataSource($extractedItems, $quoteDetails);
        $zampResponse = $this->zampCalculate->execute($request);

        if ($zampResponse && isset($zampResponse['taxDue'])) {
            $parsedZampResponse = $this->applyTaxToLineItems($zampResponse);
            $this->applyTaxInfoOnKeyedItems($parsedZampResponse);
        }

        $processedItems = [];
        foreach ($this->keyedItems as $item) {
            if (isset($this->parentToChildren[$item->getCode()])) {
                $processedChildren = [];
                foreach ($this->parentToChildren[$item->getCode()] as $child) {
                    $processedItem = $this->processItem($child, $calculator, $round);
                    $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
                    $processedItems[$processedItem->getCode()] = $processedItem;
                    $processedChildren[] = $processedItem;
                }
                $processedItem = $this->calculateParent($processedChildren, $item->getQuantity());
                $processedItem->setCode($item->getCode());
                $processedItem->setType($item->getType());
            } else {
                $processedItem = $this->processItem($item, $calculator, $round);
                $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
            }
            $processedItems[$processedItem->getCode()] = $processedItem;
        }

        $taxDetailsDataObject = $this->taxDetailsDataObjectFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $taxDetailsDataObject,
            $taxDetailsData,
            TaxDetailsInterface::class
        );
        $taxDetailsDataObject->setItems($processedItems);
        return $taxDetailsDataObject;
    }

    /**
     * Computes relationships between items, primarily the child to parent relationship.
     *
     * @param QuoteDetailsItemInterface[] $items
     * @return void
     */
    private function computeRelationships($items)
    {
        $this->keyedItems = [];
        $this->parentToChildren = [];
        foreach ($items as $item) {
            if ($item->getParentCode() === null) {
                $this->keyedItems[$item->getCode()] = $item;
            } else {
                $this->parentToChildren[$item->getParentCode()][] = $item;
            }
        }
    }

    /**
     * Is Zamp Calculation
     *
     * @return bool
     */
    public function isZampCalculation(): bool
    {
        return $this->zampCalculation;
    }

    /**
     * Set Zamp Calculation
     *
     * @param bool $zampCalculation
     * @return void
     */
    public function setZampCalculation(bool $zampCalculation): void
    {
        $this->zampCalculation = $zampCalculation;
    }

    /**
     * Check if shipping calc
     *
     * @return bool
     */
    private function checkIfShippingItems(): bool
    {
        $shippingItem = array_filter($this->keyedItems, static function ($item) {
            return $item->getType() === 'shipping';
        });
        return count($shippingItem) > 0;
    }

    /**
     * Build Data Source
     *
     * @param array $items
     * @param QuoteDetailsInterface $quoteDetails
     * @return DataObject
     */
    private function buildDataSource(array $items, QuoteDetailsInterface $quoteDetails): DataObject
    {
        $build = [];
        $quoteDetailsExtension = $quoteDetails->getExtensionAttributes();

        $customerId = $quoteDetails->getCustomerId();
        $resolvedCustomerId = $customerId && (int)$customerId > 0 ? (int)$customerId : null;
        $customerTaxExemptCode = $this->taxExemptCodeResolver->execute($resolvedCustomerId);

        if ($quoteDetailsExtension) {
            $this->quoteService->updatedCartQuote($quoteDetailsExtension->getZampQuoteId());

            $build = [
                'zamp_quote' => new DataObject([
                    'id' => $quoteDetailsExtension->getZampQuoteId(),
                    'shipping_amount' => $quoteDetailsExtension->getZampQuoteShippingAmount(),
                    'customer_tax_exempt_code' => $customerTaxExemptCode,
                    'currency_code' => $this->resolveCurrencyCode($quoteDetails),
                    'updated_at' => $quoteDetailsExtension->getZampQuoteUpdatedAt(),
                ]),
                'zamp_items' => $items,
                'zamp_shipping_address' => $quoteDetails->getShippingAddress(),
            ];
        }
        return new DataObject($build);
    }

    /**
     * Resolves the current quote/order currency without relying on generated extension methods.
     */
    private function resolveCurrencyCode(QuoteDetailsInterface $quoteDetails): ?string
    {
        if (method_exists($quoteDetails, 'getData')) {
            $currencyCode = $quoteDetails->getData(self::QUOTE_CURRENCY_CODE);
            if (is_string($currencyCode) && $currencyCode !== '') {
                return $currencyCode;
            }
        }

        $shippingAddress = $quoteDetails->getShippingAddress();
        if ($shippingAddress && method_exists($shippingAddress, 'getQuote')) {
            $quote = $shippingAddress->getQuote();
            if ($quote && method_exists($quote, 'getQuoteCurrencyCode')) {
                $currencyCode = $quote->getQuoteCurrencyCode();
                if (is_string($currencyCode) && $currencyCode !== '') {
                    return $currencyCode;
                }
            }
        }

        return null;
    }

    /**
     * Apply taxes to lineItems for easy data pull
     *
     * @param array $zampResponse
     * @return array
     */
    private function applyTaxToLineItems(array $zampResponse): array
    {
        foreach ($zampResponse['lineItems'] as $key => $lineItem) {
            $zampResponse['lineItems'][$key]['taxes'] = array_filter(
                $zampResponse['taxes'],
                static function ($tax) use ($lineItem) {
                    return $tax['lineItemId'] === $lineItem['id'];
                }
            );
        }
        return $zampResponse;
    }

    /**
     * Set tax info on every keyed item
     *
     * @param array $zampResponse
     * @param bool $remove
     * @return void
     */
    private function applyTaxInfoOnKeyedItems(array $zampResponse, bool $remove = false): void
    {
        foreach ($this->keyedItems as $item) {
            if (isset($this->parentToChildren[$item->getCode()])) {
                foreach ($this->parentToChildren[$item->getCode()] as $child) {
                    $this->updateItemTax($child, $zampResponse, $remove);
                }
            } else {
                $this->updateItemTax($item, $zampResponse, $remove);
            }
        }
    }

    /**
     * Update Item Tax
     *
     * @param QuoteDetailsItemInterface $item
     * @param array $zampResponse
     * @param bool $remove
     * @return void
     */
    private function updateItemTax(QuoteDetailsItemInterface $item, array $zampResponse, bool $remove = false): void
    {
        if (!($extension = $item->getExtensionAttributes())) {
            return;
        }

        if ($remove) {
            $extension->setZampPrice(0)
                ->setProductId(null)
                ->setProductSku(null)
                ->setProductName(null)
                ->setProductTaxCode(null)
                ->setZampTaxInfo(null);
        } else {

            if ($item->getType() === 'shipping') {
                $taxInfo = array_filter($zampResponse['taxes'], static function ($lineItem) {
                    return $lineItem['ancillaryType'] === 'SHIPPING_HANDLING';
                });

                $rateId = $this->zampCalculate->resolveRateId($zampResponse, 'SHIPPING_HANDLING');
                $zampTaxInfo = [[
                    'taxes' => $taxInfo,
                    'rateId' => $rateId,
                    'rateTitle' => $rateId
                ]];
            } else {
                $productId = $extension->getProductId();
                $zampTaxInfo = array_filter($zampResponse['lineItems'], static function ($lineItem) use ($productId) {
                    return $productId === $lineItem['id'];
                });
            }

            if (count($zampTaxInfo) > 0) {
                $extension->setZampTaxInfo($this->jsonSerializer->serialize(reset($zampTaxInfo)));
            }
        }

        $item->setExtensionAttributes($extension);
    }

    /**
     * @inheritDoc
     */
    protected function processItem(
        QuoteDetailsItemInterface $item,
        AbstractCalculator        $calculator,
        $round = true
    ) {
        $quantity = $this->getTotalQuantity($item);
        return $calculator->calculate($item, $quantity, $round);
    }

    /**
     * @inheritDoc
     */
    protected function getTotalQuantity(QuoteDetailsItemInterface $item)
    {
        $parentCode = (string)$item->getParentCode();
        if ($parentCode !== '') {
            $parentItem = is_array($this->keyedItems) ? ($this->keyedItems[$parentCode] ?? null) : null;
            if ($parentItem) {
                return $parentItem->getQuantity() * $item->getQuantity();
            }
        }

        return $item->getQuantity();
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->keyedItems = null;
        $this->parentToChildren = null;
    }

    /**
     * Extract Zamp Items, Supports Bundle
     *
     * @return array
     */
    private function extractZampItems(): array
    {
        $items = [];
        foreach ($this->keyedItems as $item) {
            if (isset($this->parentToChildren[$item->getCode()])) {
                foreach ($this->parentToChildren[$item->getCode()] as $child) {
                    $items[] = $child;
                }
            } else {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Extract Zamp Items
     *
     * @param QuoteDetailsInterface $quoteDetails
     * @return array
     */
    private function extractZampItemsFromShipping(QuoteDetailsInterface $quoteDetails): array
    {
        $items = [];
        foreach ($quoteDetails->getItems() as $quoteDetailsItem) {
            if ($extensionAttributes = $quoteDetailsItem->getExtensionAttributes()) {
                $items = $extensionAttributes->getZampItems();
                if (!empty($items)) {
                    return $items;
                }
            }
        }

        return $items;
    }
}
