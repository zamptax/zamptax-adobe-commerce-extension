<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Transaction;

use Magento\Framework\DataObject;

class LineItem extends DataObject
{
    public const ID = 'id';
    public const AMOUNT = 'amount';
    public const QUANTITY = 'quantity';
    public const DISCOUNT = 'discount';
    public const SHIPPING_HANDLING = 'shippingHandling';
    public const PRODUCT_NAME = 'productName';
    public const PRODUCT_SKU = 'productSku';
    public const PRODUCT_TAX_CODE = 'productTaxCode';

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
     * Set Id
     *
     * @param string $id
     * @return LineItem
     */
    public function setId(string $id): LineItem
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get Amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Set Amount
     *
     * @param float $amount
     * @return LineItem
     */
    public function setAmount(float $amount): LineItem
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Get Quantity
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->getData(self::QUANTITY);
    }

    /**
     * Set Quantity
     *
     * @param int $quantity
     * @return LineItem
     */
    public function setQuantity(int $quantity): LineItem
    {
        return $this->setData(self::QUANTITY, $quantity);
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
     * Set Discount
     *
     * @param float $discount
     * @return LineItem
     */
    public function setDiscount(float $discount): LineItem
    {
        return $this->setData(self::DISCOUNT, $discount);
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
     * Set Shipping Handling
     *
     * @param float $shippingHandling
     * @return LineItem
     */
    public function setShippingHandling(float $shippingHandling): LineItem
    {
        return $this->setData(self::SHIPPING_HANDLING, $shippingHandling);
    }

    /**
     * Get Product Name
     *
     * @return string
     */
    public function getProductName(): string
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * Set Product Name
     *
     * @param string $productName
     * @return LineItem
     */
    public function setProductName(string $productName): LineItem
    {
        return $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * Get Product Sku
     *
     * @return string
     */
    public function getProductSku(): string
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * Set Product Sku
     *
     * @param string $productSku
     * @return LineItem
     */
    public function setProductSku(string $productSku): LineItem
    {
        return $this->setData(self::PRODUCT_SKU, $productSku);
    }

    /**
     * Get Product Tax Code
     *
     * @return string
     */
    public function getProductTaxCode(): string
    {
        return $this->getData(self::PRODUCT_TAX_CODE);
    }

    /**
     * Set Product Tax Code
     *
     * @param string $productTaxCode
     * @return LineItem
     */
    public function setProductTaxCode(string $productTaxCode): LineItem
    {
        return $this->setData(self::PRODUCT_TAX_CODE, $productTaxCode);
    }
}
