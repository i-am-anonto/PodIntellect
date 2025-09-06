<?php
// quiz_results.php
session_start();
$quiz = $_SESSION['quiz'] ?? null;
$answers = $_SESSION['answers'] ?? [];
if (!$quiz || empty($quiz['questions'])) {
    http_response_code(400);
    echo "No quiz found.";
    exit;
}

$total = count($quiz['questions']);
$correctCount = 0;
$rows = [];

foreach ($quiz['questions'] as $q) {
    $qid = $q['id'];
    $user = $answers[$qid] ?? -1;
    $isCorrect = ($user === $q['answer']);
    if ($isCorrect) $correctCount++;
    $rows[] = [
        'q' => $q['question'],
        'choices' => $q['choices'],
        'user' => $user,
        'correct' => $q['answer'],
        'explanation' => $q['explanation'],
        'isCorrect' => $isCorrect,
    ];
}
$scorePct = $total ? round(($correctCount / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PodIntellect - Quiz Results</title>
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
    <div class="flex items-end justify-between">
      <h2 class="text-4xl font-bold">Results</h2>
      <div class="text-3xl font-extrabold <?php echo $scorePct >= 70 ? 'text-green-400' : 'text-amber-400'; ?>">
        <?php echo $correctCount; ?>/<?php echo $total; ?> (<?php echo $scorePct; ?>%)
      </div>
    </div>

    <?php foreach ($rows as $i => $r): ?>
      <section class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-semibold mb-3">Q<?php echo ($i+1); ?>. <?php echo htmlspecialchars($r['q']); ?></h3>
          <span class="px-3 py-1 rounded text-sm <?php echo $r['isCorrect'] ? 'bg-green-700' : 'bg-amber-700'; ?>">
            <?php echo $r['isCorrect'] ? 'Correct' : 'Review'; ?>
          </span>
        </div>
        <ul class="list-disc pl-6 space-y-1 text-gray-200">
          <?php foreach ($r['choices'] as $idx => $c): ?>
            <li>
              <?php
                $isUser = ($idx === $r['user']);
                $isRight = ($idx === $r['correct']);
                $badge = '';
                if ($isRight) $badge = ' ✅';
                elseif ($isUser && !$isRight) $badge = ' ✖';
                echo htmlspecialchars($c) . $badge;
              ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="text-sm text-gray-400 mt-3"><span class="text-gray-300">Explanation:</span> <?php echo htmlspecialchars($r['explanation']); ?></p>
      </section>
    <?php endforeach; ?>

    <div class="flex items-center justify-end gap-3">
      <form method="post" action="quiz_take.php">
        <button class="px-4 py-2 bg-gray-700 rounded">Review in Player</button>
      </form>
      <form method="post" action="quiz_generate.php">
        <input type="hidden" name="transcript" value="<?php echo htmlspecialchars($_POST['transcript'] ?? ''); ?>">
        <input type="hidden" name="video_title" value="<?php echo htmlspecialchars($_POST['video_title'] ?? ''); ?>">
        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded">New Quiz</button>
      </form>
    </div>
  </div>
</main>

<footer class="blue-gradient py-6 px-6 mt-12">
  <div class="container mx-auto text-sm text-gray-200">© 2024 PodIntellect</div>
</footer>
</body>
</html>
