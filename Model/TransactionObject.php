<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use ATF\Zamp\Model\Transaction\LineItem;
use ATF\Zamp\Model\Transaction\LineItemFactory;
use ATF\Zamp\Model\Transaction\ShipToAddress;
use ATF\Zamp\Model\Transaction\ShipToAddressFactory;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Creditmemo\Item as RefundItem;

class TransactionObject extends DataObject
{
    public const ID = 'id';
    public const NAME = 'name';
    public const PARENT_ID = 'parentId';
    public const TRANSACTED_AT = 'transactedAt';
    public const ENTITY = 'entity';
    public const PURPOSE = 'purpose';
    public const IS_RESALE = 'isResale';
    public const DISCOUNT = 'discount';
    public const SUB_TOTAL = 'subtotal';
    public const SHIPPING_HANDLING = 'shippingHandling';
    public const TAX_COLLECTED = 'taxCollected';
    public const TOTAL = 'total';
    public const SHIP_TO_ADDRESS = 'shipToAddress';
    public const LINE_ITEMS = 'lineItems';

    /**
     * @var ShipToAddressFactory
     */
    protected ShipToAddressFactory $shippingToAddressFactory;

    /**
     * @var LineItemFactory
     */
    protected LineItemFactory $lineItemFactory;

    /**
     * @var QuoteRepository
     */
    protected QuoteRepository $quoteRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var Configurations
     */
    protected $config;

    /**
     * @param ShipToAddressFactory $shippingToAddressFactory
     * @param LineItemFactory $lineItemFactory
     * @param QuoteRepository $quoteRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Configurations $config
     * @param array $data
     */
    public function __construct(
        ShipToAddressFactory $shippingToAddressFactory,
        LineItemFactory      $lineItemFactory,
        QuoteRepository      $quoteRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        Configurations       $config,
        array                $data = []
    ) {
        parent::__construct($data);

        $this->shippingToAddressFactory = $shippingToAddressFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->lineItemFactory = $lineItemFactory;
        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Get Transacted At
     *
     * @return string
     */
    public function getTransactedAt(): string
    {
        return $this->getData(self::TRANSACTED_AT);
    }

    /**
     * Get Entity
     *
     * @return ?string
     */
    public function getEntity(): ?string
    {
        return $this->getData(self::ENTITY);
    }

    /**
     * Get Purpose
     *
     * @return ?string
     */
    public function getPurpose(): ?string
    {
        return $this->getData(self::PURPOSE);
    }

    /**
     * Is Resale
     *
     * @return bool
     */
    public function isResale(): bool
    {
        return $this->getData(self::IS_RESALE);
    }

    /**
     * Get Sub Total
     *
     * @return float
     */
    public function getSubTotal(): float
    {
        return $this->getData(self::SUB_TOTAL);
    }

    /**
     * Get Shipping Handling
     *
     * @return float
     */
    public function getShippingHandling(): float
    {
        return $this->getData(self::SHIPPING_HANDLING);
    }

    /**
     * Get Tax Collected
     *
     * @return float
     */
    public function getTaxCollected(): float
    {
        return $this->getData(self::TAX_COLLECTED);
    }

    /**
     * Get Total
     *
     * @return float
     */
    public function getTotal(): float
    {
        return $this->getData(self::TOTAL);
    }

    /**
     * Get Ship To Address
     *
     * @return ShipToAddress
     */
    public function getShipToAddress(): ShipToAddress
    {
        return $this->getData(self::SHIP_TO_ADDRESS);
    }

    /**
     * Get Line Item
     *
     * @return LineItem
     */
    public function getLineItem(): LineItem
    {
        return $this->getData(self::LINE_ITEMS);
    }

    /**
     * Create Payload
     *
     * @param DataObject $request
     * @param string $transactionType
     * @param bool $isLocal
     * @return $this
     * @throws LocalizedException
     */
    public function createPayload(
        DataObject $request,
        string $transactionType = 'cart',
        bool $isLocal = false
    ): TransactionObject {
        $shippingToAddress = $this->buildShippingToAddress($request);
        [$lineItems, $subTotal, $discountTotal] = $this->buildLineItems($request);

        $total = 0;
        if ($transactionType === 'invoice') {
            $quote = $request->getData('zamp_invoice');
            $id = 'INV-' . $quote->getId();
            $name = 'INV-' . $quote->getIncrementId();

            $subTotal = $quote->getSubTotal();
            $discount = $quote->getDiscountAmount();
            $taxCollected = $quote->getTaxAmount();

            $this->setTaxCollected($taxCollected);
            $total += $taxCollected;
        } elseif ($transactionType === 'refund') {
            $quote = $request->getData('zamp_refund');
            $id = 'CM-' . $quote->getId();
            $name = 'CM-' . $quote->getIncrementId();
            $parentId = 'INV-' . $quote->getParentId();

            if ($isLocal) {
                $parentId = 'LOCAL-' . $parentId;
            }
            $this->setParentId($parentId);

            $discount = abs($quote->getDiscountAmount());
            $taxCollected = $quote->getTaxAmount();
            $subTotal = $quote->getSubTotal() - $discount;
            $shippingHandling = (float)$quote->getShippingAmount();

            $this->setTaxCollected(-1 * abs($quote->getTaxAmount()));
        } else {
            $quote = $request->getData('zamp_quote');

            $id = 'CART-' . $quote->getId();
            $name = null;

            $discount = $discountTotal;
        }

        if ($transactionType === 'refund') {
            /**
             * Negates values for refund transaction
             */
            $total += -1 * abs($subTotal + $shippingHandling + $taxCollected);
            $subTotal = -1 * abs($quote->getSubTotal() - $discount);
            $shippingHandling = -1 * abs((float)$quote->getShippingAmount());
        } else {
            $subTotal -= $discount;
            $shippingHandling = (float)$quote->getShippingAmount();
            $total += $subTotal + $shippingHandling;
        }

        if ($isLocal) {
            $id = 'LOCAL-' . $id;
            $name = 'LOCAL-' . $name;
        }

        $entityType = $quote->getCustomerTaxExemptCode();
        $purpose = ($entityType === 'WHOLESALER' || $entityType === 'RESALE') ? 'RESALE' : null;

        $this->setId($id)
            ->setName($name)
            ->setTransactedAt($quote->getUpdatedAt())
            ->setEntity($entityType)
            ->setResale($purpose === 'RESALE')
            ->setSubTotal($subTotal)
            ->setShippingHandling($shippingHandling)
            ->setTotal($total)
            ->setShipToAddress($shippingToAddress->toArray())
            ->setPurpose($purpose)
            ->setLineItems($lineItems);

        return $this;
    }

    /**
     * Build Shipping To Address
     *
     * @param DataObject $request
     * @return ShipToAddress
     * @throws LocalizedException
     */
    public function buildShippingToAddress(DataObject $request): ShipToAddress
    {
        $shippingToAddress = $this->createShippingToAddress();

        /** @var CustomerAddress $address */
        $address = $request->getZampShippingAddress();
        $street = $address->getStreet();
        $line1 = !empty($street[0]) ? $street[0] : ".";
        $shippingToAddress->setLine1($line1);

        $line2 = !empty($street[1]) ? $street[1] : null;
        $shippingToAddress->setLine2($line2);

        $city = $address->getCity() ?: null;
        $shippingToAddress->setCity($city);

        if ($address instanceof Address) {
            $stateId = $address->getRegionId();
        } else {
            $stateId = $address->getRegion() ? $address->getRegion()->getRegionId() : null;
        }

        if ($stateId) {
            $stateCode = $shippingToAddress->getRegionCodeById($stateId);
            $shippingToAddress->setState($stateCode);
        }

        $postcode = $address->getPostcode() ?: null;
        $shippingToAddress->setZip($postcode);

        $shippingToAddress->setCountry($address->getCountryId());

        return $shippingToAddress;
    }

    /**
     * Create Shipping To Address
     *
     * @return ShipToAddress
     */
    public function createShippingToAddress(): ShipToAddress
    {
        return $this->shippingToAddressFactory->create();
    }

    /**
     * Build Line Items
     *
     * @param DataObject $request
     * @return array
     */
    public function buildLineItems(DataObject $request): array
    {
        $lineItems = [];
        $subTotal = 0;
        $discountTotal = 0;

        /**
         * Default to zero, as Magento processes the shipping amount in the order level
         */
        $shippingHandling = 0;

        if ($items = $request->getZampItems()) {
            foreach ($items as $item) {
                $lineItem = $this->createLineItem();

                if ($item instanceof InvoiceItem || $item instanceof RefundItem) {
                    $lineId = $item->getData('order_item_id') . '-' . $item->getData('product_id');
                    $lineItem->setId($lineId);
                    $lineItem->setProductSku($item->getSku());
                    $lineItem->setProductName($item->getName());

                    $productTaxCode = $item->getTaxProviderTaxCode() ??
                        $this->config->getDefaultProductTaxProviderTaxCode();
                    $lineItem->setProductTaxCode($productTaxCode);

                    $lineItem->setAmount((float)$item->getPrice())
                        ->setDiscount((float)$item->getDiscountAmount())
                        ->setQuantity((int)$item->getQty());

                } else {
                    $extension = $item->getExtensionAttributes();

                    if (!$extension) {
                        continue;
                    }

                    $lineItem->setId($extension->getProductId());
                    $lineItem->setProductSku($extension->getProductSku());
                    $lineItem->setProductName($extension->getProductName());
                    $lineItem->setProductTaxCode($extension->getProductTaxCode());

                    $lineItem->setAmount(round($extension->getZampPrice(), 2))
                        ->setDiscount((float)$item->getDiscountAmount())
                        ->setQuantity((int)$item->getQuantity());
                }

                $lineItem->setShippingHandling($shippingHandling);
                $subTotal += ($lineItem->getAmount() * $lineItem->getQuantity());
                $discountTotal += $lineItem->getDiscount();

                /**
                 * Negates value after computation (used for refund transaction)
                 */
                if ($item instanceof RefundItem) {
                    $lineItem->setDiscount(-1 * ($lineItem->getDiscount()));
                    $lineItem->setQuantity(-1 * abs($lineItem->getQuantity()));
                }

                $lineItems[] = $lineItem->toArray();
            }
        }

        return [$lineItems, $subTotal, $discountTotal];
    }

    /**
     * Create Line Item
     *
     * @return LineItem
     */
    public function createLineItem(): LineItem
    {
        return $this->lineItemFactory->create();
    }

    /**
     * Set Id
     *
     * @param string $id
     * @return TransactionObject
     */
    public function setId(string $id): TransactionObject
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Set Shipping Handling
     *
     * @param float $shippingHandling
     * @return TransactionObject
     */
    public function setShippingHandling(float $shippingHandling): TransactionObject
    {
        return $this->setData(self::SHIPPING_HANDLING, $shippingHandling);
    }

    /**
     * Set Tax Collected
     *
     * @param float $taxCollected
     * @return TransactionObject
     */
    public function setTaxCollected(float $taxCollected): TransactionObject
    {
        return $this->setData(self::TAX_COLLECTED, $taxCollected);
    }

    /**
     * Set Discount
     *
     * @param float $discount
     * @return TransactionObject
     */
    public function setDiscount(float $discount): TransactionObject
    {
        return $this->setData(self::DISCOUNT, $discount);
    }

    /**
     * Get Discount
     *
     * @return float
     */
    public function getDiscount(): float
    {
        return $this->getData(self::DISCOUNT);
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->getData(self::ID);
    }

    /**
     * Set Line Items
     *
     * @param array $lineItems
     * @return TransactionObject
     */
    public function setLineItems(array $lineItems): TransactionObject
    {
        return $this->setData(self::LINE_ITEMS, $lineItems);
    }

    /**
     * Set Ship To Address
     *
     * @param array $shipToAddress
     * @return TransactionObject
     */
    public function setShipToAddress(array $shipToAddress): TransactionObject
    {
        return $this->setData(self::SHIP_TO_ADDRESS, $shipToAddress);
    }

    /**
     * Set Total
     *
     * @param float $total
     * @return TransactionObject
     */
    public function setTotal(float $total): TransactionObject
    {
        return $this->setData(self::TOTAL, $total);
    }

    /**
     * Set Sub Total
     *
     * @param float $subTotal
     * @return TransactionObject
     */
    public function setSubTotal(float $subTotal): TransactionObject
    {
        return $this->setData(self::SUB_TOTAL, $subTotal);
    }

    /**
     * Set Resale
     *
     * @param bool $isResale
     * @return TransactionObject
     */
    public function setResale(bool $isResale): TransactionObject
    {
        return $this->setData(self::IS_RESALE, $isResale);
    }

    /**
     * Set Purpose
     *
     * @param string|null $purpose
     * @return TransactionObject
     */
    public function setPurpose(?string $purpose): TransactionObject
    {
        return $this->setData(self::PURPOSE, $purpose);
    }

    /**
     * Set Entity
     *
     * @param string|null $entity
     * @return TransactionObject
     */
    public function setEntity(?string $entity): TransactionObject
    {
        return $this->setData(self::ENTITY, $entity);
    }

    /**
     * Set Transacted At
     *
     * @param string $transactedAt
     * @return TransactionObject
     */
    public function setTransactedAt(string $transactedAt): TransactionObject
    {
        return $this->setData(self::TRANSACTED_AT, $transactedAt);
    }

    /**
     * Set Name
     *
     * @param string|null $name
     * @return TransactionObject
     */
    public function setName(?string $name): TransactionObject
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Set parent ID
     *
     * @param string $parentId
     * @return TransactionObject
     */
    public function setParentId(string $parentId): TransactionObject
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * Get Subtotal with Discount from Invoice or Quote
     *
     * @param int|string $entityId
     * @param string $type 'invoice' or 'quote'
     * @return float
     */
    public function getSubtotalWithDiscount($entityId, $type = 'invoice')
    {
        $discount = 0.0;
        try {
            if ($type === 'invoice') {
                // Load the invoice by ID
                $invoice = $this->invoiceRepository->get($entityId);
                $discount = $invoice->getDiscountAmount();

            } else {
                // Load the quote by ID
                $quote = $this->quoteRepository->get($entityId);

                // Get the subtotal and discount from the quote shipping or billing address
                $shippingAddress = $quote->getShippingAddress();
                $discount = $shippingAddress->getDiscountAmount();
            }
            return $discount;

        } catch (\Exception $e) {
            return $discount;
        }
    }
}
