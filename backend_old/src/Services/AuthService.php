<?php

namespace App\Services;

use App\Config\Database;
use SimpleJWT;
use PDO;
use Exception;

class AuthService
{
    private array $jwtConfig;

    public function __construct(array $jwtConfig)
    {
        $this->jwtConfig = $jwtConfig;
    }

    public function authenticate(string $login, string $password): ?array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare('
            SELECT id, login, password_hash, name, email, role, active, last_login_at
            FROM users 
            WHERE (login = ? OR email = ?) AND active = TRUE
        ');
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        $this->updateLastLogin($user['id']);

        unset($user['password_hash']);
        return $user;
    }

    public function generateToken(array $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->jwtConfig['expiration'];

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $user['id'],
            'login' => $user['login'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return SimpleJWT::encode($payload, $this->jwtConfig['secret'], $this->jwtConfig['algorithm']);
    }

    public function generateRefreshToken(array $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + (30 * 24 * 60 * 60);

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $user['id'],
            'type' => 'refresh'
        ];

        return SimpleJWT::encode($payload, $this->jwtConfig['secret'], $this->jwtConfig['algorithm']);
    }

    public function verifyToken(string $token): ?object
    {
        try {
            return SimpleJWT::decode($token, $this->jwtConfig['secret'], [$this->jwtConfig['algorithm']]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getUserById(int $userId): ?array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare('
            SELECT id, login, name, email, role, active, last_login_at, created_at
            FROM users 
            WHERE id = ? AND active = TRUE
        ');
        $stmt->execute([$userId]);
        
        return $stmt->fetch() ?: null;
    }

    public function getUserByLogin(string $login): ?array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare('
            SELECT id, login, name, email, role, active, last_login_at, created_at
            FROM users 
            WHERE (login = ? OR email = ?) AND active = TRUE
        ');
        $stmt->execute([$login, $login]);
        
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $db = Database::getConnection();
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare('
            UPDATE users 
            SET password_hash = ?, updated_at = NOW()
            WHERE id = ?
        ');
        
        return $stmt->execute([$passwordHash, $userId]);
    }

    private function updateLastLogin(int $userId): void
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare('
            UPDATE users 
            SET last_login_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
