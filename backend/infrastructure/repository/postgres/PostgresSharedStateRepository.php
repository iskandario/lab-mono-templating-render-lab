<?php

declare(strict_types=1);

namespace infrastructure\repository\postgres;

use DateTimeImmutable;
use PDO;

final class PostgresSharedStateRepository extends PostgresRepository
{
    public function __construct(PDO $connection)
    {
        parent::__construct($connection);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function save(string $stateId, string $ownerId, array $state, DateTimeImmutable $createdAt): void
    {
        $this->execute(
            <<<SQL
            INSERT INTO shared_states (
                state_id,
                owner_id,
                state_json,
                created_at
            ) VALUES (
                :state_id,
                :owner_id,
                :state_json,
                :created_at
            )
            SQL,
            [
                'state_id' => $stateId,
                'owner_id' => $ownerId,
                'state_json' => $state,
                'created_at' => $createdAt,
            ]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $stateId): ?array
    {
        $row = $this->fetchOne(
            'SELECT state_json FROM shared_states WHERE state_id = :state_id',
            ['state_id' => $stateId]
        );

        if ($row === null) {
            return null;
        }

        return JsonValue::decode((string)$row['state_json']);
    }
}
