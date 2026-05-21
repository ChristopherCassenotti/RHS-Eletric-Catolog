<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

function products_table_exists(string $table): bool {
  try {
    $stmt = db()->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function products_column_exists(string $table, string $column): bool {
  try {
    $stmt = db()->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function first_existing_column(string $table, array $columns): ?string {
  foreach ($columns as $column) {
    if (products_column_exists($table, $column)) return $column;
  }
  return null;
}

function normalize_image_url($url): string {
  $url = trim((string)$url);
  if ($url === '') return '';
  if (strpos($url, '//') === 0) return 'https:' . $url;
  return $url;
}

function unique_non_empty_urls(array $urls): array {
  $out = [];
  $seen = [];
  foreach ($urls as $url) {
    $url = normalize_image_url($url);
    if ($url === '') continue;
    if (isset($seen[$url])) continue;
    $seen[$url] = true;
    $out[] = $url;
  }
  return $out;
}

function parse_image_list_value($value): array {
  $value = trim((string)$value);
  if ($value === '') return [];

  $decoded = json_decode($value, true);
  if (is_array($decoded)) {
    $urls = [];
    foreach ($decoded as $item) {
      if (is_string($item)) {
        $urls[] = $item;
      } elseif (is_array($item)) {
        foreach (['url', 'secure_url', 'imagem_url', 'image_url', 'src'] as $key) {
          if (!empty($item[$key])) {
            $urls[] = $item[$key];
            break;
          }
        }
      }
    }
    return unique_non_empty_urls($urls);
  }

  if (strpos($value, 'http') !== false && (strpos($value, ',') !== false || strpos($value, "\n") !== false || strpos($value, '|') !== false)) {
    return unique_non_empty_urls(preg_split('/[\n,|]+/', $value) ?: []);
  }

  return unique_non_empty_urls([$value]);
}

function ensure_product_images_table(): void {
  static $checked = false;
  if ($checked) return;
  $checked = true;

  try {
    db()->exec("CREATE TABLE IF NOT EXISTS produto_imagens (
      id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      produto_id INT(10) UNSIGNED NOT NULL,
      imagem_url TEXT NOT NULL,
      cloudinary_public_id VARCHAR(255) DEFAULT NULL,
      ordem INT(10) UNSIGNED NOT NULL DEFAULT 0,
      principal TINYINT(1) NOT NULL DEFAULT 0,
      criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_produto_ordem (produto_id, ordem),
      KEY idx_principal (principal)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  } catch (Throwable $e) {
    // Se o banco não permitir CREATE TABLE, o site continua tentando ler imagens já existentes.
  }
}

function product_image_table_candidates(): array {
  return ['produto_imagens', 'produtos_imagens', 'product_images', 'images_produtos'];
}

function detect_product_images_table(): ?string {
  foreach (product_image_table_candidates() as $table) {
    if (products_table_exists($table)) return $table;
  }
  return null;
}

function product_images(int $productId): array {
  try {
    if ($productId <= 0) return [];

    $stmt = db()->prepare("
      SELECT 
        id,
        produto_id,
        TRIM(imagem_url) AS imagem_url,
        cloudinary_public_id,
        ordem,
        principal
      FROM produto_imagens
      WHERE produto_id = ?
        AND imagem_url IS NOT NULL
        AND TRIM(imagem_url) <> ''
      ORDER BY principal DESC, ordem ASC, id ASC
    ");

    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    json_response([
      'success' => false,
      'message' => 'Erro ao buscar imagens do produto.',
      'debug' => $e->getMessage()
    ], 500);
  }
}

function direct_product_image_urls(array $row): array {
  $urls = [];
  foreach (['imagem_url', 'image_url', 'secure_url', 'cloudinary_url', 'image', 'imagem', 'foto', 'thumbnail', 'thumb', 'url'] as $field) {
    if (array_key_exists($field, $row)) {
      $urls = array_merge($urls, parse_image_list_value($row[$field]));
    }
  }
  foreach (['images', 'imagens', 'gallery', 'galeria'] as $field) {
    if (array_key_exists($field, $row)) {
      $urls = array_merge($urls, parse_image_list_value($row[$field]));
    }
  }
  return unique_non_empty_urls($urls);
}

function normalize_tag_color(?string $color): string {
  $color = strtolower(trim((string)$color));
  $map = [
    'pink' => 'red',
    'purple' => 'gray',
    'yellow' => 'light',
    'orange' => 'red',
    'green' => 'gray',
    'blue' => 'gray',
    'red' => 'red',
    'gray' => 'gray',
    'grey' => 'gray',
    'black' => 'black',
    'white' => 'light',
    'light' => 'light',
  ];
  return $map[$color] ?? 'red';
}

function map_product(array $row, ?array $images = null): array {
  $imgs = $images ?? product_images((int)$row['id']);

  $urls = array_values(array_filter(array_map(function ($img) {
    return trim((string)($img['imagem_url'] ?? ''));
  }, $imgs)));

  return [
    'id' => (string)($row['slug'] ?: $row['id']),
    'dbId' => (int)$row['id'],
    'name' => $row['nome'] ?? '',
    'category' => $row['categoria'] ?? '',
    'price' => (float)($row['preco'] ?? 0),
    'oldPrice' => isset($row['preco_antigo']) && $row['preco_antigo'] !== null ? (float)$row['preco_antigo'] : 0,
    'type' => $row['tipo'] ?? '',
    'tag' => $row['tag'] ?? '',
    'tagColor' => normalize_tag_color($row['tag_cor'] ?? 'red'),
    'status' => $row['status'] ?? '',
    'icon' => $row['icone'] ?? '⚡',
    'autonomy' => $row['autonomia'] ?? '',
    'speed' => $row['velocidade'] ?? '',
    'power' => $row['potencia'] ?? '',
    'description' => $row['descricao'] ?? '',
    'image' => $urls[0] ?? '',
    'images' => $urls,
    'featured' => (bool)($row['destaque'] ?? false),
  ];
}

function unique_slug(string $name, ?int $ignoreId = null): string {
  $base = slugify_php($name);
  $slug = $base;
  $i = 2;

  while (true) {
    if ($ignoreId) {
      $stmt = db()->prepare('SELECT id FROM produtos WHERE slug = ? AND id <> ? LIMIT 1');
      $stmt->execute([$slug, $ignoreId]);
    } else {
      $stmt = db()->prepare('SELECT id FROM produtos WHERE slug = ? LIMIT 1');
      $stmt->execute([$slug]);
    }

    if (!$stmt->fetch()) return $slug;
    $slug = $base . '-' . $i;
    $i++;
  }
}

function find_product_row(string $id): ?array {
  $id = trim($id);
  if ($id === '') return null;

  if (ctype_digit($id)) {
    $stmt = db()->prepare('SELECT * FROM produtos WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$id]);
    $row = $stmt->fetch();
    if ($row) return $row;
  }

  $stmt = db()->prepare('SELECT * FROM produtos WHERE slug = ? LIMIT 1');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if ($row) return $row;

  $stmt = db()->prepare('SELECT * FROM produtos WHERE nome = ? LIMIT 1');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function first_public_product_row(): ?array {
  try {
    $stmt = db()->query("SELECT * FROM produtos WHERE ativo = 1 AND COALESCE(status, '') <> 'Oculto' ORDER BY destaque DESC, atualizado_em DESC, criado_em DESC, id DESC LIMIT 1");
    $row = $stmt->fetch();
    return $row ?: null;
  } catch (Throwable $e) {
    return null;
  }
}

function product_is_public(array $row): bool {
  return (int)($row['ativo'] ?? 0) === 1 && (($row['status'] ?? '') !== 'Oculto');
}

function cloudinary_signature(array $params, string $secret): string {
  ksort($params);
  $pairs = [];
  foreach ($params as $key => $value) {
    if ($value === null || $value === '') continue;
    $pairs[] = $key . '=' . $value;
  }
  return sha1(implode('&', $pairs) . $secret);
}

function cloudinary_upload_file(string $tmpPath, string $originalName, string $mime): array {
  global $ENV;

  $cloud = $ENV['cloudinary_cloud_name'] ?? '';
  $key = $ENV['cloudinary_api_key'] ?? '';
  $secret = $ENV['cloudinary_api_secret'] ?? '';
  $folder = $ENV['cloudinary_folder'] ?? 'rhs-electric/produtos';

  if (!$cloud || !$key || !$secret || $cloud === 'SEU_CLOUD_NAME') {
    throw new RuntimeException('Configure as credenciais da Cloudinary em api/config/env.php.');
  }

  $timestamp = time();
  $params = ['folder' => $folder, 'timestamp' => $timestamp];
  $signature = cloudinary_signature($params, $secret);

  $curlFile = new CURLFile($tmpPath, $mime, $originalName);
  $post = [
    'file' => $curlFile,
    'api_key' => $key,
    'timestamp' => $timestamp,
    'folder' => $folder,
    'signature' => $signature,
  ];

  $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloud}/image/upload");
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
  ]);

  $response = curl_exec($ch);
  $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);

  if ($response === false || $httpCode < 200 || $httpCode >= 300) {
    throw new RuntimeException('Erro ao enviar imagem para a Cloudinary. ' . ($error ?: $response));
  }

  $data = json_decode((string)$response, true);
  if (!is_array($data) || empty($data['secure_url'])) {
    throw new RuntimeException('Resposta inválida da Cloudinary.');
  }

  return [
    'url' => $data['secure_url'],
    'public_id' => $data['public_id'] ?? null,
  ];
}

function cloudinary_delete_public_id(?string $publicId): void {
  global $ENV;
  if (!$publicId) return;

  $cloud = $ENV['cloudinary_cloud_name'] ?? '';
  $key = $ENV['cloudinary_api_key'] ?? '';
  $secret = $ENV['cloudinary_api_secret'] ?? '';
  if (!$cloud || !$key || !$secret || $cloud === 'SEU_CLOUD_NAME') return;

  $timestamp = time();
  $params = ['public_id' => $publicId, 'timestamp' => $timestamp];
  $signature = cloudinary_signature($params, $secret);

  $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloud}/image/destroy");
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
      'public_id' => $publicId,
      'api_key' => $key,
      'timestamp' => $timestamp,
      'signature' => $signature,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
  ]);
  curl_exec($ch);
  curl_close($ch);
}

function normalize_uploaded_files(): array {
  $normalized = [];

  foreach (['images', 'images[]', 'image', 'file'] as $field) {
    if (empty($_FILES[$field])) continue;

    $files = $_FILES[$field];
    $names = is_array($files['name'] ?? null) ? $files['name'] : [$files['name'] ?? ''];
    $tmpNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : [$files['tmp_name'] ?? ''];
    $errors = is_array($files['error'] ?? null) ? $files['error'] : [$files['error'] ?? UPLOAD_ERR_NO_FILE];
    $sizes = is_array($files['size'] ?? null) ? $files['size'] : [$files['size'] ?? 0];
    $types = is_array($files['type'] ?? null) ? $files['type'] : [$files['type'] ?? ''];

    foreach ($names as $i => $name) {
      $tmpName = $tmpNames[$i] ?? '';
      $error = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
      $size = (int)($sizes[$i] ?? 0);

      if ($error === UPLOAD_ERR_NO_FILE) continue;

      $key = md5($field . '|' . $name . '|' . $tmpName . '|' . $size);
      if (isset($normalized[$key])) continue;

      $normalized[$key] = [
        'field' => $field,
        'name' => (string)$name,
        'tmp_name' => (string)$tmpName,
        'error' => $error,
        'size' => $size,
        'type' => (string)($types[$i] ?? ''),
      ];
    }
  }

  return array_values($normalized);
}

function upload_product_images(int $productId, bool $replaceExisting = false): int {
  ensure_product_images_table();
  $files = normalize_uploaded_files();
  if (!$files) return 0;

  $validFiles = [];

  foreach ($files as $file) {
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Erro no upload da imagem: ' . ($file['name'] ?? 'arquivo'));
    }

    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
      throw new RuntimeException('Imagem muito pesada. Limite recomendado: 8MB por arquivo.');
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
      throw new RuntimeException('Arquivo temporário inválido: ' . ($file['name'] ?? 'arquivo'));
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
      throw new RuntimeException('Formato inválido. Envie JPG, PNG, WEBP ou GIF.');
    }

    $file['type'] = $mime;
    $validFiles[] = $file;
  }

  if (!$validFiles) return 0;

  if ($replaceExisting && products_table_exists('produto_imagens')) {
    $stmt = db()->prepare('SELECT cloudinary_public_id FROM produto_imagens WHERE produto_id = ?');
    $stmt->execute([$productId]);
    foreach ($stmt->fetchAll() as $img) {
      cloudinary_delete_public_id($img['cloudinary_public_id'] ?? null);
    }
    $del = db()->prepare('DELETE FROM produto_imagens WHERE produto_id = ?');
    $del->execute([$productId]);
  }

  $orderStmt = db()->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM produto_imagens WHERE produto_id = ?');
  $orderStmt->execute([$productId]);
  $ordem = (int)$orderStmt->fetchColumn();

  $countStmt = db()->prepare('SELECT COUNT(*) FROM produto_imagens WHERE produto_id = ?');
  $countStmt->execute([$productId]);
  $hasImages = ((int)$countStmt->fetchColumn()) > 0;

  $insert = db()->prepare('INSERT INTO produto_imagens (produto_id, imagem_url, cloudinary_public_id, ordem, principal) VALUES (?, ?, ?, ?, ?)');
  $uploadedCount = 0;

  foreach ($validFiles as $file) {
    $uploaded = cloudinary_upload_file($file['tmp_name'], $file['name'], $file['type']);
    $principal = (!$hasImages && $ordem === 0) ? 1 : 0;
    $insert->execute([$productId, $uploaded['url'], $uploaded['public_id'], $ordem, $principal]);
    $ordem++;
    $uploadedCount++;
  }

  return $uploadedCount;
}
