<?php
require_once __DIR__ . '/../repositories/AuthRepository.php';
require_once __DIR__ . '/../helpers/UrlHelper.php';

class AuthService {
  private AuthRepository $repo;

  public function __construct() {
    $this->repo = new AuthRepository();
  }

  public function register(array $data): array {
    $required = ['email','password','first_name','last_name','phone','address','city'];
    foreach ($required as $f) {
      if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
        return ['code' => 400, 'body' => ['error' => "Champ manquant : $f"]];
      }
    }

    $email = strtolower(trim((string)$data['email']));
    $password = (string)$data['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['code' => 400, 'body' => ['error' => 'Email invalide']];
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $password)) {
      return ['code' => 400, 'body' => ['error' => 'Mot de passe non conforme']];
    }

    if ($this->repo->findUserByEmail($email)) {
      return ['code' => 409, 'body' => ['error' => 'Email déjà utilisé']];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $this->repo->createUser([
      'first_name' => trim((string)$data['first_name']),
      'last_name'  => trim((string)$data['last_name']),
      'email'      => $email,
      'password_hash' => $hash,
      'phone'      => trim((string)$data['phone']),
      'address'    => trim((string)$data['address']),
      'city'       => trim((string)$data['city'])
    ]);

    // Mail de bienvenue (non bloquant)
    $sent = false;
    try {
      $subject = "Bienvenue chez Vite & Gourmand";
      $message = "Bonjour " . trim((string)$data['first_name']) . ",\n\nBienvenue chez Vite & Gourmand !\nVotre compte a bien été créé.\n\nÀ bientôt,\nJulie & José";
      $headers = "From: contact@vite-gourmand.fr\r\n";
      $sent = @mail($email, $subject, $message, $headers);
    } catch (Throwable $e) {
      $sent = false;
    }

    return ['code' => 200, 'body' => ['success' => true, 'welcome_mail_sent' => (bool)$sent]];
  }

  public function login(array $data): array {
    if (!$data || empty($data['email']) || empty($data['password'])) {
      return ['code' => 400, 'body' => ['error' => 'Champs manquants']];
    }

    $user = $this->repo->findUserByEmail((string)$data['email']);
    if (!$user || !password_verify((string)$data['password'], (string)$user['password_hash'])) {
      return ['code' => 401, 'body' => ['error' => 'Identifiants invalides']];
    }

    if ((int)($user['is_active'] ?? 1) === 0) {
      return ['code' => 403, 'body' => ['error' => 'Compte désactivé']];
    }

    $token = bin2hex(random_bytes(32));
    $this->repo->insertToken((int)$user['id'], $token);

    return ['code' => 200, 'body' => [
      'token' => $token,
      'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
      ]
    ]];
  }

  public function me(string $token): array {
    if (!$token) return ['code' => 401, 'body' => ['error' => 'Token manquant']];

    $user = $this->repo->findUserByToken($token);
    if (!$user) return ['code' => 401, 'body' => ['error' => 'Token invalide']];

    // renvoyer uniquement ce que tu renvoyais déjà
    return ['code' => 200, 'body' => [
      'id' => $user['id'],
      'email' => $user['email'],
      'first_name' => $user['first_name'],
      'last_name' => $user['last_name'],
      'phone' => $user['phone'],
      'address' => $user['address'],
      'city' => $user['city'],
      'role' => $user['role']
    ]];
  }

  public function updateMe(string $token, array $data): array {
    if (!$token) return ['code' => 401, 'body' => ['error' => 'Token manquant']];
    if (!$data || !is_array($data)) return ['code' => 400, 'body' => ['error' => 'JSON invalide']];

    $user = $this->repo->findUserByToken($token);
    if (!$user) return ['code' => 401, 'body' => ['error' => 'Token invalide']];

    $phone = trim((string)($data['phone'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));
    $city = trim((string)($data['city'] ?? ''));

    if ($phone === '' || $address === '' || $city === '') {
      return ['code' => 400, 'body' => ['error' => 'Téléphone, adresse et ville sont obligatoires']];
    }

    $this->repo->updateMe((int)$user['id'], $phone, $address, $city);

    return ['code' => 200, 'body' => [
      'success' => true,
      'user' => ['phone' => $phone, 'address' => $address, 'city' => $city]
    ]];
  }

  public function logout(?string $token): array {
    if ($token) $this->repo->deleteToken($token);
    return ['code' => 200, 'body' => ['success' => true]];
  }

  public function forgotPassword(array $data): array {
    $email = strtolower(trim((string)($data['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['code' => 400, 'body' => ['error' => 'Email invalide']];
    }

    $user = $this->repo->findUserByEmail($email);

    // réponse neutre même si absent
    if (!$user) {
      return ['code' => 200, 'body' => ['success' => true, 'message' => 'Si un compte existe, un lien sera envoyé.']];
    }

    $this->repo->deletePasswordResetsByEmail($email);

    $token = bin2hex(random_bytes(32));
    $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
    $this->repo->insertPasswordReset($email, $token, $expiresAt);

    // lien front (prod ou dev selon FRONT_BASE_URL)
    $resetLink = UrlHelper::frontUrl('/reset-password.html') . '?token=' . urlencode($token);

    @mail($email, "Réinitialisation de mot de passe", "Cliquez ici : $resetLink");

    $appEnv = getenv('APP_ENV') ?: '';
    $isDev = ($appEnv === 'dev') || (strpos(UrlHelper::baseUrl(), 'localhost') !== false);

    $payload = ['success' => true, 'message' => 'Si un compte existe, un lien sera envoyé.'];
    if ($isDev) $payload['debug_reset_link'] = $resetLink;

    return ['code' => 200, 'body' => $payload];
  }

  public function resetPassword(array $data): array {
    $token = trim((string)($data['token'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($token === '' || strlen($token) < 20) {
      return ['code' => 400, 'body' => ['error' => 'Token manquant/invalide']];
    }

    $pwdRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/';
    if (!preg_match($pwdRegex, $password)) {
      return ['code' => 400, 'body' => ['error' => 'Mot de passe non conforme']];
    }

    $row = $this->repo->findPasswordResetByToken($token);
    if (!$row) return ['code' => 400, 'body' => ['error' => 'Token invalide']];

    $now = new DateTime();
    $exp = new DateTime($row['expires_at']);
    if ($now > $exp) {
      $this->repo->deletePasswordResetByToken($token);
      return ['code' => 400, 'body' => ['error' => 'Token expiré']];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $this->repo->updatePasswordByEmail($row['email'], $hash);
    $this->repo->deletePasswordResetByToken($token);

    return ['code' => 200, 'body' => ['success' => true]];
  }
}