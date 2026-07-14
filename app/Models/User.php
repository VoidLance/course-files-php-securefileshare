<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function create(array $data): bool
    {
        $sql = 'INSERT INTO users (name, email, password_hash, role, storage_quota_bytes, email_verified_at)
                VALUES (:name, :email, :password_hash, :role, :storage_quota_bytes, NULL)';

        $statement = Database::connection()->prepare($sql);

        return $statement->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role' => $data['role'] ?? 'regular',
            ':storage_quota_bytes' => $data['storage_quota_bytes'] ?? 52428800,
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute([':email' => $email]);
        $result = $statement->fetch();

        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $result = $statement->fetch();

        return $result ?: null;
    }

    public function updateProfile(int $id, string $name, ?string $avatarPath): bool
    {
        $sql = 'UPDATE users SET name = :name, avatar_path = :avatar_path WHERE id = :id';
        $statement = Database::connection()->prepare($sql);

        return $statement->execute([
            ':id' => $id,
            ':name' => $name,
            ':avatar_path' => $avatarPath,
        ]);
    }

    public function storageUsageBytes(int $id): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COALESCE(SUM(size_bytes), 0) AS total FROM files WHERE user_id = :user_id'
        );
        $statement->execute([':user_id' => $id]);
        $result = $statement->fetch();

        return (int) ($result['total'] ?? 0);
    }
}
