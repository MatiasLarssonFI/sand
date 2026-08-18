<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Shared\TimeRange;
use PDO;

final class PdoEventRepository implements EventRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByCalendarAndRange(int $calendarId, TimeRange $rangeUtc): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, calendar_id, title, description, starts_at, ends_at, created_by_user_id, updated_by_user_id
             FROM events
             WHERE calendar_id = :calendar_id
               AND starts_at < :end_at
               AND ends_at > :start_at
             ORDER BY starts_at'
        );
        $statement->execute([
            'calendar_id' => $calendarId,
            'start_at' => $rangeUtc->start()->format('Y-m-d H:i:s'),
            'end_at' => $rangeUtc->end()->format('Y-m-d H:i:s'),
        ]);

        return array_map(fn (array $row): Event => $this->mapEvent($row), $statement->fetchAll());
    }

    public function findById(int $eventId): ?Event
    {
        $statement = $this->pdo->prepare(
            'SELECT id, calendar_id, title, description, starts_at, ends_at, created_by_user_id, updated_by_user_id
             FROM events
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $eventId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapEvent($row);
    }

    public function save(Event $event): Event
    {
        if ($event->id() === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO events (calendar_id, title, description, starts_at, ends_at, created_by_user_id, updated_by_user_id)
                 VALUES (:calendar_id, :title, :description, :starts_at, :ends_at, :created_by_user_id, :updated_by_user_id)'
            );
            $statement->execute($this->eventParams($event));

            return new Event(
                (int) $this->pdo->lastInsertId(),
                $event->calendarId(),
                $event->title(),
                $event->description(),
                $event->timeRangeUtc(),
                $event->createdByUserId(),
                $event->updatedByUserId(),
            );
        }

        $statement = $this->pdo->prepare(
            'UPDATE events
             SET title = :title,
                 description = :description,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 updated_by_user_id = :updated_by_user_id
             WHERE id = :id'
        );
        $statement->execute($this->eventParams($event) + ['id' => $event->id()]);

        return $event;
    }

    public function delete(int $eventId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM events WHERE id = :id');
        $statement->execute(['id' => $eventId]);
    }

    private function mapEvent(array $row): Event
    {
        return new Event(
            (int) $row['id'],
            (int) $row['calendar_id'],
            $row['title'],
            $row['description'],
            new TimeRange(
                new \DateTimeImmutable($row['starts_at'], new \DateTimeZone('UTC')),
                new \DateTimeImmutable($row['ends_at'], new \DateTimeZone('UTC'))
            ),
            (int) $row['created_by_user_id'],
            (int) $row['updated_by_user_id']
        );
    }

    private function eventParams(Event $event): array
    {
        return [
            'calendar_id' => $event->calendarId(),
            'title' => $event->title(),
            'description' => $event->description(),
            'starts_at' => $event->timeRangeUtc()->start()->format('Y-m-d H:i:s'),
            'ends_at' => $event->timeRangeUtc()->end()->format('Y-m-d H:i:s'),
            'created_by_user_id' => $event->createdByUserId(),
            'updated_by_user_id' => $event->updatedByUserId(),
        ];
    }
}
