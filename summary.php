<?php
// summary.php — generate summary + save to history
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

$summary_long  = AIUtils::summarize($transcript, 0.22, 12);
$summary_short = AIUtils::summarize($transcript, 0.10, 5);
$phrases       = AIUtils::keyPhrases($transcript, 12);

// ---- Save to history (if logged in) ----
if (current_user()) {
    $uid   = (int) current_user()['id'];
    $title = $videoTitle !== '' ? $videoTitle : 'Podcast Summary';
    $data  = [
        'title'        => $title,
        'summary_long' => $summary_long,
        'summary_tldr' => $summary_short,
        'phrases'      => $phrases,
        // If you also pass the original URL somewhere, include it here:
        'url'          => $_POST['podcast_url'] ?? ($_POST['url'] ?? null),
    ];
    record_history($uid, 'summary', $title, $data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PodIntellect - Summary</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .blue-gradient { background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 50%,#1e40af 100%); }
  </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">

<?php include __DIR__ . '/header.php'; ?> <!-- login/signup modal (hidden until opened) -->

<main class="flex-1 px-6 py-10">
  <div class="max-w-4xl mx-auto space-y-8">
    <h2 class="text-4xl font-bold">
      Summary<?php if($videoTitle) echo ": " . htmlspecialchars($videoTitle, ENT_QUOTES, 'UTF-8'); ?>
    </h2>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-green-400 mb-3">TL;DR</h3>
      <p class="text-gray-200 leading-7"><?php echo nl2br(htmlspecialchars($summary_short, ENT_QUOTES, 'UTF-8')); ?></p>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-blue-400 mb-3">Detailed Summary</h3>
      <p class="text-gray-200 leading-7"><?php echo nl2br(htmlspecialchars($summary_long, ENT_QUOTES, 'UTF-8')); ?></p>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-purple-400 mb-3">Key Phrases</h3>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($phrases as $p): ?>
          <span class="px-3 py-1 bg-gray-800 rounded-lg text-sm border border-gray-700">
            <?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </section>

    <form method="post" action="content.php" class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <input type="hidden" name="transcript"   value="<?php echo htmlspecialchars($transcript, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="video_title"  value="<?php echo htmlspecialchars($videoTitle, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="podcast_url"  value="<?php echo htmlspecialchars($_POST['podcast_url'] ?? ($_POST['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="flex items-center justify-between">
        <p class="text-gray-300">Want social & SEO content too?</p>
        <button class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg">Generate Content Kit</button>
      </div>
    </form>
  </div>
</main>

<footer class="blue-gradient py-6 px-6 mt-12">
  <div class="container mx-auto text-sm text-gray-200">© 2024 PodIntellect</div>
</footer>
</body>
</html>
