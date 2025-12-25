<?php
// Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Controllers
require_once __DIR__ . '/../controllers/MenuController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/OrderController.php'; // si présent

// Lecture route
$route = $_GET['route'] ?? '';
$route = '/' . ltrim($route, '/');
$route = rtrim($route, '/');

// ------------------------------
// 🟢 ROUTES API
// ------------------------------

// Simple test
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/') {
    echo json_encode(['ok' => true, 'service' => 'Vite & Gourmand API']);
    exit;
}


// -----------------------
// 🔐 AUTHENTIFICATION
// -----------------------

// REGISTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/auth/register') {
    AuthController::register();
    exit;
}

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/auth/login') {
    AuthController::login();
    exit;
}

// ME (token)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/auth/me') {
    AuthController::me();
    exit;
}

// LOGOUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/auth/logout') {
    AuthController::logout();
    exit;
}


// -----------------------
// ☎️ CONTACT
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/contact') {
    ContactController::create();
    exit;
}


// -----------------------
// 🍽️ MENUS
// -----------------------

// GET /api/menus
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/menus') {
    MenuController::list();
    exit;
}

// GET /api/menus/{id}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/api/menus/(\d+)$#', $route, $m)) {
    MenuController::detail((int)$m[1]);
    exit;
}

// POST /api/menus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/menus') {
    MenuController::create();
    exit;
}

// PUT /api/menus/{id}
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/api/menus/(\d+)$#', $route, $m)) {
    MenuController::update((int)$m[1]);
    exit;
}

// DELETE /api/menus/{id}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/api/menus/(\d+)$#', $route, $m)) {
    MenuController::delete((int)$m[1]);
    exit;
}


// -----------------------
// 🛒 COMMANDES (si tu les as)
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/orders') {
    OrderController::create();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/orders/my') {
    require_once __DIR__ . '/../controllers/OrderController.php';
    OrderController::getMyOrders();
    exit;
}

// -----------------------
// ❌ 404 SI AUCUNE ROUTE
// -----------------------
http_response_code(404);
echo json_encode([
    'error' => 'Route not found',
    'route' => $route
], JSON_UNESCAPED_UNICODE);
