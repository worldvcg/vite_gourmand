<?php
// back/controllers/ContactController.php
require_once __DIR__ . '/../config/db.php';

class ContactController
{
  public static function create()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $input = json_decode(file_get_contents('php://input'), true);
      if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Payload JSON invalide'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $email   = trim((string)($input['email'] ?? ''));
      $subject = trim((string)($input['subject'] ?? ''));
      $message = trim((string)($input['message'] ?? ''));

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['error' => 'Email invalide'], JSON_UNESCAPED_UNICODE);
        return;
      }
      if (mb_strlen($subject) < 3) {
        http_response_code(422);
        echo json_encode(['error' => 'Sujet trop court (min 3 caractères)'], JSON_UNESCAPED_UNICODE);
        return;
      }
      if (mb_strlen($message) < 10) {
        http_response_code(422);
        echo json_encode(['error' => 'Message trop court (min 10 caractères)'], JSON_UNESCAPED_UNICODE);
        return;
      }

      $pdo = pdo();

      // ✅ Enregistrement DB
      $stmt = $pdo->prepare("
        INSERT INTO contact_messages (email, subject, message, created_at)
        VALUES (?, ?, ?, NOW())
      ");
      $stmt->execute([$email, $subject, $message]);

      // ✅ Envoi mail (simple) — optionnel
      // ⚠️ sur MAMP, mail() peut être capricieux (souvent mieux SMTP/PHPMailer)
      $to = 'contact@vite-gourmand.fr';
      $mailSubject = '[Contact] ' . $subject;
      $body = "Nouveau message via formulaire :\n\n".
              "Email: {$email}\n".
              "Sujet: {$subject}\n\n".
              "{$message}\n";

      // @mail($to, $mailSubject, $body, "From: contact@vite-gourmand.fr\r\nReply-To: {$email}\r\n");

      echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode([
        'error' => 'Erreur serveur',
        'detail' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
    }
  }
}