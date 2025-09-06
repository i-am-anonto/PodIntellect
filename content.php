<?php
// content.php — generate content kit + save to history
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/ai_utils.php';

$transcript = $_POST['transcript'] ?? '';
$videoTitle = $_POST['video_title'] ?? '';
if (!$transcript) {
    http_response_code(400);
    echo "No transcript provided.";
    exit;
}

$kit = AIUtils::contentKit($transcript);

// ---- Save to history (if logged in) ----
if (current_user()) {
    $uid   = (int) current_user()['id'];
    $title = $videoTitle !== '' ? $videoTitle : 'Generated Content';
    $data  = [
        'title'       => $title,
        'url'         => $_POST['podcast_url'] ?? ($_POST['url'] ?? null),
        'titles'      => $kit['titles']      ?? [],
        'tldr'        => $kit['tldr']        ?? [],
        'thread'      => $kit['thread']      ?? [],
        'linkedin'    => $kit['linkedin']    ?? '',
        'description' => $kit['description'] ?? '',
        'tags'        => $kit['tags']        ?? [],
    ];
    record_history($uid, 'content', $title, $data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PodIntellect - Content Kit</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .blue-gradient { background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 50%,#1e40af 100%); }
  </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">

<?php include __DIR__ . '/header.php'; ?> <!-- login/signup modal -->

<main class="flex-1 px-6 py-10">
  <div class="max-w-5xl mx-auto space-y-8">
    <?php if ($videoTitle): ?>
      <h2 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($videoTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php endif; ?>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-green-400 mb-3">Title Ideas</h3>
      <ul class="list-disc pl-6 space-y-1 text-gray-200">
        <?php foreach (($kit['titles'] ?? []) as $t): ?>
          <li><?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-blue-400 mb-3">TL;DR (for description or show notes)</h3>
      <ul class="list-disc pl-6 space-y-1 text-gray-200">
        <?php foreach (($kit['tldr'] ?? []) as $b): ?>
          <li><?php echo htmlspecialchars($b, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-purple-400 mb-3">X (Twitter) Thread</h3>
      <ol class="list-decimal pl-6 space-y-1 text-gray-200">
        <?php foreach (($kit['thread'] ?? []) as $tw): ?>
          <li><?php echo htmlspecialchars($tw, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ol>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-amber-400 mb-3">LinkedIn Post</h3>
      <pre class="whitespace-pre-wrap text-gray-200"><?php echo htmlspecialchars($kit['linkedin'] ?? '', ENT_QUOTES, 'UTF-8'); ?></pre>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-pink-400 mb-3">YouTube/Podcast Description</h3>
      <p class="text-gray-200"><?php echo htmlspecialchars($kit['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="mt-3">
        <span class="text-gray-400 text-sm">Tags:</span>
        <?php foreach (($kit['tags'] ?? []) as $tag): ?>
          <span class="ml-1 px-2 py-0.5 bg-gray-800 rounded text-xs border border-gray-700">
            <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<footer class="blue-gradient py-6 px-6 mt-12">
  <div class="container mx-auto text-sm text-gray-200">© 2024 PodIntellect</div>
</footer>
</body>
</html>
