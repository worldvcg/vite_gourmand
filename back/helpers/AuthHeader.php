<?php

class AuthHeader {
  public static function getBearerToken(): ?string {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$auth) $auth = $_SERVER['Authorization'] ?? null;
    if (!$auth) $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

    if (!$auth) {
      if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) $auth = $headers['Authorization'];
      }
    }

    if ($auth && preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
      return $m[1];
    }

    if (!empty($_GET['token'])) return $_GET['token'];

    return null;
  }
}