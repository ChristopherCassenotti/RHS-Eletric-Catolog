<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_admin_api();

try {
  $tables = [];
  foreach (['produtos', 'produto_imagens', 'produtos_imagens', 'product_images', 'images_produtos', 'veiculos', 'veiculo_imagens'] as $table) {
    if (!products_table_exists($table)) {
      $tables[$table] = ['exists' => false];
      continue;
    }

    $count = 0;
    try {
      $count = (int)db()->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (Throwable $e) {}

    $columns = [];
    try {
      $colsStmt = db()->query("SHOW COLUMNS FROM `{$table}`");
      foreach ($colsStmt->fetchAll() as $col) {
        $columns[] = $col['Field'];
      }
    } catch (Throwable $e) {}

    $tables[$table] = [
      'exists' => true,
      'count' => $count,
      'columns' => $columns,
    ];
  }

  $sample = [];
  $stmt = db()->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 10");
  foreach ($stmt->fetchAll() as $row) {
    $mapped = map_product($row);
    $sample[] = [
      'id' => $mapped['dbId'],
      'slug' => $mapped['id'],
      'name' => $mapped['name'],
      'active' => (int)($row['ativo'] ?? 0),
      'images_count' => count($mapped['images']),
      'image' => $mapped['image'],
      'images' => $mapped['images'],
    ];
  }

  json_response([
    'success' => true,
    'message' => 'Diagnóstico gerado com sucesso.',
    'detected_image_table' => detect_product_images_table(),
    'tables' => $tables,
    'products_sample' => $sample,
  ]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
