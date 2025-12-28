<?php
require_once __DIR__ . '/../config/db.php';

class OpeningHoursController
{
  public static function list()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();
      $stmt = $pdo->query("SELECT id, day, open, close FROM opening_hours ORDER BY id ASC");
      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'OpeningHoursController::list failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  public static function update(int $id)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();
      $data = json_decode(file_get_contents('php://input'), true);

      $open  = trim((string)($data['open'] ?? ''));
      $close = trim((string)($data['close'] ?? ''));

      if ($open === '' || $close === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Heures manquantes (open/close)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $stmt = $pdo->prepare("UPDATE opening_hours SET open = ?, close = ? WHERE id = ?");
      $stmt->execute([$open, $close, $id]);

      echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'OpeningHoursController::update failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }
}