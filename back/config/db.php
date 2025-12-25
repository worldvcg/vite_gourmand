<?php
// ⚙️ Paramètres MAMP
const DB_HOST = '127.0.0.1';
const DB_PORT = 8889; // MAMP MySQL
const DB_NAME = 'vite_gourmand';
const DB_USER = 'root';
const DB_PASS = 'root';
const MAMP_SOCKET = '/Applications/MAMP/tmp/mysql/mysql.sock';

function pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $pdo;
    } catch (PDOException $e) {
        if (file_exists(MAMP_SOCKET)) {
            $dsn = 'mysql:unix_socket='.MAMP_SOCKET.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
            return $pdo;
        }
        throw $e;
    }
}

if (!class_exists('DB')) {
    class DB {
        public static function getConnection() { return pdo(); }
    }
}