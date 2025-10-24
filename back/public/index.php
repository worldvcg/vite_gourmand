<?php
// Debug (à retirer en prod)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }


require_once __DIR__ . '/../controllers/MenuController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Route demandée : priorité au ?route= (quand pas de .htaccess)
$route = isset($_GET['route']) ? $_GET['route'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'service' => 'Vite & Gourmand API']);
  exit;
}

if ($route !== null) {
  $route = '/' . ltrim($route, '/');    // force un / au début
  $route = rtrim($route, '/');          // supprime le / final éventuel
  if ($route === '') $route = '/';
}

if ($route === null) {
  // Fallback si tu actives plus tard la réécriture
  $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $root = '/vite_gourmand/back/public'; // adapte si ton chemin est différent
  $path = (substr($uri, 0, strlen($root)) === $root) ? substr($uri, strlen($root)) : $uri;
  $route = rtrim($path, '/'); 
  if ($route === '') $route = '/';
}

// Routes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/menus') {
  MenuController::list(); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/api/menus/(\d+)$#', $route, $m)) {
  MenuController::detail((int)$m[1]); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/contact') {
  ContactController::create(); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/auth/register') { AuthController::register(); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/auth/login')    { AuthController::login();    exit; }
if ($_SERVER['REQUEST_METHOD'] === 'GET'  && $route === '/api/auth/me')       { AuthController::me();       exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/auth/logout')   { AuthController::logout();   exit; }

// 404
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Route not found', 'route' => $route], JSON_UNESCAPED_UNICODE);