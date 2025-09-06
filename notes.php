<?php
// notes.php
require_once __DIR__ . '/ai_utils.php';

$transcript = $_POST['transcript'] ?? '';
$videoTitle = $_POST['video_title'] ?? '';
if (!$transcript) {
    http_response_code(400);
    echo "No transcript provided.";
    exit;
}

$notes = AIUtils::makeNotes($transcript, 10);
$phrases = AIUtils::keyPhrases($transcript, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PodIntellect - Notes</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
<header class="blue-gradient py-4 px-6 shadow-lg">
  <div class="container mx-auto flex justify-between items-center">
    <div class="flex items-center space-x-2">
      <h1 class="text-3xl font-bold" style="color:#00ff41;">PodIntellect</h1>
      <span class="text-sm text-gray-300">AI-Powered Podcast Summaries</span>
    </div>
    <nav class="hidden md:flex space-x-6">
      <a href="index.php" class="text-white hover:text-gray-300">Home</a>
      <a href="transcribe_supadata.php" class="text-white hover:text-gray-300">Transcriptions</a>
    </nav>
  </div>
</header>

<main class="flex-1 px-6 py-10">
  <div class="max-w-4xl mx-auto space-y-8">
    <h2 class="text-4xl font-bold">Notes<?php if($videoTitle) echo ": " . htmlspecialchars($videoTitle); ?></h2>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-green-400 mb-4">Actionable Takeaways</h3>
      <ul class="list-disc pl-6 space-y-2 text-gray-200">
        <?php foreach ($notes as $n): ?>
          <li><?php echo htmlspecialchars($n); ?></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-blue-400 mb-3">Topics / Keywords</h3>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($phrases as $p): ?>
          <span class="px-3 py-1 bg-gray-800 rounded-lg text-sm border border-gray-700"><?php echo htmlspecialchars($p); ?></span>
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
