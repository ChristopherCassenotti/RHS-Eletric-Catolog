<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_method('POST');

$data = input_json();
$email = strtolower(clean_string($data['email'] ?? '', 180));
$senha = (string)($data['senha'] ?? '');

if (!$email || !$senha) {
  json_response(['success' => false, 'message' => 'Informe e-mail e senha.'], 422);
}

$stmt = db()->prepare('SELECT id, nome, email, senha_hash, tipo, ativo FROM usuarios_admin WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !(int)$user['ativo'] || !password_verify($senha, $user['senha_hash'])) {
  usleep(300000);
  json_response(['success' => false, 'message' => 'E-mail ou senha inválidos.'], 401);
}

session_regenerate_id(true);
$_SESSION['admin_user'] = [
  'id' => (int)$user['id'],
  'nome' => $user['nome'],
  'email' => $user['email'],
  'tipo' => $user['tipo'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$update = db()->prepare('UPDATE usuarios_admin SET ultimo_login_em = NOW() WHERE id = ?');
$update->execute([(int)$user['id']]);

json_response([
  'success' => true,
  'message' => 'Login realizado com sucesso.',
  'csrfToken' => $_SESSION['csrf_token'],
  'user' => $_SESSION['admin_user'],
]);
