<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

class AdminController {

    public static function requireAdmin() {
        header('Content-Type: application/json');

        $token = AuthController::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Token manquant']);
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
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide']);
            exit;
        }

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Accès admin requis']);
            exit;
        }

        return $user;
    }

    public static function listEmployees() {
        self::requireAdmin();
        header('Content-Type: application/json');

        $pdo = pdo();
        $stmt = $pdo->query("
            SELECT id, email, first_name, last_name, role, is_active, created_at
            FROM users
            WHERE role = 'employe'
            ORDER BY created_at DESC
        ");
        echo json_encode($stmt->fetchAll());
    }

    public static function createEmployee() {
        self::requireAdmin();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email et mot de passe requis']);
            return;
        }

        $email = trim(strtolower($data['email']));
        $password = (string)$data['password'];

        // sécurité : interdiction de créer un admin via l'app
        $role = 'employe';

        $pdo = pdo();

        // email unique
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email déjà utilisé']);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, first_name, last_name, role, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $email,
            $hash,
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $role
        ]);

        // (Optionnel) envoi email - en local ça dépend de ta config MAMP
        // mail($email, "Compte employé créé", "Un compte employé a été créé pour vous. Contactez l'admin pour le mot de passe.");

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

    public static function setEmployeeActive(int $id) {
        self::requireAdmin();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : null;

        if ($is_active === null || ($is_active !== 0 && $is_active !== 1)) {
            http_response_code(400);
            echo json_encode(['error' => 'is_active doit valoir 0 ou 1']);
            return;
        }

        $pdo = pdo();

        // on ne touche qu'aux employés
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'employe'");
        $stmt->execute([$is_active, $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Employé introuvable']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}