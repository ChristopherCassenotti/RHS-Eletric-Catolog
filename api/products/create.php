<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_method('POST');
require_admin_api();
require_csrf();

try {
  $name = clean_string($_POST['name'] ?? '', 180);
  $category = clean_string($_POST['category'] ?? '', 80);
  $price = clean_decimal($_POST['price'] ?? 0);

  if (!$name || !$category) {
    json_response(['success' => false, 'message' => 'Nome e categoria são obrigatórios.'], 422);
  }

  $slug = unique_slug($name);

  $stmt = db()->prepare('INSERT INTO produtos
    (slug, nome, categoria, preco, preco_antigo, tipo, tag, tag_cor, status, icone, autonomia, velocidade, potencia, descricao, destaque, ativo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');

  $oldPrice = ($_POST['oldPrice'] ?? '') !== '' ? clean_decimal($_POST['oldPrice']) : null;
  $stmt->execute([
    $slug,
    $name,
    $category,
    $price,
    $oldPrice,
    clean_string($_POST['type'] ?? '', 120),
    clean_string($_POST['tag'] ?? '', 90),
    clean_string($_POST['tagColor'] ?? 'red', 30),
    clean_string($_POST['status'] ?? '', 60),
    clean_string($_POST['icon'] ?? '⚡', 20),
    clean_string($_POST['autonomy'] ?? '', 60),
    clean_string($_POST['speed'] ?? '', 60),
    clean_string($_POST['power'] ?? '', 60),
    trim((string)($_POST['description'] ?? '')),
    !empty($_POST['featured']) ? 1 : 0,
  ]);

  $productId = (int)db()->lastInsertId();
  $uploadedCount = upload_product_images($productId, false);

  $row = find_product_row((string)$productId);
  json_response([
    'success' => true,
    'message' => 'Produto cadastrado com sucesso.',
    'uploaded_count' => $uploadedCount,
    'received_files_count' => count(normalize_uploaded_files()),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'product' => map_product($row)
  ]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
