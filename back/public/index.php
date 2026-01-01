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
require_once __DIR__ . '/../controllers/OrderController.php'; 
require_once __DIR__ . '/../controllers/ReviewController.php';
require_once __DIR__ . '/../controllers/DishController.php';
require_once __DIR__ . '/../controllers/OpeningHoursController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/StatsController.php';

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

// LIST ALL ORDERS (employé / admin)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/orders') {
    OrderController::listAll();
    exit;
}

// UPDATE ORDER STATUS (employé/admin)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/api/orders/(\d+)/status$#', $route, $m)) {
    OrderController::updateStatus((int)$m[1]);
    exit;
}

// CANCEL ORDER (employé/admin) : POST /api/orders/{id}/cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/api/orders/(\d+)/cancel$#', $route, $m)) {
    OrderController::cancel((int)$m[1]);
    exit;
}

// GET /api/orders/{id}/status (suivi client)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('#^/api/orders/(\d+)/status$#', $route, $m)) {
    OrderController::getStatus((int)$m[1]);
    exit;
}

// -----------------------
// ⭐ AVIS / REVIEWS
// -----------------------

// LIST REVIEWS (employé/admin)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/reviews') {
    ReviewController::list();
    exit;
}

// MODERATE REVIEW
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/api/reviews/(\d+)/moderate$#', $route, $m)) {
    ReviewController::moderate((int)$m[1]);
    exit;
}

// -----------------------
// 🍽️ DISHES
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/dishes') {
    DishController::list();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/dishes') {
    DishController::create();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/api/dishes/(\d+)$#', $route, $m)) {
    DishController::update((int)$m[1]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('#^/api/dishes/(\d+)$#', $route, $m)) {
    DishController::delete((int)$m[1]);
    exit;
}

// -----------------------
// 🕒 OPENING HOURS
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/opening-hours') {
    OpeningHoursController::list();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/api/opening-hours/(\d+)$#', $route, $m)) {
    OpeningHoursController::update((int)$m[1]);
    exit;
}

// -----------------------
// 🛠️ ADMIN (Employés)
// -----------------------

// LIST EMPLOYEES
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/admin/employees') {
    AdminController::listEmployees();
    exit;
}

// CREATE EMPLOYEE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/api/admin/employees') {
    AdminController::createEmployee();
    exit;
}

// TOGGLE ACTIVE (enable/disable)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('#^/api/admin/employees/(\d+)/active$#', $route, $m)) {
    AdminController::setEmployeeActive((int)$m[1]);
    exit;
}

// -----------------------
// 📊 ADMIN STATS
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/admin/stats/orders-per-menu') {
    StatsController::ordersPerMenu();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route === '/api/admin/stats/revenue-per-menu') {
    StatsController::revenuePerMenu();
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
