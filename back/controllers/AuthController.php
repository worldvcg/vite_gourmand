<?php
require_once __DIR__ . '/../config/db.php';

class AuthController {
    public static function register() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Champs manquants']);
            return;
        }

        $pdo = pdo();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email déjà utilisé']);
            return;
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['email'], $hash, $data['first_name'] ?? '', $data['last_name'] ?? '']);
        echo json_encode(['success' => true]);
    }

    public static function login() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Champs manquants']);
            return;
        }

        $pdo = pdo();
        $stmt = $pdo->prepare("SELECT id, email, password_hash, first_name, last_name FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Identifiants invalides']);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token) VALUES (?, ?)");
        $stmt->execute([$user['id'], $token]);

        echo json_encode([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ]);
    }

    public static function me() {
        header('Content-Type: application/json');
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Token manquant']);
            return;
        }

        $pdo = pdo();
        $stmt = $pdo->prepare("SELECT u.id, u.email, u.first_name, u.last_name 
                               FROM user_tokens t 
                               JOIN users u ON u.id = t.user_id
                               WHERE t.token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide']);
            return;
        }

        echo json_encode($user);
    }

    public static function logout() {
        header('Content-Type: application/json');
        $token = self::getBearerToken();
        if ($token) {
            $pdo = pdo();
            $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE token = ?");
            $stmt->execute([$token]);
        }
        echo json_encode(['success' => true]);
    }

    private static function getBearerToken() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) return null;
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        return null;
    }
}