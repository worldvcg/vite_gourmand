<?php
require_once __DIR__.'/../config/db.php';
header('Content-Type: text/plain; charset=utf-8');

try {
  $pdo = pdo();
  $info = $pdo->query("
    SELECT
      DATABASE() AS dbname,
      @@hostname AS host,
      @@port     AS port,
      @@socket   AS socket,
      @@version  AS version
  ")->fetch(PDO::FETCH_ASSOC);

  echo "=== CONNEXION ===\n";
  print_r($info);

  echo "\n=== DERNIERS USERS ===\n";
  foreach ($pdo->query("SELECT id,email,LEFT(password_hash,7) hash7 FROM users ORDER BY id DESC LIMIT 10") as $row) {
    printf("#%s  %s  %s...\n", $row['id'], $row['email'], $row['hash7']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "ERR: ".$e->getMessage();
}