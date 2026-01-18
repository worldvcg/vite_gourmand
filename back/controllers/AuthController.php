<?php
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../helpers/AuthHeader.php';

class AuthController {

  public static function register() {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);

    $service = new AuthService();
    $res = $service->register($data ?? []);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }

  public static function login() {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);

    $service = new AuthService();
    $res = $service->login($data ?? []);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }

  public static function me() {
    header('Content-Type: application/json; charset=utf-8');
    $token = AuthHeader::getBearerToken() ?: '';

    $service = new AuthService();
    $res = $service->me($token);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }

  public static function updateMe() {
    header('Content-Type: application/json; charset=utf-8');
    $token = AuthHeader::getBearerToken() ?: '';
    $data = json_decode(file_get_contents('php://input'), true);

    $service = new AuthService();
    $res = $service->updateMe($token, $data ?? []);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }

  public static function logout() {
    header('Content-Type: application/json; charset=utf-8');
    $token = AuthHeader::getBearerToken();

    $service = new AuthService();
    $res = $service->logout($token);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }

  public static function forgotPassword() {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);

    $service = new AuthService();
    $res = $service->forgotPassword($data ?? []);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }

  public static function resetPassword() {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);

    $service = new AuthService();
    $res = $service->resetPassword($data ?? []);

    http_response_code($res['code']);
    echo json_encode($res['body'], JSON_UNESCAPED_UNICODE);
  }
}
