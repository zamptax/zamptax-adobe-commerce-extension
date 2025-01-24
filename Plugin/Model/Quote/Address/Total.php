<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Plugin\Model\Quote\Address;

use ATF\Zamp\ViewModel\TaxViewModel;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total as BaseTotal;

class Total
{
    /**
     * @var TaxViewModel
     */
    private $taxViewModel;

    /**
     * @param TaxViewModel $taxViewModel
     */
    public function __construct(
        TaxViewModel $taxViewModel
    ) {
        $this->taxViewModel = $taxViewModel;
    }

    /**
     * Plugin for getTitle method
     *
     * @param BaseTotal $subject
     * @param mixed $result
     * @return Phrase|mixed
     */
    public function afterGetTitle(
        BaseTotal $subject,
        mixed $result
    ) {
        if (($defaultLabel = $result->render()) === 'Tax') {
            /** @var Address $address */
            $address = $subject->getAddress();
            $quote = $address->getQuote();

            return __($this->taxViewModel->getTaxLabel($quote, $defaultLabel));
        }

        return $result;
    }
}
