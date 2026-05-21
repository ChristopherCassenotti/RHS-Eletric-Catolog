<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$admin = current_admin();
if (!$admin) {
  json_response(['success' => false, 'logged' => false], 401);
}

json_response([
  'success' => true,
  'logged' => true,
  'user' => [
    'id' => $admin['id'],
    'nome' => $admin['nome'],
    'email' => $admin['email'],
    'tipo' => $admin['tipo'],
  ],
  'csrfToken' => $_SESSION['csrf_token'],
]);
