<?php
namespace H4ck3r31\BankAccountExample\Infrastructure\Domain\Model\Iban;

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
use H4ck3r31\BankAccountExample\Domain\Model\Bank\Bank;
use H4ck3r31\BankAccountExample\Domain\Model\Iban\ExistingIban;
use H4ck3r31\BankAccountExample\Domain\Model\Iban\Iban;
use H4ck3r31\BankAccountExample\Domain\Model\Iban\MaximumIban;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Projection\ProjectionManager;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\Saga;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\EventSelector;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\EventStorePool;
use TYPO3\CMS\EventSourcing\Infrastructure\Domain\Model\Base\EventRepository;

/**
 * Repository organizing events for Iban
 */
class IbanEventRepository implements EventRepository
{
    /**
     * @return IbanEventRepository
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * @param Iban $iban
     * @return Iban|null
     */
    public function findByIban(Iban $iban)
    {
        $streamName = Common::STREAM_PREFIX . '/IBAN';
        $eventSelector = EventSelector::instance()->setStreamName($streamName);

        $existingIban = new ExistingIban($iban);
        Saga::create($eventSelector)->tell($existingIban);

        return $existingIban->getExistingIban();
    }

    /**
     * @param Bank $bank
     * @return Iban
     */
    public function determineNextByBank(Bank $bank)
    {
        $streamName = Common::STREAM_PREFIX . '/IBAN';
        $eventSelector = EventSelector::instance()->setStreamName($streamName);

        $maximumIban = new MaximumIban($bank);
        Saga::create($eventSelector)->tell($maximumIban);

        return $maximumIban->incrementAccountNumber();
    }

    public function commit(Iban $iban)
    {
        foreach ($iban->getRecordedEvents() as $event) {
            $this->commitEvent($event);
        }

        ProjectionManager::provide()->projectEvents($iban->getRecordedEvents());
        $iban->purgeRecordedEvents();
    }

    /**
     * @param BaseEvent $event
     */
    public function commitEvent(BaseEvent $event)
    {
        $streamName = Common::STREAM_PREFIX . '/IBAN';

        $eventSelector = EventSelector::instance()
            ->setEvents([get_class($event)])
            ->setStreamName($streamName);

        EventStorePool::provide()
            ->getAllFor($eventSelector)
            ->attach($streamName, $event);
    }
}
