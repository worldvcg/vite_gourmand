<?php
require_once __DIR__ . '/../config/db.php';

class MenuModel {

    public static function findAll(array $filters = []): array {
        $db = DB::getConnection();
        $sql = "SELECT * FROM menus WHERE 1=1";
        $params = [];

        if (!empty($filters['theme'])) { $sql .= " AND theme = :theme"; $params[':theme'] = $filters['theme']; }
        if (!empty($filters['regime'])) { $sql .= " AND regime = :regime"; $params[':regime'] = $filters['regime']; }
        if (!empty($filters['price_min'])) { $sql .= " AND base_price >= :price_min"; $params[':price_min'] = $filters['price_min']; }
        if (!empty($filters['price_max'])) { $sql .= " AND base_price <= :price_max"; $params[':price_max'] = $filters['price_max']; }
        if (!empty($filters['min_people'])) { $sql .= " AND min_people >= :min_people"; $params[':min_people'] = $filters['min_people']; }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findOne(int $id): ?array {
        $db = DB::getConnection();
        $stmt = $db->prepare("SELECT * FROM menus WHERE id = ?");
        $stmt->execute([$id]);
        $menu = $stmt->fetch();
        if (!$menu) return null;

        $stmt = $db->prepare("SELECT * FROM dishes WHERE menu_id = ?");
        $stmt->execute([$id]);
        $menu['dishes'] = $stmt->fetchAll();
        return $menu;
    }

    public static function create(array $data): ?int {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            INSERT INTO menus (title, description, theme, regime, min_people, base_price, image)
            VALUES (:title, :description, :theme, :regime, :min_people, :base_price, :image)
        ");
        $ok = $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':theme' => $data['theme'],
            ':regime' => $data['regime'],
            ':min_people' => $data['min_people'],
            ':base_price' => $data['base_price'],
            ':image' => $data['image'],
        ]);
        if (!$ok) return null;
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            UPDATE menus SET
                title = :title,
                description = :description,
                theme = :theme,
                regime = :regime,
                min_people = :min_people,
                base_price = :base_price,
                image = :image
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':theme' => $data['theme'],
            ':regime' => $data['regime'],
            ':min_people' => $data['min_people'],
            ':base_price' => $data['base_price'],
            ':image' => $data['image'],
        ]);
    }

    public static function delete(int $id): bool {
        $db = DB::getConnection();
        $stmt = $db->prepare("DELETE FROM dishes WHERE menu_id = ?");
        $stmt->execute([$id]);
        $stmt = $db->prepare("DELETE FROM menus WHERE id = ?");
        return $stmt->execute([$id]);
    }
}