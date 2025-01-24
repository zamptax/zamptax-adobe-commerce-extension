<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\Extend;

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Tax\Api\Data\QuoteDetailsInterface;

class QuoteDetails
{
    /**
     * Extend Interface Data
     *
     * @param QuoteDetailsInterface $quoteDetails
     * @param QuoteAddress $address
     * @return QuoteDetailsInterface
     */
    public function execute(QuoteDetailsInterface $quoteDetails, QuoteAddress $address): QuoteDetailsInterface
    {
        $quote = $address->getQuote();
        if ($extension = $quoteDetails->getExtensionAttributes()) {
            $extension->setZampQuoteId($quote->getId());
            $extension->setZampQuoteShippingAmount($quote->getShippingAddress()->getShippingAmount());

            $quoteDate = $quote->getUpdatedAt() ?: $quote->getCreatedAt();
            $extension->setZampQuoteUpdatedAt($quoteDate);

            $quoteDetails->setExtensionAttributes($extension);
        }
        return $quoteDetails;
    }
}
