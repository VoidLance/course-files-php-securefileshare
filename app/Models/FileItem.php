<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class FileItem
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO files (
                    user_id, original_name, storage_name, mime_type, size_bytes,
                    folder_name, tags, checksum_sha256, iv_base64, version_number
                ) VALUES (
                    :user_id, :original_name, :storage_name, :mime_type, :size_bytes,
                    :folder_name, :tags, :checksum_sha256, :iv_base64, :version_number
                )';

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            ':user_id' => $data['user_id'],
            ':original_name' => $data['original_name'],
            ':storage_name' => $data['storage_name'],
            ':mime_type' => $data['mime_type'],
            ':size_bytes' => $data['size_bytes'],
            ':folder_name' => $data['folder_name'] ?? 'root',
            ':tags' => $data['tags'] ?? null,
            ':checksum_sha256' => $data['checksum_sha256'],
            ':iv_base64' => $data['iv_base64'],
            ':version_number' => $data['version_number'] ?? 1,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function allForUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM files WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM files WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute([':id' => $id, ':user_id' => $userId]);
        $result = $statement->fetch();

        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM files WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $result = $statement->fetch();

        return $result ?: null;
    }

    public function searchForUser(int $userId, string $query): array
    {
        $sql = 'SELECT * FROM files
                WHERE user_id = :user_id
                AND (original_name LIKE :query OR folder_name LIKE :query OR tags LIKE :query)
                ORDER BY created_at DESC';

        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            ':user_id' => $userId,
            ':query' => '%' . $query . '%',
        ]);

        return $statement->fetchAll();
    }
}
