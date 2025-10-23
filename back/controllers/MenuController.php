<?php
require_once __DIR__ . '/../models/MenuModel.php';

class MenuController {
  public static function list(): void {
    $filters = [
      'theme'      => $_GET['theme']      ?? null,
      'regime'     => $_GET['regime']     ?? null,
      'price_max'  => $_GET['price_max']  ?? null,
      'price_min'  => $_GET['price_min']  ?? null,
      'min_people' => $_GET['min_people'] ?? null,
    ];
    $data = MenuModel::findAll($filters);
    self::json($data);
  }

  public static function detail($id): void {
    if (!ctype_digit((string)$id)) self::json(['error' => 'Bad id'], 400);
    $menu = MenuModel::findOne((int)$id);
    if (!$menu) self::json(['error' => 'Not found'], 404);
    self::json($menu);
  }

  private static function json($payload, int $status=200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    // CORS dev (autoriser front statique)
    header('Access-Control-Allow-Origin: *');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
}