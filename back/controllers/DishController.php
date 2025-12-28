<?php
require_once __DIR__ . '/../config/db.php';

class DishController
{
  public static function list()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();
      $stmt = $pdo->query("SELECT id, name, type, description FROM dishes ORDER BY id DESC");
      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'DishController::list failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  public static function create()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();
      $data = json_decode(file_get_contents('php://input'), true);

      $name = trim((string)($data['name'] ?? ''));
      $type = trim((string)($data['type'] ?? ''));
      $description = trim((string)($data['description'] ?? ''));

      if ($name === '' || $type === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Champs manquants (name/type)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $allowed = ['entree', 'plat', 'dessert'];
      if (!in_array($type, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type invalide (entree/plat/dessert)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $stmt = $pdo->prepare("INSERT INTO dishes (name, type, description) VALUES (?, ?, ?)");
      $stmt->execute([$name, $type, $description !== '' ? $description : null]);

      echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'DishController::create failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  public static function update(int $id)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();
      $data = json_decode(file_get_contents('php://input'), true);

      $name = trim((string)($data['name'] ?? ''));
      $type = trim((string)($data['type'] ?? ''));
      $description = trim((string)($data['description'] ?? ''));

      if ($name === '' || $type === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Champs manquants (name/type)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $allowed = ['entree', 'plat', 'dessert'];
      if (!in_array($type, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type invalide (entree/plat/dessert)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $stmt = $pdo->prepare("UPDATE dishes SET name = ?, type = ?, description = ? WHERE id = ?");
      $stmt->execute([$name, $type, $description !== '' ? $description : null, $id]);

      echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'DishController::update failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  public static function delete(int $id)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();
      $stmt = $pdo->prepare("DELETE FROM dishes WHERE id = ?");
      $stmt->execute([$id]);

      echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'DishController::delete failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }
}