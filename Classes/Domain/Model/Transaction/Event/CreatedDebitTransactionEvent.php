<?php
namespace H4ck3r31\BankAccountExample\Domain\Model\Transaction\Event;

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

use H4ck3r31\BankAccountExample\Common;
use H4ck3r31\BankAccountExample\Domain\Model\AbstractEvent;
use H4ck3r31\BankAccountExample\Domain\Model\Iban\Iban;
use H4ck3r31\BankAccountExample\Domain\Model\Transaction\Money;
use H4ck3r31\BankAccountExample\Domain\Model\Transaction\TransactionReference;
use H4ck3r31\BankAccountExample\Domain\Object\Transactional;
use H4ck3r31\BankAccountExample\Domain\Object\TransactionalTrait;
use H4ck3r31\BankAccountExample\Domain\Object\TransactionIdentifiable;
use H4ck3r31\BankAccountExample\Domain\Object\TransactionIdentifiableTrait;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Event\EntityEvent;
use TYPO3\CMS\DataHandling\Core\Framework\Object\Instantiable;

/**
 * CreatedDebitTransactionEvent
 */
class CreatedDebitTransactionEvent extends AbstractEvent implements Instantiable, EntityEvent, Transactional, TransactionIdentifiable
{
    use TransactionalTrait;
    use TransactionIdentifiableTrait;

    /**
     * @return CreatedDebitTransactionEvent
     */
    public static function instance()
    {
        return Common::getObjectManager()->get(static::class);
    }

    /**
     * @param Iban $iban
     * @param UuidInterface $transactionId
     * @param Money $money
     * @param TransactionReference $reference
     * @param \DateTime $entryDate
     * @param \DateTime $availabilityDate
     * @return CreatedDebitTransactionEvent
     */
    public static function create(
        Iban $iban,
        UuidInterface $transactionId,
        Money $money,
        TransactionReference $reference,
        \DateTime $entryDate,
        \DateTime $availabilityDate
    ) {
        $event = static::instance();
        $event->iban = $iban;
        $event->transactionId = $transactionId;
        $event->money = $money;
        $event->reference = $reference;
        $event->entryDate = $entryDate;
        $event->availabilityDate = $availabilityDate;
        return $event;
    }
}