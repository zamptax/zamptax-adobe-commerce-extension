<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Tax\Model\Sales\Total\Quote;

use ATF\Zamp\Model\Extend\QuoteDetails;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

class CommonTaxCollectorQuote
{
    /**
     * @var QuoteDetails
     */
    private QuoteDetails $quoteDetails;

    /**
     * @param QuoteDetails $quoteDetails
     */
    public function __construct(
        QuoteDetails $quoteDetails
    ) {
        $this->quoteDetails = $quoteDetails;
    }

    /**
     * After Populate Address Data
     *
     * @param CommonTaxCollector $subject
     * @param QuoteDetailsInterface $result
     * @param QuoteDetailsInterface $quoteDetails
     * @param QuoteAddress $address
     * @return QuoteDetailsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPopulateAddressData(
        CommonTaxCollector    $subject,
        QuoteDetailsInterface $result,
        QuoteDetailsInterface $quoteDetails,
        QuoteAddress          $address
    ): QuoteDetailsInterface {
        return $this->quoteDetails->execute($result, $address);
    }
}
