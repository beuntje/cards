<?php

namespace Cards;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'database';
            $name = $_ENV['DB_NAME'] ?? 'lamp';
            $user = $_ENV['DB_USER'] ?? 'lamp';
            $pass = $_ENV['DB_PASSWORD'] ?? 'lamp';

            self::$instance = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }
        return self::$instance;
    }

    public static function migrate(): void
    {
        $db = self::getInstance();

        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            number VARCHAR(255) NOT NULL,
            barcode_type VARCHAR(50) DEFAULT 'code128',
            favorite TINYINT(1) DEFAULT 0,
            logo VARCHAR(255) NULL,
            color VARCHAR(7) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS card_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT NOT NULL,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            latitude DECIMAL(10, 7) NULL,
            longitude DECIMAL(10, 7) NULL,
            FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
            INDEX idx_card_id (card_id),
            INDEX idx_location (latitude, longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Migration: drop last_use column if it exists
        $stmt = $db->query("SHOW COLUMNS FROM cards LIKE 'last_use'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE cards DROP COLUMN last_use");
        }

        // Migration: add logo and color columns if missing
        $stmt = $db->query("SHOW COLUMNS FROM cards LIKE 'logo'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE cards ADD COLUMN logo VARCHAR(255) NULL AFTER favorite");
            $db->exec("ALTER TABLE cards ADD COLUMN color VARCHAR(7) NULL AFTER logo");
        }
    }
}
