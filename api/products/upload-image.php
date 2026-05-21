<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_method('POST');
require_admin_api();
require_csrf();

try {
  $productId = (int)($_POST['product_id'] ?? $_POST['id'] ?? 0);

  if ($productId <= 0) {
    json_response([
      'success' => false,
      'message' => 'Produto não informado para vincular a imagem.'
    ], 422);
  }

  $row = find_product_row((string)$productId);
  if (!$row) {
    json_response([
      'success' => false,
      'message' => 'Produto não encontrado para vincular a imagem.'
    ], 404);
  }

  $uploadedCount = upload_product_images($productId, false);
  $updated = find_product_row((string)$productId);

  json_response([
    'success' => true,
    'message' => 'Imagem vinculada ao produto com sucesso.',
    'uploaded_count' => $uploadedCount,
    'received_files_count' => count(normalize_uploaded_files()),
    'product' => map_product($updated)
  ]);
} catch (Throwable $e) {
  json_response([
    'success' => false,
    'message' => $e->getMessage()
  ], 500);
}
