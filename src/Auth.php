<?php

namespace Cards;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class Auth
{
    private const COOKIE_NAME = 'cards_token';
    private const EXPIRY_SECONDS = 365 * 24 * 3600; // 1 year
    private const REFRESH_AFTER = 30 * 24 * 3600;   // 1 month

    public static function register(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);
        return true;
    }

    public static function login(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        self::setTokenCookie($user['id'], $email);
        return true;
    }

    public static function logout(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function getUser(): ?array
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!$token) {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $data = (array) $decoded;

            // Auto-refresh token if older than 1 month
            if (isset($data['iat']) && (time() - $data['iat']) > self::REFRESH_AFTER) {
                self::setTokenCookie($data['sub'], $data['email']);
            }

            return [
                'id' => $data['sub'],
                'email' => $data['email'],
            ];
        } catch (ExpiredException $e) {
            self::logout();
            return null;
        } catch (\Exception $e) {
            self::logout();
            return null;
        }
    }

    private static function setTokenCookie(int $userId, string $email): void
    {
        $now = time();
        $payload = [
            'sub' => $userId,
            'email' => $email,
            'iat' => $now,
            'exp' => $now + self::EXPIRY_SECONDS,
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
        $isProduction = ($_ENV['APP_ENV'] ?? 'production') === 'production';

        setcookie(self::COOKIE_NAME, $jwt, [
            'expires' => $now + self::EXPIRY_SECONDS,
            'path' => '/',
            'httponly' => true,
            'secure' => $isProduction,
            'samesite' => 'Lax',
        ]);
    }
}
