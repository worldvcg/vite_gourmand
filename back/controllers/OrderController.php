<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class OrderController
{
  // ==========================
  // Helpers historique
  // ==========================
  private static function pushStatusHistory(PDO $pdo, int $orderId, string $status, ?string $note = null): void
  {
    $stmt = $pdo->prepare("
      INSERT INTO order_status_history (order_id, status, note)
      VALUES (?, ?, ?)
    ");
    $stmt->execute([$orderId, $status, $note]);
  }

  private static function getHistory(PDO $pdo, int $orderId): array
  {
    $stmt = $pdo->prepare("
      SELECT status, changed_at, note
      FROM order_status_history
      WHERE order_id = ?
      ORDER BY changed_at ASC, id ASC
    ");
    $stmt->execute([$orderId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
  }

  // ==========================
  // ✅ Création commande
  // ==========================
  public static function create()
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

      // Lire le JSON
      $data = json_decode(file_get_contents('php://input'), true);
      if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes'], JSON_UNESCAPED_UNICODE);
        return;
      }

      // Champs requis
      $required = [
        'menu_id', 'fullname', 'email', 'phone', 'address',
        'prestation_date', 'prestation_time', 'guests'
      ];
      foreach ($required as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
          http_response_code(400);
          echo json_encode(['error' => "Champ manquant : $field"], JSON_UNESCAPED_UNICODE);
          return;
        }
      }

      $menuId = (int)$data['menu_id'];
      $guests = (int)$data['guests'];

      // Charger menu (avec stock_available)
      $stmt = $pdo->prepare("
        SELECT id, title, min_people, base_price, stock_available
        FROM menus
        WHERE id = ?
        LIMIT 1
      ");
      $stmt->execute([$menuId]);
      $menu = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$menu) {
        http_response_code(404);
        echo json_encode(['error' => 'Menu introuvable'], JSON_UNESCAPED_UNICODE);
        return;
      }

      // Stock
      $stock = (int)($menu['stock_available'] ?? 0);
      if ($stock <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Stock épuisé pour ce menu'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $minPeople = (int)$menu['min_people'];
      $unitPrice = (float)$menu['base_price'];

      if ($guests < $minPeople) {
        http_response_code(400);
        echo json_encode(['error' => "Minimum {$minPeople} personnes requises"], JSON_UNESCAPED_UNICODE);
        return;
      }

      // ---------------------------
      // Calcul prix (serveur)
      // ---------------------------
      $basePrice = $unitPrice * $guests;

      // -10% si guests >= min_people + 5
      if ($guests >= ($minPeople + 5)) {
        $basePrice *= 0.90;
      }

      // Livraison : 5€ + 0.59€/km si ville != Bordeaux
      $deliveryCost = 0.0;
      $city = isset($data['city']) ? strtolower(trim((string)$data['city'])) : '';
      $km   = isset($data['distance_km']) ? (float)$data['distance_km'] : 0.0;

      if ($city !== '' && $city !== 'bordeaux') {
        if ($km < 0) $km = 0;
        $deliveryCost = 5.0 + (0.59 * $km);
      }

      $totalPrice = $basePrice + $deliveryCost;

      // Transaction
      $pdo->beginTransaction();

      // Insert order (statut = attente)
      $stmt = $pdo->prepare("
        INSERT INTO orders
          (user_id, menu_id, fullname, email, phone, address, prestation_date, prestation_time, guests,
           base_price, delivery_cost, total_price, status)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");

      $stmt->execute([
        $userId,
        $menuId,
        trim((string)$data['fullname']),
        trim((string)$data['email']),
        trim((string)$data['phone']),
        trim((string)$data['address']),
        $data['prestation_date'],
        $data['prestation_time'],
        $guests,
        round((float)$basePrice, 2),
        round((float)$deliveryCost, 2),
        round((float)$totalPrice, 2),
        'attente'
      ]);

      $orderId = (int)$pdo->lastInsertId();

      // ✅ Historique : création
      self::pushStatusHistory($pdo, $orderId, 'attente', 'Commande créée');

      // Décrémenter stock
      $stmt = $pdo->prepare("
        UPDATE menus
        SET stock_available = stock_available - 1
        WHERE id = ? AND stock_available > 0
      ");
      $stmt->execute([$menuId]);

      if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Stock épuisé (conflit). Réessayez.'], JSON_UNESCAPED_UNICODE);
        return;
      }

      // NoSQL
      self::appendNoSqlOrder([
        'order_id'      => $orderId,
        'user_id'       => $userId,
        'menu_id'       => $menuId,
        'menu_label'    => (string)($menu['title'] ?? ("Menu #".$menuId)),
        'total'         => round((float)$totalPrice, 2),
        'delivery_cost' => round((float)$deliveryCost, 2),
        'distance_km'   => round((float)$km, 2),
        'status'        => 'attente',
        'created_at'    => date('Y-m-d')
      ]);

      $pdo->commit();

      echo json_encode([
        'success' => true,
        'message' => 'Commande enregistrée',
        'order_id' => $orderId,
        'total_price' => round((float)$totalPrice, 2),
        'delivery_cost' => round((float)$deliveryCost, 2)
      ], JSON_UNESCAPED_UNICODE);
      return;

    } catch (Throwable $e) {
      try {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
          $pdo->rollBack();
        }
      } catch (Throwable $ignored) {}

      http_response_code(500);
      echo json_encode([
        'error' => 'create failed',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      return;
    }
  }

  // ==========================
  // ✅ Commandes du client
  // ==========================
  public static function getMyOrders()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $token = AuthController::getBearerToken();
      if (!$token) { echo json_encode([], JSON_UNESCAPED_UNICODE); return; }

      $pdo = pdo();

      $stmt = $pdo->prepare("
        SELECT u.id
        FROM user_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token = ?
        LIMIT 1
      ");
      $stmt->execute([$token]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user) { echo json_encode([], JSON_UNESCAPED_UNICODE); return; }

      $userId = (int)$user['id'];

      $stmt = $pdo->prepare("
        SELECT o.*, m.title AS menu_name
        FROM orders o
        LEFT JOIN menus m ON m.id = o.menu_id
        WHERE o.user_id = ?
        ORDER BY o.id DESC
      ");
      $stmt->execute([$userId]);

      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'getMyOrders failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  // ==========================
  // ✅ Suivi: order + history
  // ==========================
  public static function getStatus(int $orderId)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $stmt = $pdo->prepare("
  SELECT
    id,
    menu_id,
    guests,
    status,
    prestation_date,
    prestation_time,
    updated_at
    FROM orders
    WHERE id = ?
    LIMIT 1
  ");
      $stmt->execute([$orderId]);

      $order = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Commande introuvable'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $history = self::getHistory($pdo, $orderId);

      echo json_encode([
        'order' => $order,
        'history' => $history
      ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'getStatus failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  // ==========================
  // ✅ Annuler (et historique)
  // ==========================
  public static function cancel(int $orderId)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $data = json_decode(file_get_contents('php://input'), true);
      if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Payload manquant'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $reason = trim((string)($data['cancel_reason'] ?? ''));
      $mode   = strtolower(trim((string)($data['cancel_contact_mode'] ?? '')));

      if ($reason === '' || mb_strlen($reason) < 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Motif manquant ou trop court'], JSON_UNESCAPED_UNICODE);
        return;
      }

      if (!in_array($mode, ['gsm', 'mail'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Mode de contact invalide (gsm/mail)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1");
      $stmt->execute([$orderId]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Commande introuvable'], JSON_UNESCAPED_UNICODE);
        return;
      }

      if (in_array($row['status'], ['annulee', 'terminee'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Commande déjà annulée ou terminée'], JSON_UNESCAPED_UNICODE);
        return;
      }

      // ✅ Transaction (update + history)
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'annulee',
            cancel_reason = ?,
            cancel_contact_mode = ?,
            canceled_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$reason, $mode, $orderId]);

      self::pushStatusHistory($pdo, $orderId, 'annulee', 'Annulation: ' . $reason);

      $pdo->commit();

      echo json_encode(['success' => true, 'id' => $orderId], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      try { if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $ignored) {}
      http_response_code(500);
      echo json_encode(['error' => 'cancel failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  // ==========================
  // ✅ Update statut (et history)
  // ==========================
  public static function updateStatus(int $orderId)
{
  header('Content-Type: application/json; charset=utf-8');

  try {
    $pdo = pdo();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['status'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Status manquant'], JSON_UNESCAPED_UNICODE);
      return;
    }

    $allowed = ['attente','accepte','en_preparation','en_livraison','livre','attente_retour_materiel','terminee','annulee'];
    $status = trim((string)$data['status']);

    if (!in_array($status, $allowed, true)) {
      http_response_code(400);
      echo json_encode(['error' => 'Status invalide'], JSON_UNESCAPED_UNICODE);
      return;
    }

    // ✅ récupérer l'ancien status (pour éviter d'écrire 2x la même chose)
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
      http_response_code(404);
      echo json_encode(['error' => 'Commande introuvable'], JSON_UNESCAPED_UNICODE);
      return;
    }

    if ($old['status'] === $status) {
      echo json_encode(['success' => true, 'id' => $orderId, 'status' => $status], JSON_UNESCAPED_UNICODE);
      return;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    // ✅ Historique
    self::pushStatusHistory($pdo, $orderId, $status, 'Changement de statut');

    $pdo->commit();

    echo json_encode(['success' => true, 'id' => $orderId, 'status' => $status], JSON_UNESCAPED_UNICODE);

  } catch (Throwable $e) {
    try { if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack(); } catch(Throwable $ignored) {}

    http_response_code(500);
    echo json_encode(['error' => 'updateStatus failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
  }
}

  // ==========================
  // Liste des commandes (admin/employe)
  // ==========================
  public static function listAll()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $status = $_GET['status'] ?? '';
      $email  = $_GET['email'] ?? '';

      $sql = "
        SELECT
          o.id, o.user_id, o.menu_id, o.fullname, o.email, o.phone, o.address,
          o.prestation_date, o.prestation_time, o.guests,
          o.base_price, o.delivery_cost, o.total_price,
          o.created_at, o.updated_at, o.status,
          o.cancel_reason, o.cancel_contact_mode, o.canceled_at,
          m.title AS menu_title
        FROM orders o
        LEFT JOIN menus m ON m.id = o.menu_id
        WHERE 1=1
      ";

      $params = [];
      if ($email !== '') { $sql .= " AND o.email LIKE ?"; $params[] = "%$email%"; }
      if ($status !== '') { $sql .= " AND o.status = ?"; $params[] = $status; }

      $sql .= " ORDER BY o.id DESC";

      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);

      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'listAll failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
  }

  // ==========================
  // NoSQL helpers
  // ==========================
  private static function appendNoSqlOrder(array $doc): void
  {
    $dir   = __DIR__ . '/../nosql';
    $file  = $dir . '/orders.json';

    if (!is_dir($dir)) {
      if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new Exception("Impossible de créer le dossier: $dir");
      }
    }

    if (!file_exists($file)) {
      file_put_contents($file, "[]", LOCK_EX);
    }

    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') $raw = "[]";

    $data = json_decode($raw, true);
    if (!is_array($data)) $data = [];

    $data[] = $doc;

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $ok = file_put_contents($file, $json, LOCK_EX);

    if ($ok === false) {
      throw new Exception("Impossible d'écrire dans $file (permissions ?)");
    }
  }
}
