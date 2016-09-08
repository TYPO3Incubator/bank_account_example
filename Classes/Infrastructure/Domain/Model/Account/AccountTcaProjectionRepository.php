<?php
namespace H4ck3r31\BankAccountExample\Infrastructure\Domain\Model\Account;

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
use H4ck3r31\BankAccountExample\Domain\Model\Account\Account;
use H4ck3r31\BankAccountExample\Domain\Model\Bank\Bank;
use H4ck3r31\BankAccountExample\Domain\Model\Iban\Iban;
use H4ck3r31\BankAccountExample\Infrastructure\Domain\Model\Iban\IbanProjectionRepository;
use H4ck3r31\BankAccountExample\Infrastructure\Domain\Model\DatabaseFieldNameConverter;
use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\DataHandling\Core\Framework\Domain\Repository\ProjectionRepository;
use TYPO3\CMS\DataHandling\Core\Framework\Process\Projection\TcaProjectionService;

/**
 * Repository organizing TCA projections for Account
 */
class AccountTcaProjectionRepository implements ProjectionRepository
{
    const TABLE_NAME = 'tx_bankaccountexample_domain_model_account';

    /**
     * @return AccountTcaProjectionRepository
     */
    public static function instance()
    {
        return Common::getObjectManager()->get(static::class);
    }

    /**
     * @param UuidInterface $aggregateId
     * @return mixed
     */
    public function findByAggregateId(UuidInterface $aggregateId)
    {
        return TcaProjectionService::findByUuid(
            static::TABLE_NAME,
            $aggregateId
        );
    }

    public function add(array $data)
    {
        $data = TcaProjectionService::mapFieldNames(static::TABLE_NAME, $data);
        $data = TcaProjectionService::addCreateFieldValues(static::TABLE_NAME, $data);
        Common::getDatabaseConnection()
            ->insert(static::TABLE_NAME, $data);
    }

    public function update(string $identifier, array $data)
    {
        $data = DatabaseFieldNameConverter::toDatabase($data);
        $data = TcaProjectionService::mapFieldNames(static::TABLE_NAME, $data);
        $data = TcaProjectionService::addUpdateFieldValues(static::TABLE_NAME, $data);
        $identifier = [\TYPO3\CMS\DataHandling\Common::FIELD_UUID => $identifier];
        Common::getDatabaseConnection()
            ->update(static::TABLE_NAME, $data, $identifier);
    }
}