<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function db(): PDO {
  static $pdo = null;
  global $ENV;

  if ($pdo instanceof PDO) return $pdo;

  $dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $ENV['db_host'],
    $ENV['db_name']
  );

  try {
    $pdo = new PDO($dsn, $ENV['db_user'], $ENV['db_pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
  } catch (Throwable $e) {
    json_response([
      'success' => false,
      'message' => 'Erro ao conectar no banco de dados.',
      'debug' => $e->getMessage(),
    ], 500);
  }
}
