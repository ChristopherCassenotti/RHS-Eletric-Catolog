<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

if (!current_admin()) {
  $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'admin-produtos.php');
  header('Location: admin-login.html?redirect=' . $redirect);
  exit;
}
