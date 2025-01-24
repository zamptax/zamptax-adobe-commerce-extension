<?php declare(strict_types=1);
/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
namespace ATF\Zamp\Model\Config\Backend;

use Magento\Framework\Exception\LocalizedException;

class Encrypted extends \Magento\Config\Model\Config\Backend\Encrypted
{
    private const VALID_TOKEN_CHARACTERS = 64;

    /**
     * Config validations before saving
     *
     * @return void
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $this->_dataSaveAllowed = false;
        $value = $this->getValue();

        // don't save value, if an obscured value was received. This indicates that data was not changed.
        if ($value && !preg_match('/^\*+$/', $value)) {
            if (strlen(trim($value)) !== self::VALID_TOKEN_CHARACTERS) {
                throw new LocalizedException(
                    __('API Token must contain exactly 64 characters.')
                );
            } else {
                $this->_dataSaveAllowed = true;
                $encrypted = $this->_encryptor->encrypt($value);
                $this->setValue($encrypted);
            }
        }
    }
}
