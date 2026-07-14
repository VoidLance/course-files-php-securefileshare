<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ActivityLog
{
    public function log(int $userId, string $eventType, string $description, ?string $ipAddress = null): bool
    {
        $sql = 'INSERT INTO activity_logs (user_id, event_type, description, ip_address)
                VALUES (:user_id, :event_type, :description, :ip_address)';

        $statement = Database::connection()->prepare($sql);

        return $statement->execute([
            ':user_id' => $userId,
            ':event_type' => $eventType,
            ':description' => $description,
            ':ip_address' => $ipAddress,
        ]);
    }

    public function latestForUser(int $userId, int $limit = 10): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit_rows'
        );
        $statement->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':limit_rows', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
