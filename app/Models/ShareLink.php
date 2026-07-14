<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ShareLink
{
    public function create(array $data): bool
    {
        $sql = 'INSERT INTO share_links (
                    file_id, token, permission, expires_at, password_hash, revoked_at, created_by
                ) VALUES (
                    :file_id, :token, :permission, :expires_at, :password_hash, NULL, :created_by
                )';

        $statement = Database::connection()->prepare($sql);

        return $statement->execute([
            ':file_id' => $data['file_id'],
            ':token' => $data['token'],
            ':permission' => $data['permission'] ?? 'view',
            ':expires_at' => $data['expires_at'],
            ':password_hash' => $data['password_hash'],
            ':created_by' => $data['created_by'],
        ]);
    }

    public function allForUser(int $userId): array
    {
        $sql = 'SELECT s.*, f.original_name
                FROM share_links s
                INNER JOIN files f ON f.id = s.file_id
                WHERE f.user_id = :user_id
                ORDER BY s.created_at DESC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function findByToken(string $token): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, f.original_name, f.storage_name, f.iv_base64, f.mime_type
             FROM share_links s
             INNER JOIN files f ON f.id = s.file_id
             WHERE s.token = :token
             LIMIT 1'
        );
        $statement->execute([':token' => $token]);
        $result = $statement->fetch();

        return $result ?: null;
    }

    public function revoke(int $id, int $createdBy): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE share_links SET revoked_at = CURRENT_TIMESTAMP WHERE id = :id AND created_by = :created_by'
        );

        return $statement->execute([':id' => $id, ':created_by' => $createdBy]);
    }
}
