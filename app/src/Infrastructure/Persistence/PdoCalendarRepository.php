<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Calendar\Calendar;
use App\Domain\Calendar\CalendarMember;
use App\Domain\Calendar\CalendarRepositoryInterface;
use PDO;

final class PdoCalendarRepository implements CalendarRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAccessibleByUserId(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.name, c.timezone
             FROM calendars c
             INNER JOIN calendar_members cm ON cm.calendar_id = c.id
             WHERE cm.user_id = :user_id
             ORDER BY c.name'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map(
            fn (array $row): Calendar => new Calendar((int) $row['id'], $row['name'], $row['timezone']),
            $statement->fetchAll()
        );
    }

    public function findById(int $calendarId): ?Calendar
    {
        $statement = $this->pdo->prepare('SELECT id, name, timezone FROM calendars WHERE id = :id');
        $statement->execute(['id' => $calendarId]);
        $row = $statement->fetch();

        return $row === false ? null : new Calendar((int) $row['id'], $row['name'], $row['timezone']);
    }

    public function findMemberships(int $calendarId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, calendar_id, user_id, role
             FROM calendar_members
             WHERE calendar_id = :calendar_id
             ORDER BY role, user_id'
        );
        $statement->execute(['calendar_id' => $calendarId]);

        return array_map(fn (array $row): CalendarMember => $this->mapMembership($row), $statement->fetchAll());
    }

    public function findMembership(int $calendarId, int $userId): ?CalendarMember
    {
        $statement = $this->pdo->prepare(
            'SELECT id, calendar_id, user_id, role
             FROM calendar_members
             WHERE calendar_id = :calendar_id AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['calendar_id' => $calendarId, 'user_id' => $userId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapMembership($row);
    }

    public function findMembershipById(int $membershipId): ?CalendarMember
    {
        $statement = $this->pdo->prepare(
            'SELECT id, calendar_id, user_id, role
             FROM calendar_members
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $membershipId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapMembership($row);
    }

    public function createMembership(int $calendarId, int $userId, string $role): CalendarMember
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO calendar_members (calendar_id, user_id, role) VALUES (:calendar_id, :user_id, :role)'
        );
        $statement->execute([
            'calendar_id' => $calendarId,
            'user_id' => $userId,
            'role' => $role,
        ]);

        return new CalendarMember((int) $this->pdo->lastInsertId(), $calendarId, $userId, $role);
    }

    public function updateMembershipRole(int $membershipId, string $role): void
    {
        $statement = $this->pdo->prepare('UPDATE calendar_members SET role = :role WHERE id = :id');
        $statement->execute(['id' => $membershipId, 'role' => $role]);
    }

    public function deleteMembership(int $membershipId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM calendar_members WHERE id = :id');
        $statement->execute(['id' => $membershipId]);
    }

    public function countOwners(int $calendarId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM calendar_members WHERE calendar_id = :calendar_id AND role = :role'
        );
        $statement->execute(['calendar_id' => $calendarId, 'role' => CalendarMember::ROLE_OWNER]);

        return (int) $statement->fetchColumn();
    }

    private function mapMembership(array $row): CalendarMember
    {
        return new CalendarMember(
            (int) $row['id'],
            (int) $row['calendar_id'],
            (int) $row['user_id'],
            $row['role']
        );
    }
}
