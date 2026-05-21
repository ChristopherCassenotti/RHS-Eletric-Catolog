<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';
require_method('POST');
require_admin_api();
require_csrf();

try {
  $id = clean_string($_POST['id'] ?? '', 190);
  if (!$id) json_response(['success' => false, 'message' => 'Produto não informado.'], 422);

  $row = find_product_row($id);
  if (!$row) json_response(['success' => false, 'message' => 'Produto não encontrado.'], 404);

  $name = clean_string($_POST['name'] ?? '', 180);
  $category = clean_string($_POST['category'] ?? '', 80);
  if (!$name || !$category) {
    json_response(['success' => false, 'message' => 'Nome e categoria são obrigatórios.'], 422);
  }

  $productId = (int)$row['id'];
  $slug = $row['slug']; // mantém a URL antiga estável mesmo que o nome mude
  $oldPrice = ($_POST['oldPrice'] ?? '') !== '' ? clean_decimal($_POST['oldPrice']) : null;

  $stmt = db()->prepare('UPDATE produtos SET
    slug = ?, nome = ?, categoria = ?, preco = ?, preco_antigo = ?, tipo = ?, tag = ?, tag_cor = ?, status = ?, icone = ?, autonomia = ?, velocidade = ?, potencia = ?, descricao = ?, destaque = ?
    WHERE id = ?');

  $stmt->execute([
    $slug,
    $name,
    $category,
    clean_decimal($_POST['price'] ?? 0),
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
    $productId,
  ]);

  // Se novas imagens forem selecionadas na edição, substitui a galeria atual.
  $uploadedCount = upload_product_images($productId, true);

  $updated = find_product_row((string)$productId);
  json_response([
    'success' => true,
    'message' => 'Produto atualizado com sucesso.',
    'uploaded_count' => $uploadedCount,
    'received_files_count' => count(normalize_uploaded_files()),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'product' => map_product($updated)
  ]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
