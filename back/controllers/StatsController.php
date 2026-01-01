<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class StatsController
{
  private static function requireAdmin(): array
  {
    header('Content-Type: application/json; charset=utf-8');

    $token = AuthController::getBearerToken();
    if (!$token) {
      http_response_code(401);
      echo json_encode(['error' => 'Token manquant'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $pdo = pdo();
    $stmt = $pdo->prepare("
      SELECT u.id, u.role
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
      exit;
    }

    if (($user['role'] ?? '') !== 'admin') {
      http_response_code(403);
      echo json_encode(['error' => 'Accès admin requis'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    return $user;
  }

  // -----------------------
  // NoSQL helpers (JSON file)
  // -----------------------
  private static function readNoSqlOrders(): array
  {
    $file = __DIR__ . '/../nosql/orders.json';
    if (!file_exists($file)) return [];

    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  // Normalise created_at:
  // - "2026-01-01 20:10:00" => "2026-01-01"
  // - "2026-01-01" => "2026-01-01"
  private static function normalizeDate(?string $date): ?string
  {
    if (!$date) return null;
    $date = trim($date);
    if (strlen($date) >= 10) return substr($date, 0, 10);
    return null;
  }

  private static function inRange(?string $date, ?string $from, ?string $to): bool
  {
    $date = self::normalizeDate($date);
    if (!$date) return false;

    $from = $from ? self::normalizeDate($from) : null;
    $to   = $to   ? self::normalizeDate($to)   : null;

    if ($from && $date < $from) return false;
    if ($to && $date > $to) return false;
    return true;
  }

  // ✅ Commandes par menu (NoSQL)
  // GET ?route=/api/admin/stats/orders-per-menu&menu_id=1&from=2025-12-01&to=2025-12-31
  public static function ordersPerMenu()
  {
    self::requireAdmin();

    $menuId = isset($_GET['menu_id']) ? trim((string)$_GET['menu_id']) : '';
    $from   = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
    $to     = isset($_GET['to']) ? trim((string)$_GET['to']) : '';

    $orders = self::readNoSqlOrders();
    $counts = []; // menu_id => ['label'=>..., 'value'=>...]

    foreach ($orders as $o) {
      $oidMenu = (string)($o['menu_id'] ?? '');
      $date    = $o['created_at'] ?? null;

      if ($oidMenu === '') continue;
      if ($menuId !== '' && $oidMenu !== $menuId) continue;
      if (($from || $to) && !self::inRange($date, $from ?: null, $to ?: null)) continue;

      $label = (string)($o['menu_label'] ?? ('Menu #' . $oidMenu));

      if (!isset($counts[$oidMenu])) {
        $counts[$oidMenu] = ['label' => $label, 'value' => 0];
      }
      $counts[$oidMenu]['value']++;
    }

    echo json_encode(array_values($counts), JSON_UNESCAPED_UNICODE);
  }

  // ✅ CA par menu (NoSQL)
  // GET ?route=/api/admin/stats/revenue-per-menu&menu_id=1&from=2025-12-01&to=2025-12-31
  public static function revenuePerMenu()
  {
    self::requireAdmin();

    $menuId = isset($_GET['menu_id']) ? trim((string)$_GET['menu_id']) : '';
    $from   = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
    $to     = isset($_GET['to']) ? trim((string)$_GET['to']) : '';

    $orders = self::readNoSqlOrders();

    $byMenu = []; // menu_id => ['label'=>..., 'value'=>...]
    $total  = 0.0;

    foreach ($orders as $o) {
      $oidMenu = (string)($o['menu_id'] ?? '');
      $date    = $o['created_at'] ?? null;

      if ($oidMenu === '') continue;
      if ($menuId !== '' && $oidMenu !== $menuId) continue;
      if (($from || $to) && !self::inRange($date, $from ?: null, $to ?: null)) continue;

      $label  = (string)($o['menu_label'] ?? ('Menu #' . $oidMenu));
      $amount = (float)($o['total'] ?? 0);

      $total += $amount;

      if (!isset($byMenu[$oidMenu])) {
        $byMenu[$oidMenu] = ['label' => $label, 'value' => 0.0];
      }
      $byMenu[$oidMenu]['value'] += $amount;
    }

    echo json_encode([
      'total' => round($total, 2),
      'by_menu' => array_values($byMenu)
    ], JSON_UNESCAPED_UNICODE);
  }
}