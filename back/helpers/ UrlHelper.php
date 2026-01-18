<?php

class UrlHelper {
  public static function baseUrl(): string {
    $env = getenv('FRONT_BASE_URL');
    if ($env && trim($env) !== '') return rtrim(trim($env), '/');

    $isHttps =
      (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
      || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($host !== '') return $scheme . '://' . $host . '/vite_gourmand/front';

    return 'http://localhost:8888/vite_gourmand/front';
  }

  public static function frontUrl(string $path): string {
    $base = self::baseUrl();
    $path = '/' . ltrim($path, '/');
    return rtrim($base, '/') . $path;
  }
}