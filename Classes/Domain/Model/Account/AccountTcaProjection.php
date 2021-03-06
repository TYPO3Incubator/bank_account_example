<?php
namespace H4ck3r31\BankAccountExample\Domain\Model\Account;

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

use H4ck3r31\BankAccountExample\Domain\Model\Account\Event\AttachedDebitTransactionEvent;
use H4ck3r31\BankAccountExample\Domain\Model\Account\Event\AttachedDepositTransactionEvent;
use H4ck3r31\BankAccountExample\Domain\Model\Account\Event\ChangedAccountHolderEvent;
use H4ck3r31\BankAccountExample\Domain\Model\Account\Event\ClosedAccountEvent;
use H4ck3r31\BankAccountExample\Domain\Model\Account\Event\CreatedAccountEvent;
use H4ck3r31\BankAccountExample\Infrastructure\Domain\Model\Account\AccountEventRepository;
use H4ck3r31\BankAccountExample\Infrastructure\Domain\Model\Account\AccountTcaProjectionRepository;
use TYPO3\CMS\DataHandling\Core\Domain\Model\Base\Projection\TcaProjectionService;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Projection\Projection;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\Saga;

/**
 * AccountProjection
 */
final class AccountTcaProjection implements Projection
{
    /**
     * @return string[]
     */
    public function listensTo()
    {
        return [
            CreatedAccountEvent::class,
            ChangedAccountHolderEvent::class,
            ClosedAccountEvent::class,
            AttachedDepositTransactionEvent::class,
            AttachedDebitTransactionEvent::class,
        ];
    }

    /**
     * @param BaseEvent $event
     */
    public function project(BaseEvent $event)
    {
        if ($event instanceof CreatedAccountEvent) {
            $this->projectCreatedAccountEvent($event);
        }
        if ($event instanceof ChangedAccountHolderEvent) {
            $this->projectChangedAccountHolderEvent($event);
        }
        if ($event instanceof ClosedAccountEvent) {
            $this->projectClosedAccountEvent($event);
        }
        if ($event instanceof AttachedDepositTransactionEvent) {
            $this->projectAttachedDepositTransactionEvent($event);
        }
        if ($event instanceof AttachedDebitTransactionEvent) {
            $this->projectAttachedDebitTransactionEvent($event);
        }
    }

    /**
     * @param CreatedAccountEvent $event
     */
    private function projectCreatedAccountEvent(CreatedAccountEvent $event)
    {
        $account = AccountEventRepository::instance()
            ->findByIban(
                $event->getIban(),
                $event->getEventId(),
                Saga::EVENT_INCLUDING
            );
        $accountData = TcaProjectionService::addAggregateId(
            $event->getAggregateId(),
            $account->toArray()
        );

        AccountTcaProjectionRepository::instance()->add($accountData);
    }

    /**
     * @param ChangedAccountHolderEvent $event
     */
    private function projectChangedAccountHolderEvent(ChangedAccountHolderEvent $event)
    {
        AccountTcaProjectionRepository::instance()->update(
            $event->getAggregateId()->toString(),
            ['accountHolder' => $event->getAccountHolder()->getValue()]
        );
    }

    /**
     * @param ClosedAccountEvent $event
     */
    private function projectClosedAccountEvent(ClosedAccountEvent $event)
    {
        AccountTcaProjectionRepository::instance()->update(
            (string)$event->getIban(),
            ['closed' => true]
        );
    }

    /**
     * @param AttachedDepositTransactionEvent $event
     */
    private function projectAttachedDepositTransactionEvent(AttachedDepositTransactionEvent $event)
    {
        $accountData = AccountTcaProjectionRepository::instance()
            ->findByAggregateId($event->getAggregateId());

        AccountTcaProjectionRepository::instance()->update(
            (string)$event->getIban(),
            ['balance' => $accountData['balance'] + $event->getTransaction()->getMoney()->getValue()]
        );
    }

    /**
     * @param AttachedDebitTransactionEvent $event
     */
    private function projectAttachedDebitTransactionEvent(AttachedDebitTransactionEvent $event)
    {
        $accountData = AccountTcaProjectionRepository::instance()
            ->findByAggregateId($event->getAggregateId());

        AccountTcaProjectionRepository::instance()->update(
            (string)$event->getIban(),
            ['balance' => $accountData['balance'] - $event->getTransaction()->getMoney()->getValue()]
        );
    }
}
