<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Preference\Model\Quote\Address;

class Total extends \Magento\Quote\Model\Quote\Address\Total
{
    /**
     * Returns the title
     *
     * @return mixed
     * @since 100.1.0
     */
    public function getTitle(): mixed
    {
        return $this->getData('title');
    }
}
