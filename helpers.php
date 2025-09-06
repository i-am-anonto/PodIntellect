<?php
// helpers.php
require_once __DIR__ . '/db.php';

function current_user() {
  return $_SESSION['user'] ?? null; // ['id'=>..., 'name'=>..., 'email'=>...]
}

function require_login() {
  if (!current_user()) {
    header('Location: ' . BASE_URL . '/index.php?auth=1');
    exit;
  }
}

function sanitize($str) {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function record_history(int $user_id, string $item_type, string $title, $data): int {
  // $data can be array/object/string; we store as JSON string if array/object
  if (is_array($data) || is_object($data)) {
    $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  $stmt = db()->prepare("INSERT INTO history (user_id, item_type, title, data) VALUES (?, ?, ?, ?)");
  $stmt->execute([$user_id, $item_type, $title, (string)$data]);
  return (int)db()->lastInsertId();
}

function get_user_history(int $user_id, ?string $type = null, int $limit = 100) {
  if ($type) {
    $stmt = db()->prepare("SELECT * FROM history WHERE user_id=? AND item_type=? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $type, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
  } else {
    $stmt = db()->prepare("SELECT * FROM history WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
  }
  return $stmt->fetchAll();
}

function create_share_link(int $history_id): string {
  // Generate deterministic-unique token
  $token = bin2hex(random_bytes(20)); // 40-char hex
  $stmt = db()->prepare("INSERT INTO shares (history_id, token) VALUES (?, ?)");
  $stmt->execute([$history_id, $token]);
  return BASE_URL . '/share.php?t=' . $token;
}

function find_shared_item(string $token) {
  $stmt = db()->prepare("SELECT h.* FROM shares s JOIN history h ON h.id = s.history_id WHERE s.token = ? LIMIT 1");
  $stmt->execute([$token]);
  return $stmt->fetch();
}
