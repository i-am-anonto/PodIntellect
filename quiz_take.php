<?php
// quiz_take.php
session_start();
$quiz = $_SESSION['quiz'] ?? null;
if (!$quiz || empty($quiz['questions'])) {
    http_response_code(400);
    echo "No quiz found. Please generate a quiz first.";
    exit;
}
$idx = $_SESSION['idx'] ?? 0;
$answers = $_SESSION['answers'] ?? [];

// Handle navigation / answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $choice = isset($_POST['choice']) ? (int)$_POST['choice'] : null;

    // Save answer for current question if provided
    if ($choice !== null) {
        $qId = $quiz['questions'][$idx]['id'];
        $answers[$qId] = $choice;
        $_SESSION['answers'] = $answers;
    }

    if ($action === 'prev') {
        $idx = max(0, $idx - 1);
    } elseif ($action === 'next') {
        $idx = min(count($quiz['questions']) - 1, $idx + 1);
    } elseif ($action === 'finish') {
        $_SESSION['idx'] = $idx;
        header('Location: quiz_results.php');
        exit;
    }
    $_SESSION['idx'] = $idx;
}

// Calculate progress
$total = count($quiz['questions']);
$progress = (int) floor((($idx) / $total) * 100);
$current = $quiz['questions'][$idx];
$selected = $answers[$current['id']] ?? -1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PodIntellect - Take Quiz</title>
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
  <div class="max-w-3xl mx-auto space-y-6">
    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($quiz['title'] ?? 'Quiz'); ?></h2>

    <div class="w-full bg-gray-800 h-3 rounded">
      <div class="bg-green-500 h-3 rounded" style="width: <?php echo $progress; ?>%;"></div>
    </div>
    <div class="text-sm text-gray-400"><?php echo ($idx+1) . " / " . $total; ?></div>

    <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
      <div class="font-semibold mb-4"><?php echo htmlspecialchars($current['question']); ?></div>
      <form method="POST" class="space-y-3">
        <?php foreach ($current['choices'] as $i => $c): ?>
          <label class="flex items-start gap-3 p-3 bg-gray-800 rounded border border-gray-700 cursor-pointer">
            <input type="radio" name="choice" value="<?php echo $i; ?>" <?php if ($selected === $i) echo 'checked'; ?>>
            <span><?php echo htmlspecialchars($c); ?></span>
          </label>
        <?php endforeach; ?>

        <div class="flex justify-between mt-6">
          <button name="action" value="prev" class="px-4 py-2 bg-gray-700 rounded <?php echo $idx === 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $idx === 0 ? 'disabled' : ''; ?>>Previous</button>
          <div class="space-x-3">
            <?php if ($idx < $total - 1): ?>
              <button name="action" value="next" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded">Next</button>
            <?php else: ?>
              <button name="action" value="finish" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded">Finish</button>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </section>
  </div>
</main>

<footer class="blue-gradient py-6 px-6 mt-12">
  <div class="container mx-auto text-sm text-gray-200">© 2024 PodIntellect</div>
</footer>
</body>
</html>
