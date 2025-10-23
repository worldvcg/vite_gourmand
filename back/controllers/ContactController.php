<?php
// back/controllers/ContactController.php
require_once __DIR__ . '/../config/db.php';

class ContactController {
  public static function create() {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
      http_response_code(400);
      echo json_encode(['error' => 'Payload JSON invalide']);
      return;
    }

    $email = trim($input['email'] ?? '');
    $subject = trim($input['subject'] ?? '');
    $message = trim($input['message'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Champs requis manquants ou invalides']);
      return;
    }

    try {
      $pdo = DB::getConnection();
      $stmt = $pdo->prepare('INSERT INTO contact_messages (email, subject, message) VALUES (?, ?, ?)');
      $stmt->execute([$email, $subject, $message]);

      // Optionnel : envoi mail (si MAMP mail() ok)
      // @mail('contact@vitetgourmand.fr', '[Contact] ' . $subject, $message . "\n\nDe: " . $email);

      echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Erreur serveur', 'detail' => $e->getMessage()]);
    }
  }
}