<?php
declare(strict_types=1);

$envPath = __DIR__ . '/env.php';
if (!file_exists($envPath)) {
  $envPath = __DIR__ . '/env.php';
}
$ENV = require $envPath;

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');

session_name($ENV['session_name'] ?? 'rhs_admin_session');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $isHttps,
  'httponly' => true,
  'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function json_response(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function input_json(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_method(string $method): void {
  if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
    json_response(['success' => false, 'message' => 'Método não permitido.'], 405);
  }
}

function current_admin(): ?array {
  if (empty($_SESSION['admin_user'])) return null;
  return $_SESSION['admin_user'];
}

function require_admin_api(): array {
  $admin = current_admin();
  if (!$admin) {
    json_response(['success' => false, 'message' => 'Acesso não autorizado.'], 401);
  }
  return $admin;
}

function require_csrf(): void {
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    json_response(['success' => false, 'message' => 'Token de segurança inválido. Atualize a página e tente novamente.'], 403);
  }
}

function clean_string($value, int $max = 255): string {
  $value = trim((string)($value ?? ''));
  $value = preg_replace('/\s+/', ' ', $value);
  return mb_substr($value, 0, $max);
}

function clean_decimal($value): float {
  if ($value === null || $value === '') return 0.0;
  $value = str_replace(['R$', '.', ','], ['', '', '.'], (string)$value);
  return (float)$value;
}

function slugify_php(string $text): string {
  $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
  $text = strtolower((string)$text);
  $text = preg_replace('/[^a-z0-9]+/', '-', $text);
  $text = trim((string)$text, '-');
  return $text ?: 'produto';
}
