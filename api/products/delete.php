<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_method('POST');
require_admin_api();
require_csrf();

try {
  $data = input_json();
  $id = clean_string($data['id'] ?? '', 190);
  if (!$id) json_response(['success' => false, 'message' => 'Produto não informado.'], 422);

  $row = find_product_row($id);
  if (!$row) json_response(['success' => false, 'message' => 'Produto não encontrado.'], 404);

  $productId = (int)$row['id'];

  if (products_table_exists('produto_imagens')) {
    $stmt = db()->prepare('SELECT cloudinary_public_id FROM produto_imagens WHERE produto_id = ?');
    $stmt->execute([$productId]);
    foreach ($stmt->fetchAll() as $img) {
      cloudinary_delete_public_id($img['cloudinary_public_id'] ?? null);
    }
  }

  $delete = db()->prepare('DELETE FROM produtos WHERE id = ?');
  $delete->execute([$productId]);

  json_response(['success' => true, 'message' => 'Produto excluído com sucesso.']);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
