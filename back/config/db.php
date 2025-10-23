<?php
// ⚙️ Paramètres MAMP par défaut
const DB_HOST = '127.0.0.1';
const DB_PORT = 8889;              // MAMP: MySQL sur 8889
const DB_NAME = 'vite_gourmand';
const DB_USER = 'root';
const DB_PASS = 'root';

// ⚙️ Chemin du socket MAMP (utile si le port pose problème)
const MAMP_SOCKET = '/Applications/MAMP/tmp/mysql/mysql.sock';

function pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $commonOpts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $lastError = null;

    // 1) Tentative via TCP (host/port)
    try {
        $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $commonOpts);
        return $pdo;
    } catch (PDOException $e) {
        $lastError = $e;
    }

    // 2) Tentative via SOCKET MAMP (si disponible)
    if (file_exists(MAMP_SOCKET)) {
        try {
            $dsn = 'mysql:unix_socket='.MAMP_SOCKET.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $commonOpts);
            return $pdo;
        } catch (PDOException $e2) {
            $lastError = $e2;
        }
    }

    // 3) Si tout échoue → remonter l'erreur détaillée
    throw $lastError ?: new RuntimeException('Unknown DB connection error');
  }

  if (!class_exists('DB')) {
  class DB {
    public static function getConnection() {
      return pdo();
    }
  }
}