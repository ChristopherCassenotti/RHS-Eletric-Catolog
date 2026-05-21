<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

try {
  $isAdminList = isset($_GET['admin']) && $_GET['admin'] === '1';
  if ($isAdminList) {
    require_admin_api();
  }

  $where = ['ativo = 1'];
  $params = [];

  if (!$isAdminList) {
    $where[] = "COALESCE(status, '') <> 'Oculto'";
  }

  if (!empty($_GET['category']) && $_GET['category'] !== 'todos') {
    $where[] = 'categoria = ?';
    $params[] = clean_string($_GET['category'], 80);
  }

  $sql = 'SELECT * FROM produtos WHERE ' . implode(' AND ', $where) . ' ORDER BY destaque DESC, atualizado_em DESC, criado_em DESC, id DESC';
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  $products = [];
  foreach ($rows as $row) {
    $products[] = map_product($row);
  }

  json_response(['success' => true, 'products' => $products]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
