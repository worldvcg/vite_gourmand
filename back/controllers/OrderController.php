<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class OrderController {

    public static function create() {
        header('Content-Type: application/json');

        // 1️⃣ Vérification token
        $token = AuthController::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Token manquant']);
            return;
        }

        // Récupérer l'utilisateur lié au token
        $pdo = pdo();
        $stmt = $pdo->prepare("
            SELECT u.id FROM user_tokens t 
            JOIN users u ON u.id = t.user_id
            WHERE t.token = ?
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide']);
            return;
        }

        $userId = $user['id'];

        // 2️⃣ Récupération des données
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            return;
        }

        $required = ['menu_id', 'persons', 'address', 'city', 'date', 'time', 'total'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Champ manquant : $field"]);
                return;
            }
        }

        // 3️⃣ Vérification du menu (prix / minPersonnes)
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ?");
        $stmt->execute([$data['menu_id']]);
        $menu = $stmt->fetch();

        if (!$menu) {
            http_response_code(404);
            echo json_encode(['error' => 'Menu introuvable']);
            return;
        }

        // 4️⃣ Validation du nombre de personnes
        if ($data['persons'] < $menu['minPersonnes']) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre de personnes insuffisant']);
            return;
        }

        // 5️⃣ Recalcul serveur (sécurité)
        $persons = intval($data['persons']);
        $price = floatval($menu['prixBase']);

        $total = $persons * $price;

        if ($persons >= $menu['minPersonnes'] + 5) {
            $total *= 0.90; // réduction 10%
        }

        if (strtolower($data['city']) !== "bordeaux") {
            $total += 5; // livraison BASIQUE (tu pourras ajouter les km après)
        }

        // 6️⃣ Enregistrer la commande
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, menu_id, persons, address, city, date, time, total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['menu_id'],
            $persons,
            $data['address'],
            $data['city'],
            $data['date'],
            $data['time'],
            $total
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Commande enregistrée",
            "order_total" => $total
        ]);
    }

    public static function getMyOrders() {
    header('Content-Type: application/json');

    // Récupération du token
    $token = AuthController::getBearerToken();
    if (!$token) {
        echo json_encode([]); 
        return;
    }

    $pdo = pdo();

    // Trouver l'utilisateur via le token
    $stmt = $pdo->prepare("
        SELECT u.id 
        FROM user_tokens t
        JOIN users u ON u.id = t.user_id
        WHERE t.token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([]);
        return;
    }

    $userId = (int)$user['id'];

    // Récupérer ses commandes
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
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders, JSON_UNESCAPED_UNICODE);
}

}