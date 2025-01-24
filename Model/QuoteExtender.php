<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model;

use ATF\Zamp\Model\Extend\QuoteDetails;
use ATF\Zamp\Model\Extend\QuoteDetailsItem;

class QuoteExtender
{
    /**
     * @var QuoteDetails
     */
    private QuoteDetails $extendQuoteDetails;

    /**
     * @var QuoteDetailsItem
     */
    private QuoteDetailsItem $extendQuoteDetailsItem;

    /**
     * @param QuoteDetails $extendQuoteDetails
     * @param QuoteDetailsItem $extendQuoteDetailsItem
     */
    public function __construct(
        QuoteDetails          $extendQuoteDetails,
        QuoteDetailsItem      $extendQuoteDetailsItem
    ) {
        $this->extendQuoteDetails = $extendQuoteDetails;
        $this->extendQuoteDetailsItem = $extendQuoteDetailsItem;
    }

    /**
     * Getter Quote Details
     *
     * @return QuoteDetails
     */
    public function getQuoteDetails(): QuoteDetails
    {
        return $this->extendQuoteDetails;
    }

    /**
     * Getter Quote Details Item
     *
     * @return QuoteDetailsItem
     */
    public function getQuoteDetailsItem(): QuoteDetailsItem
    {
        return $this->extendQuoteDetailsItem;
    }
}
