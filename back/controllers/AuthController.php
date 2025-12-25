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
        $stmt = $pdo->prepare("SELECT id, email, password_hash, first_name, last_name, role FROM users WHERE email = ?");
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
                'last_name' => $user['last_name'],
                'role' => $user['role']
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
        $stmt = $pdo->prepare("SELECT u.id, u.email, u.first_name, u.last_name, u.role 
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

public static function getBearerToken() {
    // 1) Essayer via variables serveur courantes
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$auth) $auth = $_SERVER['Authorization'] ?? null;
    if (!$auth) $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

    // 2) Polyfill getallheaders() si dispo
    if (!$auth) {
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (strpos($name, 'HTTP_') === 0) {
                        $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $headers[$key] = $value;
                    }
                }
                return $headers;
            }
        }
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
        }
    }

    // 3) Bearer <token>
    if ($auth && preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
        return $m[1];
    }

    // 4) Debug : ?token=...
    if (!empty($_GET['token'])) {
        return $_GET['token'];
    }

    return null;
}
}
