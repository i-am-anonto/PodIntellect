<?php
// auth.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($method === 'POST' && in_array($action, ['login','register'], true)) {
  header('Content-Type: application/json; charset=utf-8');

  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  if ($action === 'register') {
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '' || $email === '' || $password === '') {
      http_response_code(400);
      echo json_encode(['ok'=>false, 'error'=>'All fields are required.']);
      exit;
    }
    // check uniqueness
    $exists = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $exists->execute([$email]);
    if ($exists->fetch()) {
      http_response_code(409);
      echo json_encode(['ok'=>false, 'error'=>'Email already registered.']);
      exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = db()->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $ins->execute([$name, $email, $hash]);

    $uid = (int)db()->lastInsertId();
    $_SESSION['user'] = ['id'=>$uid, 'name'=>$name, 'email'=>$email];
    echo json_encode(['ok'=>true, 'redirect'=> BASE_URL.'/dashboard.php']);
    exit;
  }

  if ($action === 'login') {
    if ($email === '' || $password === '') {
      http_response_code(400);
      echo json_encode(['ok'=>false, 'error'=>'Email and password are required.']);
      exit;
    }
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
      http_response_code(401);
      echo json_encode(['ok'=>false, 'error'=>'Invalid credentials.']);
      exit;
    }
    $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email']];
    echo json_encode(['ok'=>true, 'redirect'=> BASE_URL.'/dashboard.php']);
    exit;
  }
}

if ($method === 'GET' && $action === 'logout') {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
  header('Location: ' . BASE_URL . '/index.php');
  exit;
}

http_response_code(405);
echo 'Method/Action not allowed';
