<?php
declare(strict_types=1);
require_once __DIR__ . '/_helpers.php';

try {
  $id = clean_string($_GET['id'] ?? '', 190);
  $row = null;

  if ($id !== '') {
    $row = find_product_row($id);
  }

  // Se a URL veio sem id, ou com um id antigo/inválido, ainda assim puxamos o primeiro produto público do banco.
  // Isso evita a tela "Produto não encontrado" quando o site está sendo testado direto em produto.html.
  if (!$row || !product_is_public($row)) {
    $row = first_public_product_row();
  }

  if (!$row || !product_is_public($row)) {
    json_response(['success' => false, 'message' => 'Nenhum produto ativo encontrado no banco de dados.'], 404);
  }

  json_response(['success' => true, 'product' => map_product($row)]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
