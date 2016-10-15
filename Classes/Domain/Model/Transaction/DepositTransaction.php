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

use H4ck3r31\BankAccountExample\Domain\Model\Iban\Iban;
use H4ck3r31\BankAccountExample\Domain\Model\Transaction\Event\CreatedDepositTransactionEvent;
use H4ck3r31\BankAccountExample\Domain\Model\Common\CommandException;
use Ramsey\Uuid\Uuid;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Event\EventHandlerTrait;

/**
 * DepositTransaction
 */
class DepositTransaction extends AbstractTransaction
{
    use EventHandlerTrait;

    /**
     * @param array $data
     * @return DepositTransaction
     */
    public static function buildFromProjection(array $data)
    {
        $transaction = static::instance();
        $transaction->projected = true;
        $transaction->transactionId = Uuid::fromString($data['transactionId']);
        $transaction->iban = Iban::fromString($data['iban']);
        $transaction->money = Money::create($data['money']);
        $transaction->reference = TransactionReference::create($data['reference']);
        $transaction->entryDate = new \DateTimeImmutable($data['entryDate']);
        $transaction->availabilityDate = new \DateTimeImmutable($data['availabilityDate']);
        return $transaction;
    }

    /**
     * @return DepositTransaction
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @return string
     */
    protected function getTransactionType()
    {
        return get_class($this);
    }


    /**
     * Command handlers
     */

    /**
     * @param Iban $iban
     * @param Money $money
     * @param TransactionReference $reference
     * @param \DateTime|null $availabilityDate
     * @return DepositTransaction
     * @throws CommandException
     */
    public static function createTransaction(
        Iban $iban,
        Money $money,
        TransactionReference $reference,
        \DateTime $availabilityDate = null
    ) {
        $entryDate = new \DateTime('now');

        if ($availabilityDate === null) {
            $availabilityDate = $entryDate;
        } elseif ($availabilityDate < $entryDate) {
            throw new CommandException('Availability date cannot be before entry date', 1471512962);
        }

        $transaction = static::instance();
        $transactionId = Uuid::uuid4();

        $event = CreatedDepositTransactionEvent::create(
            $iban,
            $transactionId,
            $money,
            $reference,
            $entryDate,
            $availabilityDate
        );
        // Transactions are handled in the bounds of Account
        // that's the reason why it's not published, but just applied
        $transaction->applyEvent($event);

        return $transaction;
    }


    /*
     * Event handling
     */

    /**
     * @param CreatedDepositTransactionEvent $event
     */
    protected function applyCreatedDepositTransactionEvent(CreatedDepositTransactionEvent $event)
    {
        $this->transactionId = $event->getTransactionId();
        $this->iban = $event->getIban();
        $this->money = $event->getMoney();
        $this->reference = $event->getReference();
        $this->entryDate = $event->getEntryDate();
        $this->availabilityDate = $event->getAvailabilityDate();
    }
}
