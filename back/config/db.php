<?php
// back/config/db.php

// =======================
// CONFIGURATION BDD
// =======================

// 🔹 LOCAL (MAMP)
$LOCAL_DB = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'name' => 'vite_gourmand',
    'user' => 'root',
    'pass' => 'root',
];

// 🔹 PRODUCTION (AlwaysData)
// ⚠️ remplace par TES vraies infos AlwaysData
$PROD_DB = [
    'host' => 'mysql-jean-baptiste.alwaysdata.net',
    'port' => 3306,
    'name' => 'jean-baptiste_vite_gourmand',
    'user' => 'jean-baptiste',
    'pass' => 'A2e4t6u8_',
];

// =======================
// DÉTECTION ENVIRONNEMENT
// =======================
$isProd = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);

$db = $isProd ? $PROD_DB : $LOCAL_DB;

// =======================
// CONNEXION PDO
// =======================
function pdo(): PDO
{
    global $db;
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['port'],
        $db['name']
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
    return $pdo;
}

// Alias utilisé dans ton code
if (!class_exists('DB')) {
    class DB {
        public static function getConnection(): PDO {
            return pdo();
        }
    }
}