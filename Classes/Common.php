<?php
namespace H4ck3r31\BankAccountExample;

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

use H4ck3r31\BankAccountExample\Domain\Event\AbstractAccountEvent;
use H4ck3r31\BankAccountExample\Domain\Event\AbstractTransactionEvent;
use H4ck3r31\BankAccountExample\Domain\Event\AssignedAccountEvent;
use H4ck3r31\BankAccountExample\Domain\Handler\AccountEventHandler;
use H4ck3r31\BankAccountExample\Domain\Handler\TransactionEventHandler;
use H4ck3r31\BankAccountExample\Domain\Model\Account;
use H4ck3r31\BankAccountExample\Domain\Model\Applicable\ApplicableAccount;
use H4ck3r31\BankAccountExample\Domain\Model\Applicable\ApplicableTransaction;
use H4ck3r31\BankAccountExample\Domain\Model\Transaction;
use H4ck3r31\BankAccountExample\Domain\Repository\AccountRepository;
use H4ck3r31\BankAccountExample\Domain\Repository\TransactionRepository;
use H4ck3r31\BankAccountExample\EventSourcing\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\DataHandling\Core\Domain\Event\Definition\RelationalEvent;
use TYPO3\CMS\DataHandling\Core\EventSourcing\SourceManager;
use TYPO3\CMS\DataHandling\Core\EventSourcing\Stream\StreamProvider;
use TYPO3\CMS\DataHandling\Core\Process\Projection\ProjectionPool;
use TYPO3\CMS\DataHandling\Extbase\Persistence\EntityProjection;
use TYPO3\CMS\DataHandling\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Common
 */
class Common
{
    const KEY_EXTENSION = 'bank_account_example';
    const STREAM_PREFIX = 'H4ck3r31-BankAccountExample';

    const STREAM_PREFIX_BANK = self::STREAM_PREFIX . '/Bank';
    const STREAM_PREFIX_ACCOUNT = self::STREAM_PREFIX . '/Account';
    const STREAM_PREFIX_TRANSACTION = self::STREAM_PREFIX . '/Transaction';

    /**
     * Registers requirements for event sources processing with TYPO3.
     */
    public static function registerEventSources()
    {
        ExtensionUtility::instance()
            ->addMapping('tx_bankaccountexample_domain_model_account', Account::class)
            ->addMapping('tx_bankaccountexample_domain_model_account', ApplicableAccount::class)
            ->addMapping('tx_bankaccountexample_domain_model_transaction', Transaction::class)
            ->addMapping('tx_bankaccountexample_domain_model_transaction', ApplicableTransaction::class);

        SourceManager::provide()
            ->addSourcedTableName('tx_bankaccountexample_domain_model_account')
            ->addSourcedTableName('tx_bankaccountexample_domain_model_transaction');

        StreamProvider::provide()
            ->registerStream(
                static::STREAM_PREFIX_BANK,
                Stream::instance()
                    ->setIgnoreEvent(true)
                    ->setPrefix(static::STREAM_PREFIX_BANK)
            )
            ->registerStream(
                static::STREAM_PREFIX_ACCOUNT,
                Stream::instance()
                    ->setPrefix(static::STREAM_PREFIX_ACCOUNT)
            );

        ProjectionPool::provide()
            ->enrolProjection(
                '$' . static::STREAM_PREFIX_BANK
            )
            ->setProjectionName(EntityProjection::class)
            ->on(
                RelationalEvent::class,
                function(EntityProjection $projection, RelationalEvent $event) {
                    $event->cancel();
                    $projection->triggerProjection(
                        static::STREAM_PREFIX_ACCOUNT
                        . '/' . $event->getRelationId()->toString()
                    );
                }
            );

        ProjectionPool::provide()
            ->enrolProjection(
                '$' . static::STREAM_PREFIX_ACCOUNT . '/*',
                '[' . AbstractAccountEvent::class . ']'
            )
            ->setEventHandlerName(AccountEventHandler::class)
            ->setRepositoryName(AccountRepository::class)
            ->setProjectionName(EntityProjection::class)
            ->setSubjectName(Account::class)
            ->on(
                RelationalEvent::class,
                function(EntityProjection $projection, AssignedAccountEvent $event) {
                    $event->cancel();
                    $projection->triggerProjection(
                        static::STREAM_PREFIX_ACCOUNT
                        . '/' . $event->getRelationId()->toString()
                    );
                }
            );

        ProjectionPool::provide()
            ->enrolProjection(
                '$' . static::STREAM_PREFIX_TRANSACTION . '/*',
                '[' . AbstractTransactionEvent::class . ']'
            )
            ->setEventHandlerName(TransactionEventHandler::class)
            ->setRepositoryName(TransactionRepository::class)
            ->setProjectionName(EntityProjection::class)
            ->setSubjectName(Transaction::class);
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
