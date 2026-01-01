<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class OrderController
{
  // ✅ Création commande (client connecté)
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
      $required = ['menu_id', 'fullname', 'email', 'phone', 'address', 'prestation_date', 'prestation_time', 'guests'];
      foreach ($required as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
          http_response_code(400);
          echo json_encode(['error' => "Champ manquant : $field"], JSON_UNESCAPED_UNICODE);
          return;
        }
      }

      $menuId = (int)$data['menu_id'];
      $guests = (int)$data['guests'];

      // Charger menu (min_people/base_price)
      $stmt = $pdo->prepare("SELECT id, min_people, base_price, title FROM menus WHERE id = ? LIMIT 1");
      $stmt->execute([$menuId]);
      $menu = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$menu) {
        http_response_code(404);
        echo json_encode(['error' => 'Menu introuvable'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $minPeople = (int)$menu['min_people'];
      $unitPrice = (float)$menu['base_price'];

      if ($guests < $minPeople) {
        http_response_code(400);
        echo json_encode(['error' => "Minimum {$minPeople} personnes requises"], JSON_UNESCAPED_UNICODE);
        return;
      }

      // Calculs serveur
      $basePrice = $unitPrice * $guests;

      // -10% si guests >= min_people + 5
      if ($guests >= ($minPeople + 5)) {
        $basePrice *= 0.90;
      }

      // Livraison +5 si city != Bordeaux (si city fournie)
      $deliveryCost = 0.0;
      if (isset($data['city']) && trim((string)$data['city']) !== '') {
        $city = strtolower(trim((string)$data['city']));
        if ($city !== 'bordeaux') $deliveryCost = 5.0;
      }

      $totalPrice = $basePrice + $deliveryCost;

      // ✅ Transaction : SQL + NoSQL ensemble
      $pdo->beginTransaction();

      // Insert SQL
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
        $basePrice,
        $deliveryCost,
        $totalPrice,
        'accepte'
      ]);

      $orderId = (int)$pdo->lastInsertId();

      // Append NoSQL (orders.json)
      self::appendNoSqlOrder([
        'order_id'   => $orderId,
        'user_id'    => $userId,
        'menu_id'    => $menuId,
        'menu_label' => (string)($menu['title'] ?? ("Menu #".$menuId)),
        'total'      => round((float)$totalPrice, 2),
        'status'     => 'accepte',
        'created_at' => date('Y-m-d')
      ]);

      $pdo->commit();

      echo json_encode([
        'success' => true,
        'message' => 'Commande enregistrée',
        'order_id' => $orderId,
        'total_price' => round((float)$totalPrice, 2)
      ], JSON_UNESCAPED_UNICODE);
      return;

    } catch (Throwable $e) {
      // rollback si transaction ouverte
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

  // ✅ Commandes du client connecté
  public static function getMyOrders()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $token = AuthController::getBearerToken();
      if (!$token) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        return;
      }

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

      if (!$user) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        return;
      }

      $userId = (int)$user['id'];

      $stmt = $pdo->prepare("
        SELECT
          o.*,
          m.title AS menu_name
        FROM orders o
        LEFT JOIN menus m ON m.id = o.menu_id
        WHERE o.user_id = ?
        ORDER BY o.id DESC
      ");
      $stmt->execute([$userId]);

      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'getMyOrders failed',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
    }
  }

  // ✅ Suivi client : GET /api/orders/{id}/status
  public static function getStatus(int $orderId)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $stmt = $pdo->prepare("
        SELECT id, status, prestation_date, prestation_time, updated_at
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

      echo json_encode($order, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'getStatus failed',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
    }
  }

  // ✅ Annulation (employé/admin) : POST /api/orders/{id}/cancel
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

      // Vérifier statut actuel
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

      echo json_encode(['success' => true, 'id' => $orderId], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'cancel failed',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
    }
  }

  // ✅ Update statut (employé/admin) : PUT /api/orders/{id}/status
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

      $allowed = ['accepte', 'en_preparation', 'en_livraison', 'livre', 'attente_retour_materiel', 'terminee', 'annulee'];
      $status = trim((string)$data['status']);

      if (!in_array($status, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Status invalide'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
      $stmt->execute([$status, $orderId]);

      echo json_encode(['success' => true, 'id' => $orderId, 'status' => $status], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'updateStatus failed',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
    }
  }

  // ✅ Liste des commandes (employé/admin) + filtres
  public static function listAll()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $pdo = pdo();

      $status = $_GET['status'] ?? '';
      $email  = $_GET['email'] ?? '';

      $sql = "
        SELECT
          o.id,
          o.user_id,
          o.menu_id,
          o.fullname,
          o.email,
          o.phone,
          o.address,
          o.prestation_date,
          o.prestation_time,
          o.guests,
          o.base_price,
          o.delivery_cost,
          o.total_price,
          o.created_at,
          o.updated_at,
          o.status,
          o.cancel_reason,
          o.cancel_contact_mode,
          o.canceled_at,
          m.title AS menu_title
        FROM orders o
        LEFT JOIN menus m ON m.id = o.menu_id
        WHERE 1=1
      ";

      $params = [];

      if ($email !== '') {
        $sql .= " AND o.email LIKE ?";
        $params[] = "%$email%";
      }

      if ($status !== '') {
        $sql .= " AND o.status = ?";
        $params[] = $status;
      }

      $sql .= " ORDER BY o.id DESC";

      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);

      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'listAll failed',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
    }
  }

  // -----------------------
  // NoSQL helpers (JSON file)
  // -----------------------
  private static function appendNoSqlOrder(array $doc): void
{
  $dir  = __DIR__ . '/../nosql';
  $file = $dir . '/orders.json';
  $trace = $dir . '/trace.txt';

  // 1) Créer dossier
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
      throw new Exception("Impossible de créer le dossier: $dir");
    }
  }

  // 2) Créer fichier s'il n'existe pas
  if (!file_exists($file)) {
    file_put_contents($file, "[]", LOCK_EX);
  }

  // Trace (pour vérifier que la fonction est appelée)
  file_put_contents($trace, "append called => $file\n", FILE_APPEND);

  // 3) Lire existant
  $raw = file_get_contents($file);
  if ($raw === false || trim($raw) === '') $raw = "[]";
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = [];

  // 4) Ajouter doc
  $data[] = $doc;

  // 5) Écrire avec verrou
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  $ok = file_put_contents($file, $json, LOCK_EX);

  if ($ok === false) {
    throw new Exception("Impossible d'écrire dans $file (permissions ?)");
  }
 }
}
