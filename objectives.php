<?php
// objectives.php
require_once __DIR__ . '/ai_utils.php';

$transcript = $_POST['transcript'] ?? '';
$videoTitle = $_POST['video_title'] ?? '';
if (!$transcript) {
    http_response_code(400);
    echo "No transcript provided.";
    exit;
}

$count = isset($_POST['count']) ? max(3, min(12, (int)$_POST['count'])) : 6;
$objectives = AIUtils::learningObjectives($transcript, $count);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PodIntellect - Learning Objectives</title>
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
    <h2 class="text-4xl font-bold">Learning Objectives<?php if($videoTitle) echo ": " . htmlspecialchars($videoTitle); ?></h2>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <form method="POST" class="flex items-end gap-3">
        <div>
          <label class="block text-sm text-gray-400 mb-1">How many objectives?</label>
          <input type="number" name="count" value="<?php echo (int)$count; ?>" min="3" max="12"
                 class="bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white w-28">
        </div>
        <input type="hidden" name="transcript" value="<?php echo htmlspecialchars($transcript); ?>">
        <input type="hidden" name="video_title" value="<?php echo htmlspecialchars($videoTitle); ?>">
        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded">Regenerate</button>
      </form>
    </section>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <h3 class="text-xl font-semibold text-green-400 mb-4">Objectives</h3>
      <ol class="list-decimal pl-6 space-y-2 text-gray-200">
        <?php foreach ($objectives as $o): ?>
          <li><?php echo htmlspecialchars($o); ?></li>
        <?php endforeach; ?>
      </ol>
    </section>

    <form method="post" action="quiz_generate.php" class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <input type="hidden" name="transcript" value="<?php echo htmlspecialchars($transcript); ?>">
      <input type="hidden" name="video_title" value="<?php echo htmlspecialchars($videoTitle); ?>">
      <div class="flex items-center justify-between">
        <p class="text-gray-300">Ready to turn this into a quiz?</p>
        <button class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg">Generate Quiz</button>
      </div>
    </form>
  </div>
</main>

<footer class="blue-gradient py-6 px-6 mt-12">
  <div class="container mx-auto text-sm text-gray-200">© 2024 PodIntellect</div>
</footer>
</body>
</html>
