<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Preference\Bundle\Pricing\Price;

use ATF\Zamp\Model\QuoteExtender;
use Magento\Bundle\Pricing\Price\TaxPrice;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config;

/**
 * @see TaxPrice;
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class TaxPriceBundleZamp extends TaxPrice
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TaxClassKeyInterfaceFactory
     */
    private $taxClassKeyFactory;

    /**
     * @var Config
     */
    private $taxConfig;

    /**
     * @var QuoteDetailsInterfaceFactory
     */
    private $quoteDetailsFactory;

    /**
     * @var QuoteDetailsItemInterfaceFactory
     */
    private $quoteDetailsItemFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var TaxCalculationInterface
     */
    private $taxCalculationService;

    /**
     * @var GroupRepositoryInterface
     */
    private $customerGroupRepository;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var QuoteExtender
     */
    private $quoteExtender;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * */
    public function __construct(
        StoreManagerInterface            $storeManager,
        TaxClassKeyInterfaceFactory      $taxClassKeyFactory,
        Config                           $taxConfig,
        QuoteDetailsInterfaceFactory     $quoteDetailsFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        TaxCalculationInterface          $taxCalculationService,
        CustomerSession                  $customerSession,
        GroupRepositoryInterface         $customerGroupRepository,
        Session                          $checkoutSession,
        QuoteExtender                    $quoteExtender
    ) {
        parent::__construct(
            $storeManager,
            $taxClassKeyFactory,
            $taxConfig,
            $quoteDetailsFactory,
            $quoteDetailsItemFactory,
            $taxCalculationService,
            $customerSession,
            $customerGroupRepository,
            $checkoutSession
        );

        $this->storeManager = $storeManager;
        $this->taxClassKeyFactory = $taxClassKeyFactory;
        $this->taxConfig = $taxConfig;
        $this->quoteDetailsFactory = $quoteDetailsFactory;
        $this->quoteDetailsItemFactory = $quoteDetailsItemFactory;
        $this->taxCalculationService = $taxCalculationService;
        $this->customerSession = $customerSession;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteExtender = $quoteExtender;
    }

    /**
     * Get product price with all tax settings processing for cart
     *
     * @param Product $product
     * @param float $price
     * @param bool|null $includingTax
     * @param int|null $ctc
     * @param StoreInterface|null $store
     * @param bool|null $priceIncludesTax
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getTaxPrice(
        Product $product,
        float   $price,
        bool    $includingTax = null,
        int     $ctc = null,
        $store = null,
        bool    $priceIncludesTax = null
    ): float {
        if (!$price) {
            return $price;
        }

        $store = $this->storeManager->getStore($store);
        $storeId = $store ? $store->getId() : null;
        $taxClassKey = $this->taxClassKeyFactory->create();
        $customerTaxClassKey = $this->taxClassKeyFactory->create();
        $item = $this->quoteDetailsItemFactory->create();
        $quoteDetails = $this->quoteDetailsFactory->create();
        $customerQuote = $this->checkoutSession->getQuote();

        if ($priceIncludesTax === null) {
            $priceIncludesTax = $this->taxConfig->priceIncludesTax($store);
        }

        $taxClassKey->setType(TaxClassKeyInterface::TYPE_ID)
            ->setValue($product->getTaxClassId());

        if ($ctc === null && $this->customerSession->getCustomerGroupId() !== null) {
            $ctc = $this->customerGroupRepository->getById($this->customerSession->getCustomerGroupId())
                ->getTaxClassId();
        }

        $customerTaxClassKey->setType(TaxClassKeyInterface::TYPE_ID)
            ->setValue($ctc);

        $itemExtension = $item->getExtensionAttributes();
        if ($itemExtension) {
            $itemExtension->setProductId($product->getId());
        }

        $item->setQuantity(1)
            ->setCode($product->getSku())
            ->setShortDescription($product->getShortDescription())
            ->setTaxClassKey($taxClassKey)
            ->setIsTaxIncluded($priceIncludesTax)
            ->setType('product')
            ->setUnitPrice($price);

        $item = $this->quoteExtender->getQuoteDetailsItem()->execute($item, $product);

        $quoteDetails
            ->setShippingAddress($customerQuote->getShippingAddress()->getDataModel())
            ->setCustomerTaxClassKey($customerTaxClassKey)
            ->setItems([$item])
            ->setCustomerId($this->customerSession->getCustomerId());

        $quoteDetails = $this->quoteExtender->getQuoteDetails()
            ->execute($quoteDetails, $customerQuote->getShippingAddress());

        $taxDetails = $this->taxCalculationService->calculateTax($quoteDetails, $storeId);
        $items = $taxDetails->getItems();
        $taxDetailsItem = array_shift($items);

        if ($includingTax !== null) {
            if ($includingTax) {
                $price = $taxDetailsItem->getPriceInclTax();
            } else {
                $price = $taxDetailsItem->getPrice();
            }
        } else {
            $price = $this->taxConfig->displayCartPricesExclTax($store) ||
            $this->taxConfig->displayCartPricesBoth($store) ?
                $taxDetailsItem->getPrice() : $taxDetailsItem->getPriceInclTax();
        }

        return $price;
    }

    /**
     * Check if both cart prices are shown
     *
     * @param StoreInterface|null $store
     * @return bool
     */
    public function displayCartPricesBoth(StoreInterface $store = null): bool
    {
        return $this->taxConfig->displayCartPricesBoth($store);
    }
}
