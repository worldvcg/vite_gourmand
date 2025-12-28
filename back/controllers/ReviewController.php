<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class ReviewController
{
  // ✅ Liste des avis (employé/admin)
  // GET /api/reviews?status=pending
  public static function list()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $status = $_GET['status'] ?? '';

      $sql = "
        SELECT
          r.id,
          r.rating,
          r.comment,
          r.status,
          r.created_at,
          u.email AS user_email,
          m.title AS menu_title
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        JOIN menus m ON m.id = r.menu_id
        WHERE 1=1
      ";

      $params = [];

      if ($status !== '') {
        $sql .= " AND r.status = ?";
        $params[] = $status;
      }

      $sql .= " ORDER BY r.created_at DESC";

      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);

      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'list reviews failed',
        'detail' => $e->getMessage()
      ]);
    }
  }

  // ✅ Modération d’un avis
  // POST /api/reviews/{id}/moderate
  public static function moderate(int $id)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $data = json_decode(file_get_contents('php://input'), true);
      if (!$data || empty($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Status manquant']);
        return;
      }

      $status = $data['status'];
      $reason = trim((string)($data['moderation_reason'] ?? ''));

      if (!in_array($status, ['approved', 'rejected'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Statut invalide']);
        return;
      }

      if ($status === 'rejected' && mb_strlen($reason) < 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Motif requis (min 5 caractères)']);
        return;
      }

      $stmt = $pdo->prepare("
        UPDATE reviews
        SET status = ?,
            moderation_reason = ?,
            reviewed_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$status, $reason, $id]);

      echo json_encode(['success' => true]);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'moderation failed',
        'detail' => $e->getMessage()
      ]);
    }
  }
}