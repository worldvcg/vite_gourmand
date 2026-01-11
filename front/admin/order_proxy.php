<?php
require_once __DIR__ . '/../../back/config/db.php';

header('Content-Type: application/json; charset=utf-8');

$API = 'https://jean-baptiste.alwaysdata.net/api/back/public/index.php?route=';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];
$action = $body['action'] ?? '';

function call_api($method, $url, $token = null) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

  $headers = ['Content-Type: application/json'];
  if ($token) $headers[] = 'Authorization: Bearer ' . $token;
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($resp === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Proxy cURL error', 'details' => curl_error($ch)]);
    exit;
  }

  curl_close($ch);
  http_response_code($code);
  echo $resp;
  exit;
}

if ($action === 'list') {
  $token = $body['authToken'] ?? null;
  call_api('GET', $API . '/api/orders', $token);
}

http_response_code(400);
echo json_encode(['error' => 'Bad request']);