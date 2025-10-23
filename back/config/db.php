<?php
// MAMP: user root / mdp vide (par défaut)
$DB_HOST = 'localhost';
$DB_PORT = '8889'; // MAMP MySQL par défaut = 8889 (vérifie dans MAMP > Preferences > Ports)
$DB_NAME = 'vite_gourmand';
$DB_USER = 'root';
$DB_PASS = '';

// DSN + options
$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  die("Erreur base de données : " . $e->getMessage());
}
