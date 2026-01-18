<?php
require_once __DIR__ . '/../config/db.php';

class AuthController {
    public static function register() {
            header('Content-Type: application/json; charset=utf-8');

    $data = json_decode(file_get_contents('php://input'), true);

    // Champs requis selon cahier des charges
    $required = ['email','password','first_name','last_name','phone','address','city'];
    foreach ($required as $f) {
        if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
            http_response_code(400);
            echo json_encode(['error' => "Champ manquant : $f"], JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    $email = strtolower(trim((string)$data['email']));
    $password = (string)$data['password'];
    $first = trim((string)$data['first_name']);
    $last  = trim((string)$data['last_name']);
    $phone = trim((string)$data['phone']);
    $address = trim((string)$data['address']);
    $city = trim((string)$data['city']);

    // Vérif email simple
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email invalide'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Vérif mot de passe (10+ maj/min/chiffre/spécial)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Mot de passe non conforme'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $pdo = pdo();

    // Email déjà pris ?
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email déjà utilisé'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    // role = client (ta DB est enum client/employe/admin)
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password_hash, phone, address, city, role, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'client', 1)
    ");
    $stmt->execute([$first, $last, $email, $hash, $phone, $address, $city]);

    // Mail de bienvenue (souvent non configuré sur MAMP => on ne bloque pas si ça échoue)
    $sent = false;
    try {
        $subject = "Bienvenue chez Vite & Gourmand";
        $message = "Bonjour $first,\n\nBienvenue chez Vite & Gourmand !\nVotre compte a bien été créé.\n\nÀ bientôt,\nJulie & José";
        $headers = "From: contact@vite-gourmand.fr\r\n";
        $sent = @mail($email, $subject, $message, $headers);
    } catch (Throwable $e) {
        $sent = false;
    }

    echo json_encode([
        'success' => true,
        'welcome_mail_sent' => (bool)$sent
    ], JSON_UNESCAPED_UNICODE);
}

private static function baseUrl(): string {
  // Optionnel : si tu définis une variable d'env
  $env = getenv('FRONT_BASE_URL');
  if ($env && trim($env) !== '') return rtrim(trim($env), '/');

  $isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

  $scheme = $isHttps ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '';

  if ($host !== '') return $scheme . '://' . $host . '/vite_gourmand/front';

  // fallback dev
  return 'http://localhost:8888/vite_gourmand/front';
}

private static function frontUrl(string $path): string {
  $base = self::baseUrl();
  $path = '/' . ltrim($path, '/');
  return rtrim($base, '/') . $path;
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
        $stmt = $pdo->prepare("SELECT id, email, password_hash, first_name, last_name, role, is_active FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
             http_response_code(401);
             echo json_encode(['error' => 'Identifiants invalides']);
             return;
        }

        // ✅ Compte désactivé (après avoir vérifié que $user existe)
        if ((int)($user['is_active'] ?? 1) === 0) {
           http_response_code(403);
           echo json_encode(['error' => 'Compte désactivé']);
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

    public static function me()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Token manquant']);
            return;
        }

        $pdo = pdo();

        $stmt = $pdo->prepare("
            SELECT 
              u.id,
              u.email,
              u.first_name,
              u.last_name,
              u.phone,
              u.address,
              u.city,
              u.role
            FROM user_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide']);
            return;
        }

        echo json_encode($user, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'me failed',
            'detail' => $e->getMessage()
        ]);
    }
}

public static function updateMe() {
  header('Content-Type: application/json; charset=utf-8');

  try {
    $pdo = pdo();

    $token = self::getBearerToken();
    if (!$token) {
      http_response_code(401);
      echo json_encode(['error' => 'Token manquant'], JSON_UNESCAPED_UNICODE);
      return;
    }

    // récupérer user
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

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !is_array($data)) {
      http_response_code(400);
      echo json_encode(['error' => 'JSON invalide'], JSON_UNESCAPED_UNICODE);
      return;
    }

    $phone = trim((string)($data['phone'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));
    $city = trim((string)($data['city'] ?? ''));

    if ($phone === '' || $address === '' || $city === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Téléphone, adresse et ville sont obligatoires'], JSON_UNESCAPED_UNICODE);
      return;
    }

    $stmt = $pdo->prepare("
      UPDATE users
      SET phone = ?, address = ?, city = ?
      WHERE id = ?
    ");
    $stmt->execute([$phone, $address, $city, (int)$user['id']]);

    echo json_encode([
      'success' => true,
      'user' => [
        'phone' => $phone,
        'address' => $address,
        'city' => $city
      ]
    ], JSON_UNESCAPED_UNICODE);

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
      'error' => 'updateMe failed',
      'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
  }}


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

public static function forgotPassword() {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = strtolower(trim((string)($data['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo = pdo();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                'success' => true,
                'message' => 'Si un compte existe, un lien sera envoyé.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expiresAt]);

        $resetLink = 'https://jean-baptiste.alwaysdata.net/reset-password.html?token=' . urlencode($token);

        @mail($email, "Réinitialisation de mot de passe", "Cliquez ici : $resetLink");

        $appEnv = getenv('APP_ENV') ?: '';
        $isDev = ($appEnv === 'dev') || (strpos(self::baseUrl(), 'localhost') !== false);

        $payload = [
            'success' => true,
            'message' => 'Si un compte existe, un lien sera envoyé.'
        ];

        if ($isDev) {
            $payload['debug_reset_link'] = $resetLink;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'forgotPassword failed',
            'detail' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
}

public static function resetPassword() {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $token = trim((string)($data['token'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($token === '' || strlen($token) < 20) {
            http_response_code(400);
            echo json_encode(['error' => 'Token manquant/invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // même règle que ton front
        $pwdRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/';
        if (!preg_match($pwdRegex, $password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Mot de passe non conforme'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo = pdo();

        // Chercher le token
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(400);
            echo json_encode(['error' => 'Token invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Expiration
        $now = new DateTime();
        $exp = new DateTime($row['expires_at']);
        if ($now > $exp) {
            // supprimer token expiré
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            http_response_code(400);
            echo json_encode(['error' => 'Token expiré'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $email = $row['email'];

        // Update password users
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ? LIMIT 1");
        $stmt->execute([$hash, $email]);

        // Supprimer token après usage
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'resetPassword failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

}
