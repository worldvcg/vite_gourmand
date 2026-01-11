<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$id = $data['id'] ?? null;
$payload = $data['payload'] ?? [];
$token = $data['authToken'] ?? null;

$base = 'https://jean-baptiste.alwaysdata.net/api/back/public/index.php?route=/api/menus';

$method = 'GET';
$url = $base;

if ($action === 'create') $method = 'POST';
elseif ($action === 'update') { $method = 'PUT'; $url .= "/$id"; }
elseif ($action === 'delete') { $method = 'DELETE'; $url .= "/$id"; }

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        $token ? "Authorization: Bearer $token" : ''
    ]
]);

echo curl_exec($ch);