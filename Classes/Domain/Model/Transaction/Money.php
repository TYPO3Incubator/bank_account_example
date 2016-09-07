<?php
namespace H4ck3r31\BankAccountExample\Domain\Model\Transaction;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\DataHandling\Core\Framework\Object\RepresentableAsString;

/**
 * Money
 */
class Money implements RepresentableAsString
{
    /**
     * @param float $value
     * @return Money
     */
    public static function create(float $value)
    {
        if ($value === 0.0) {
            throw new \InvalidArgumentException('Amount is zero');
        }
        if ($value < 0) {
            throw new \InvalidArgumentException('Amount is less than zero');
        }

        return new static($value);
    }

    /**
     * @var float
     */
    private $value;

    /**
     * @param float $value
     */
    private function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
