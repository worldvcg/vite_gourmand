<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class UserController
{
  public static function updateMe()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      // Token obligatoire
      $token = AuthController::getBearerToken();
      if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Token manquant'], JSON_UNESCAPED_UNICODE);
        return;
      }

      // Trouver user_id via token
      $stmt = $pdo->prepare("
        SELECT u.id
        FROM user_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token = ?
        LIMIT 1
      ");
      $stmt->execute([$token]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalide'], JSON_UNESCAPED_UNICODE);
        return;
      }
      $userId = (int)$user['id'];

      // Lire JSON
      $data = json_decode(file_get_contents('php://input'), true);
      if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON invalide'], JSON_UNESCAPED_UNICODE);
        return;
      }

      // Champs modifiables
      $first  = trim((string)($data['first_name'] ?? ''));
      $last   = trim((string)($data['last_name'] ?? ''));
      $phone  = trim((string)($data['phone'] ?? ''));
      $address= trim((string)($data['address'] ?? ''));
      $city   = trim((string)($data['city'] ?? ''));

      // (optionnel) petite validation
      if ($first === '' || $last === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Prénom/Nom requis'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $stmt = $pdo->prepare("
        UPDATE users
        SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, updated_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$first, $last, $phone, $address, $city, $userId]);

      echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'updateMe failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }
}