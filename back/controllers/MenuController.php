<?php
// back/controllers/MenuController.php
require_once __DIR__ . '/../config/db.php';

class MenuController {

    // =========================
    // GET /api/menus
    // =========================
    public static function list() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = pdo();

            $stmt = $pdo->query("
                SELECT
                    id,
                    title,
                    description,
                    theme,
                    regime,
                    min_people,
                    base_price,
                    stock_available,
                    conditions_text,
                    image
                FROM menus
                ORDER BY id DESC
            ");

            $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($menus, JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur récupération menus',
                'detail' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================
    // GET /api/menus/{id}
    // =========================
    public static function detail(int $id) {
  header('Content-Type: application/json; charset=utf-8');

  try {
    $pdo = pdo();

    // 1) Menu
    $stmt = $pdo->prepare("
      SELECT
        id, title, description, theme, regime, min_people,
        base_price, stock_available, conditions_text, image
      FROM menus
      WHERE id = ?
      LIMIT 1
    ");
    $stmt->execute([$id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
      http_response_code(404);
      echo json_encode(['error' => 'Menu introuvable'], JSON_UNESCAPED_UNICODE);
      return;
    }

    // 2) Dishes liés au menu (via menu_dishes)
    $stmt = $pdo->prepare("
      SELECT
        d.id,
        d.name,
        d.type,
        d.description
      FROM menu_dishes md
      JOIN dishes d ON d.id = md.dish_id
      WHERE md.menu_id = ?
      ORDER BY FIELD(d.type, 'entrée', 'plat', 'dessert'), d.name
    ");
    $stmt->execute([$id]);
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Retour complet
    $menu['dishes'] = $dishes;

    echo json_encode($menu, JSON_UNESCAPED_UNICODE);

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
      'error' => 'Erreur récupération menu',
      'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
  }
}

    // =========================
    // POST /api/menus
    // =========================
    public static function create() {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $pdo = pdo();

            $stmt = $pdo->prepare("
                INSERT INTO menus
                (title, description, theme, regime, min_people, base_price, stock_available, conditions_text, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim((string)($data['title'] ?? '')),
                $data['description'] ?? null,
                $data['theme'] ?? 'Classique',
                $data['regime'] ?? 'classique',
                (int)($data['min_people'] ?? 4),
                (float)($data['base_price'] ?? 0),
                (int)($data['stock_available'] ?? 0),
                $data['conditions_text'] ?? null,
                $data['image'] ?? null
            ]);

            echo json_encode([
                'success' => true,
                'id' => (int)$pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur création menu',
                'detail' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================
    // PUT /api/menus/{id}
    // =========================
    public static function update(int $id) {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $pdo = pdo();

            $stmt = $pdo->prepare("
                UPDATE menus SET
                    title = ?,
                    description = ?,
                    theme = ?,
                    regime = ?,
                    min_people = ?,
                    base_price = ?,
                    stock_available = ?,
                    conditions_text = ?,
                    image = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim((string)($data['title'] ?? '')),
                $data['description'] ?? null,
                $data['theme'] ?? 'Classique',
                $data['regime'] ?? 'classique',
                (int)($data['min_people'] ?? 4),
                (float)($data['base_price'] ?? 0),
                (int)($data['stock_available'] ?? 0),
                $data['conditions_text'] ?? null,
                $data['image'] ?? null,
                $id
            ]);

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur mise à jour menu',
                'detail' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================
    // DELETE /api/menus/{id}
    // =========================
    public static function delete(int $id) {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = pdo();
            $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur suppression menu',
                'detail' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}