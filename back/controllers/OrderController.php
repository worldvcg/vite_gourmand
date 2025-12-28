<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class OrderController
{
  // ✅ Création commande (client connecté)
  public static function create()
  {
    header('Content-Type: application/json; charset=utf-8');

    // 1) Token
    $token = AuthController::getBearerToken();
    if (!$token) {
      http_response_code(401);
      echo json_encode(['error' => 'Token manquant']);
      return;
    }

    // 2) Trouver user via token
    $pdo = pdo();
    $stmt = $pdo->prepare("
      SELECT u.id
      FROM user_tokens t
      JOIN users u ON u.id = t.user_id
      WHERE t.token = ?
      LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
      http_response_code(401);
      echo json_encode(['error' => 'Token invalide']);
      return;
    }
    $userId = (int)$user['id'];

    // 3) Payload
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
      http_response_code(400);
      echo json_encode(['error' => 'Données manquantes']);
      return;
    }

    $required = ['menu_id', 'persons', 'address', 'city', 'date', 'time'];
    foreach ($required as $field) {
      if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
        http_response_code(400);
        echo json_encode(['error' => "Champ manquant : $field"]);
        return;
      }
    }

    $menuId  = (int)$data['menu_id'];
    $persons = (int)$data['persons'];

    // 4) Charger menu (✅ bons champs DB)
    $stmt = $pdo->prepare("SELECT id, title, min_people, base_price FROM menus WHERE id = ? LIMIT 1");
    $stmt->execute([$menuId]);
    $menu = $stmt->fetch();

    if (!$menu) {
      http_response_code(404);
      echo json_encode(['error' => 'Menu introuvable']);
      return;
    }

    $minPeople  = (int)$menu['min_people'];
    $unitPrice  = (float)$menu['base_price'];

    if ($persons < $minPeople) {
      http_response_code(400);
      echo json_encode(['error' => "Minimum {$minPeople} personnes requises"]);
      return;
    }

    // 5) Recalcul serveur (sécurité)
    $base = $unitPrice * $persons;

    // Réduction -10% si persons >= min_people + 5 (comme ton front)
    if ($persons >= ($minPeople + 5)) {
      $base *= 0.90;
    }

    // Livraison +5 si ville != Bordeaux
    $delivery = 0.0;
    $city = strtolower(trim((string)$data['city']));
    if ($city !== 'bordeaux') {
      $delivery = 5.0;
    }

    $total = $base + $delivery;

    // 6) Insert (✅ colonnes existantes : persons/address/city/date/time/total)
    $stmt = $pdo->prepare("
      INSERT INTO orders (user_id, menu_id, persons, address, city, date, time, total)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
      $userId,
      $menuId,
      $persons,
      trim((string)$data['address']),
      trim((string)$data['city']),
      $data['date'],
      $data['time'],
      $total
    ]);

    echo json_encode([
      'success' => true,
      'message' => 'Commande enregistrée',
      'order_total' => $total
    ], JSON_UNESCAPED_UNICODE);
  }

  // ✅ Commandes du client connecté
  public static function getMyOrders()
  {
    header('Content-Type: application/json; charset=utf-8');

    $token = AuthController::getBearerToken();
    if (!$token) {
      echo json_encode([]);
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
    $user = $stmt->fetch();

    if (!$user) {
      echo json_encode([]);
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
  }

  // ✅ Toutes les commandes (employé/admin) + filtres
  // Filtre: ?status=... (si tu ajoutes la colonne status plus tard)
  // Filtre: ?email=... (via users.email)
public static function listAll()
{
  header('Content-Type: application/json; charset=utf-8');

  try {
    $pdo = pdo();

    $status = $_GET['status'] ?? ''; // pas utilisé tant que la colonne n'existe pas
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
    o.status,
    m.title AS menu_title
  FROM orders o
  LEFT JOIN menus m ON m.id = o.menu_id
  WHERE 1=1
";

    $params = [];

    if ($email !== '') {
      $sql .= " AND u.email LIKE ?";
      $params[] = "%$email%";
    }

    // ✅ À activer seulement quand tu ajoutes la colonne `status` dans orders
    // if ($status !== '') {
    //   $sql .= " AND o.status = ?";
    //   $params[] = $status;
    // }

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
} 