<?php
require_once __DIR__ . '/../config/db.php';

class AuthRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = pdo();
  }

  public function findUserByEmail(string $email): ?array {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function createUser(array $u): void {
    $stmt = $this->pdo->prepare("
      INSERT INTO users (first_name, last_name, email, password_hash, phone, address, city, role, is_active)
      VALUES (?, ?, ?, ?, ?, ?, ?, 'client', 1)
    ");
    $stmt->execute([
      $u['first_name'], $u['last_name'], $u['email'], $u['password_hash'],
      $u['phone'], $u['address'], $u['city']
    ]);
  }

  public function insertToken(int $userId, string $token): void {
    $stmt = $this->pdo->prepare("INSERT INTO user_tokens (user_id, token) VALUES (?, ?)");
    $stmt->execute([$userId, $token]);
  }

  public function findUserByToken(string $token): ?array {
    $stmt = $this->pdo->prepare("
      SELECT 
        u.id, u.email, u.first_name, u.last_name, u.phone, u.address, u.city, u.role, u.is_active
      FROM user_tokens t
      JOIN users u ON u.id = t.user_id
      WHERE t.token = ?
      LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function updateMe(int $userId, string $phone, string $address, string $city): void {
    $stmt = $this->pdo->prepare("
      UPDATE users
      SET phone = ?, address = ?, city = ?
      WHERE id = ?
    ");
    $stmt->execute([$phone, $address, $city, $userId]);
  }

  public function deleteToken(string $token): void {
    $stmt = $this->pdo->prepare("DELETE FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
  }

  public function deletePasswordResetsByEmail(string $email): void {
    $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
  }

  public function insertPasswordReset(string $email, string $token, string $expiresAt): void {
    $stmt = $this->pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expiresAt]);
  }

  public function findPasswordResetByToken(string $token): ?array {
    $stmt = $this->pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function deletePasswordResetByToken(string $token): void {
    $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
  }

  public function updatePasswordByEmail(string $email, string $hash): void {
    $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ? LIMIT 1");
    $stmt->execute([$hash, $email]);
  }
}