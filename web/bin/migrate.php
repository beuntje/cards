<?php

use Cards\Database;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
Database::migrate();

echo "Cards database schema is up to date.\n";
