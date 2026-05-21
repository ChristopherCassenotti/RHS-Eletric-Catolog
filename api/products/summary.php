<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

try {
  $publicWhere = "ativo = 1 AND COALESCE(status, '') <> 'Oculto'";

  $stmt = db()->query("SELECT categoria, COUNT(*) AS total FROM produtos WHERE {$publicWhere} GROUP BY categoria");
  $counts = [];
  foreach ($stmt->fetchAll() as $row) {
    $counts[(string)$row['categoria']] = (int)$row['total'];
  }

  $featuredStmt = db()->query("SELECT * FROM produtos WHERE {$publicWhere} AND destaque = 1 ORDER BY atualizado_em DESC, criado_em DESC, id DESC LIMIT 4");
  $featuredRows = $featuredStmt->fetchAll();

  if (!$featuredRows) {
    $fallbackStmt = db()->query("SELECT * FROM produtos WHERE {$publicWhere} ORDER BY atualizado_em DESC, criado_em DESC, id DESC LIMIT 4");
    $featuredRows = $fallbackStmt->fetchAll();
  }

  $featured = [];
  foreach ($featuredRows as $row) {
    $featured[] = map_product($row);
  }

  json_response([
    'success' => true,
    'counts' => $counts,
    'featured' => $featured,
  ]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
