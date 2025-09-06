<?php
// share.php
require_once __DIR__ . '/helpers.php';

$token = $_GET['t'] ?? '';
$item = null;
if ($token && preg_match('/^[a-f0-9]{40}$/', $token)) {
  $item = find_shared_item($token);
}

include __DIR__ . '/partials/header.php';
?>
<main class="container">
  <?php if (!$item): ?>
    <section class="card">
      <h2>Link not found</h2>
      <p class="muted">This share link may be invalid or expired.</p>
    </section>
  <?php else: ?>
    <section class="card">
      <div class="kicker">Shared <?= sanitize($item['item_type']) ?></div>
      <?php if ($item['title']): ?>
        <h2><?= sanitize($item['title']) ?></h2>
      <?php endif; ?>
      <p class="muted"><?= sanitize($item['created_at']) ?></p>
      <pre style="white-space:pre-wrap; background:#0d1626; padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.06)"><?= sanitize($item['data']) ?></pre>
    </section>
  <?php endif; ?>
</main>
</body></html>
