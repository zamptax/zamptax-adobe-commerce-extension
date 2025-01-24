<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

namespace ATF\Zamp\Model\System\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\AbstractModel;

class LogLifetime extends Value
{
    public const MINIMUM_LIFETIME_IN_DAYS = 30;

    /**
     * Set default data if empty fields have been left
     *
     * @return $this|AbstractModel
     */
    public function beforeSave()
    {
        $currentValue = $this->getValue();
        if (!$currentValue || $currentValue < self::MINIMUM_LIFETIME_IN_DAYS) {
            $this->setValue((string)self::MINIMUM_LIFETIME_IN_DAYS);
        }
        return $this;
    }
}
