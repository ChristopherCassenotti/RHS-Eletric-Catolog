<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_method('POST');

$count = (int)db()->query('SELECT COUNT(*) FROM usuarios_admin')->fetchColumn();
if ($count > 0) {
  json_response([
    'success' => false,
    'message' => 'Já existe um administrador cadastrado. Por segurança, apague este arquivo da hospedagem.'
  ], 403);
}

$data = input_json();
$nome = clean_string($data['nome'] ?? 'Administrador', 120);
$email = strtolower(clean_string($data['email'] ?? '', 180));
$senha = (string)($data['senha'] ?? '');

if (!$email || strlen($senha) < 8) {
  json_response(['success' => false, 'message' => 'Informe um e-mail e uma senha com pelo menos 8 caracteres.'], 422);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO usuarios_admin (nome, email, senha_hash, tipo, ativo) VALUES (?, ?, ?, "admin", 1)');
$stmt->execute([$nome, $email, $hash]);

json_response([
  'success' => true,
  'message' => 'Primeiro administrador criado. Agora apague o arquivo api/auth/create-first-admin.php da hospedagem.'
]);
