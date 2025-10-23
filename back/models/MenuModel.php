<?php
require_once __DIR__ . '/../config/db.php';

class MenuModel {
  public static function findAll(array $filters = []): array {
    $pdo = pdo();
    $sql = "SELECT id, title, description, theme, regime, min_people, base_price, stock_available
            FROM menus WHERE 1=1";
    $params = [];

    if (!empty($filters['theme'])) {
      $sql .= " AND theme = :theme";
      $params[':theme'] = $filters['theme'];
    }
    if (!empty($filters['regime'])) {
      $sql .= " AND regime = :regime";
      $params[':regime'] = $filters['regime'];
    }
    if (!empty($filters['price_max'])) {
      $sql .= " AND base_price <= :pmax";
      $params[':pmax'] = (float)$filters['price_max'];
    }
    if (!empty($filters['price_min'])) {
      $sql .= " AND base_price >= :pmin";
      $params[':pmin'] = (float)$filters['price_min'];
    }
    if (!empty($filters['min_people'])) {
      $sql .= " AND min_people >= :mp";
      $params[':mp'] = (int)$filters['min_people'];
    }

    $sql .= " ORDER BY created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
  }

  public static function findOne(int $id): ?array {
    $pdo = pdo();
    // menu + ses plats + allergènes
    $menu = $pdo->prepare("SELECT * FROM menus WHERE id = :id");
    $menu->execute([':id' => $id]);
    $m = $menu->fetch();
    if (!$m) return null;

    $qDishes = $pdo->prepare("
      SELECT d.id, d.name, d.type, d.description,
             GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR ', ') AS allergens
      FROM menu_dishes md
      JOIN dishes d ON d.id = md.dish_id
      LEFT JOIN dish_allergens da ON da.dish_id = d.id
      LEFT JOIN allergens a ON a.id = da.allergen_id
      WHERE md.menu_id = :id
      GROUP BY d.id
      ORDER BY FIELD(d.type,'entrée','plat','dessert'), d.name
    ");
    $qDishes->execute([':id' => $id]);
    $m['dishes'] = $qDishes->fetchAll();

    return $m;
  }
}