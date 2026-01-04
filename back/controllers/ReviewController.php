<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class ReviewController{

  // ✅ Créer un avis (client)
// POST /api/reviews
public static function create()
{
  header('Content-Type: application/json; charset=utf-8');

  try {
    $pdo = pdo();

    // Auth obligatoire
    $token = AuthController::getBearerToken();
    if (!$token) {
      http_response_code(401);
      echo json_encode(['error' => 'Token manquant'], JSON_UNESCAPED_UNICODE);
      return;
    }

    // user_id via token
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

    // Payload
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !is_array($data)) {
      http_response_code(400);
      echo json_encode(['error' => 'Payload manquant'], JSON_UNESCAPED_UNICODE);
      return;
    }

    $orderId = (int)($data['order_id'] ?? 0);
    $rating  = (int)($data['rating'] ?? 0);
    $comment = trim((string)($data['comment'] ?? ''));

    if ($orderId <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'order_id manquant'], JSON_UNESCAPED_UNICODE);
      return;
    }

    if ($rating < 1 || $rating > 5) {
      http_response_code(400);
      echo json_encode(['error' => 'Note invalide (1 à 5)'], JSON_UNESCAPED_UNICODE);
      return;
    }

    // 1) Vérifier que la commande appartient au user + terminée + récupérer menu_id
    $stmt = $pdo->prepare("
      SELECT id, user_id, menu_id, status
      FROM orders
      WHERE id = ?
      LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || (int)$order['user_id'] !== $userId) {
      http_response_code(403);
      echo json_encode(['error' => 'Commande invalide'], JSON_UNESCAPED_UNICODE);
      return;
    }

    if ($order['status'] !== 'terminee') {
      http_response_code(400);
      echo json_encode(['error' => 'Vous ne pouvez noter qu’une commande terminée'], JSON_UNESCAPED_UNICODE);
      return;
    }

    $menuId = (int)$order['menu_id'];

    // 2) Empêcher double avis (si tu as mis UNIQUE(order_id), ça protège aussi)
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
      http_response_code(400);
      echo json_encode(['error' => 'Avis déjà envoyé pour cette commande'], JSON_UNESCAPED_UNICODE);
      return;
    }

    // 3) Insert (status = pending)
    $stmt = $pdo->prepare("
      INSERT INTO reviews (order_id, user_id, menu_id, rating, comment, status)
      VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$orderId, $userId, $menuId, $rating, $comment]);

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
      'error' => 'create review failed',
      'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
  }
}

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