<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/ai_utils.php';

// Require authentication
require_login();

$transcript = $_POST['transcript'] ?? '';
$videoTitle = $_POST['video_title'] ?? '';
$numQ = isset($_POST['numQ']) ? max(3, min(20, (int)$_POST['numQ'])) : 8;
$difficulty = $_POST['difficulty'] ?? 'mixed';
$questionTypes = $_POST['question_types'] ?? ['all'];

if (!$transcript) {
    http_response_code(400);
    echo "No transcript provided.";
    exit;
}

// Generate enhanced quiz
$quiz = AIUtils::generateQuiz($transcript, $numQ);
if ($videoTitle) {
    $quiz['title'] = $videoTitle . ' — Quiz';
}

// Store quiz in session for the flow
$_SESSION['quiz'] = $quiz;
$_SESSION['answers'] = [];
$_SESSION['idx'] = 0;

// Save to history (if logged in)
if (current_user()) {
    $uid = (int) current_user()['id'];
    $title = $quiz['title'] ?? ($videoTitle ? ($videoTitle . ' — Quiz') : 'Quiz');
    $quizData = $quiz;
    if (!isset($quizData['url'])) {
        $quizData['url'] = $_POST['podcast_url'] ?? ($_POST['url'] ?? null);
    }
    record_history($uid, 'quiz', $title, $quizData);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Generator - PodIntellect</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body { 
      font-family: 'Inter', sans-serif; 
      background: linear-gradient(180deg, #000000 0%, #0a0a0a 50%, #111111 100%);
    }
    
    .neon-green { 
      color:#00ff41; 
      text-shadow:0 0 5px #00ff41,0 0 10px #00ff41,0 0 15px #00ff41,0 0 20px #00ff41; 
    }
    
    .blue-gradient { 
      background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 50%,#1e40af 100%); 
    }
    
    @keyframes slideInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .animate-slide-up { animation: slideInUp 0.8s ease-out; }
    .animate-fade-in { animation: fadeIn 1s ease-out; }
    .animate-pulse { animation: pulse 2s ease-in-out infinite; }
    
    .card-hover {
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }
    
    .card-hover:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 25px 50px rgba(0,0,0,0.4);
    }
    
    .question-card {
      transition: all 0.3s ease;
    }
    
    .question-card:hover {
      background: rgba(255,255,255,0.05);
      transform: translateX(5px);
    }
    
    .difficulty-easy { border-left: 4px solid #10b981; }
    .difficulty-medium { border-left: 4px solid #f59e0b; }
    .difficulty-hard { border-left: 4px solid #ef4444; }
    
    .type-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 0.375rem;
      font-weight: 500;
    }
    
    .type-cloze { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    .type-definition { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .type-purpose { background: rgba(168, 85, 247, 0.2); color: #a78bfa; }
    .type-comparison { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    .type-application { background: rgba(236, 72, 153, 0.2); color: #f472b6; }
    .type-cause_effect { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .type-sequence { background: rgba(6, 182, 212, 0.2); color: #22d3ee; }
    .type-evaluation { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .type-factual { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .type-inferential { background: rgba(251, 146, 60, 0.2); color: #fb923c; }
    .type-analytical { background: rgba(220, 38, 127, 0.2); color: #ec4899; }
    
    .progress-bar {
      background: linear-gradient(90deg, #00ff41 0%, #00cc33 100%);
      transition: width 0.3s ease;
    }
    
    .stats-card {
      background: linear-gradient(135deg, rgba(0,255,65,0.1) 0%, rgba(0,255,65,0.05) 100%);
      border: 1px solid rgba(0,255,65,0.2);
    }
  </style>
</head>
<body class="bg-black text-white min-h-screen">

  <?php include __DIR__ . '/header.php'; ?>

  <!-- Navigation Bar -->
  <nav class="bg-gray-900/80 backdrop-blur-lg border-b border-gray-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-2">
          <h1 class="text-2xl font-bold neon-green">PodIntellect</h1>
          <span class="text-gray-400">Quiz Generator</span>
        </div>
        <div class="flex items-center space-x-6">
          <a href="index.php" class="text-gray-300 hover:text-white transition-colors">Home</a>
          <a href="dashboard.php" class="text-gray-300 hover:text-white transition-colors">Dashboard</a>
          <a href="history.php" class="text-gray-300 hover:text-white transition-colors">History</a>
        </div>
      </div>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto px-6 py-8">
    <!-- Header Section -->
    <section class="mb-8 animate-slide-up">
      <div class="bg-gray-900/80 backdrop-blur-lg rounded-xl p-8 border border-gray-800 shadow-2xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h2 class="text-3xl font-bold mb-2 flex items-center">
              <svg class="w-8 h-8 mr-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548-.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
              </svg>
              AI-Generated Quiz
            </h2>
            <p class="text-gray-400">
              <?php if ($videoTitle): ?>
                Based on: <span class="text-white font-medium"><?= htmlspecialchars($videoTitle) ?></span>
              <?php else: ?>
                Generated from podcast transcript
              <?php endif; ?>
            </p>
          </div>
          
          <!-- Quiz Stats -->
          <div class="flex space-x-4 mt-4 md:mt-0">
            <div class="stats-card rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-green-400"><?= count($quiz['questions']) ?></div>
              <div class="text-xs text-gray-400">Questions</div>
            </div>
            <div class="stats-card rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-blue-400"><?= count($quiz['metadata']['question_types'] ?? []) ?></div>
              <div class="text-xs text-gray-400">Types</div>
            </div>
            <div class="stats-card rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-purple-400"><?= $quiz['metadata']['total_concepts'] ?? 0 ?></div>
              <div class="text-xs text-gray-400">Concepts</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Quiz Configuration -->
    <section class="mb-8 animate-slide-up" style="animation-delay: 0.1s;">
      <div class="bg-gray-900/80 backdrop-blur-lg rounded-xl p-6 border border-gray-800 shadow-2xl">
        <h3 class="text-xl font-semibold mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
          </svg>
          Quiz Configuration
        </h3>
        
        <form method="POST" class="space-y-4">
          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-400 mb-2">Number of Questions</label>
              <input type="number" name="numQ" value="<?= $numQ ?>" min="3" max="20"
                     class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-green-400 focus:outline-none transition-colors">
            </div>
            <div>
              <label class="block text-sm text-gray-400 mb-2">Difficulty Level</label>
              <select name="difficulty" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-green-400 focus:outline-none">
                <option value="mixed" <?= $difficulty === 'mixed' ? 'selected' : '' ?>>Mixed</option>
                <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
              </select>
            </div>
            <div class="flex items-end">
              <button type="submit" class="w-full px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 text-white font-semibold rounded-lg hover:from-purple-600 hover:to-blue-600 transition-all duration-300">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Regenerate Quiz
              </button>
            </div>
          </div>
          
          <input type="hidden" name="transcript" value="<?= htmlspecialchars($transcript, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="video_title" value="<?= htmlspecialchars($videoTitle, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="podcast_url" value="<?= htmlspecialchars($_POST['podcast_url'] ?? ($_POST['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </form>
      </div>
    </section>

    <!-- Quiz Preview -->
    <section class="mb-8 animate-slide-up" style="animation-delay: 0.2s;">
      <div class="bg-gray-900/80 backdrop-blur-lg rounded-xl p-8 border border-gray-800 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-2xl font-semibold text-green-400"><?= htmlspecialchars($quiz['title'] ?? 'Quiz', ENT_QUOTES, 'UTF-8') ?></h3>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-400">Difficulty Distribution:</span>
            <?php if (isset($quiz['metadata']['difficulty_distribution'])): ?>
              <?php foreach ($quiz['metadata']['difficulty_distribution'] as $diff => $count): ?>
                <span class="px-2 py-1 bg-gray-700 text-xs rounded <?= $diff === 'easy' ? 'text-green-400' : ($diff === 'medium' ? 'text-yellow-400' : 'text-red-400') ?>">
                  <?= ucfirst($diff) ?>: <?= $count ?>
                </span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        
        <?php if (empty($quiz['questions'])): ?>
          <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-400 mb-2">No questions generated</h3>
            <p class="text-gray-500">Couldn't generate questions from this transcript. Try with a longer or more detailed transcript.</p>
          </div>
        <?php else: ?>
          <div class="space-y-6">
            <?php foreach ($quiz['questions'] as $index => $q): ?>
              <div class="question-card p-6 bg-gray-800/50 rounded-lg border border-gray-700/50 animate-fade-in" 
                   style="animation-delay: <?= 0.1 * ($index + 1) ?>s;">
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center space-x-3">
                    <span class="w-8 h-8 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center text-sm font-bold">
                      <?= $index + 1 ?>
                    </span>
                    <div>
                      <div class="flex items-center space-x-2">
                        <span class="type-badge type-<?= $q['type'] ?? 'definition' ?>">
                          <?= ucfirst(str_replace('_', ' ', $q['type'] ?? 'definition')) ?>
                        </span>
                        <span class="px-2 py-1 bg-gray-700 text-xs rounded <?= $q['difficulty'] === 'easy' ? 'text-green-400' : ($q['difficulty'] === 'medium' ? 'text-yellow-400' : 'text-red-400') ?>">
                          <?= ucfirst($q['difficulty'] ?? 'medium') ?>
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <button class="text-gray-400 hover:text-white transition-colors" onclick="toggleQuestion(<?= $index ?>)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                </div>
                
                <div class="question-content">
                  <h4 class="text-lg font-medium mb-4 text-gray-200"><?= htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8') ?></h4>
                  
                  <div class="grid gap-2 mb-4">
                    <?php foreach ($q['choices'] as $i => $choice): ?>
                      <div class="flex items-center space-x-3 p-3 bg-gray-700/50 rounded-lg">
                        <span class="w-6 h-6 bg-gray-600 rounded-full flex items-center justify-center text-xs font-bold">
                          <?= chr(65 + $i) ?>
                        </span>
                        <span class="text-gray-300">
                          <?= htmlspecialchars($choice, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <details class="group">
                    <summary class="cursor-pointer text-blue-400 hover:text-blue-300 transition-colors text-sm font-medium list-none flex items-center">
                      <svg class="w-4 h-4 mr-1 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                      Show explanation
                    </summary>
                    <div class="mt-3 p-4 bg-gray-700/70 backdrop-blur rounded-lg border border-gray-600">
                      <p class="text-sm text-gray-300"><?= htmlspecialchars($q['explanation'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                  </details>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Action Buttons -->
    <section class="animate-slide-up" style="animation-delay: 0.3s;">
      <div class="bg-gray-900/80 backdrop-blur-lg rounded-xl p-6 border border-gray-800 shadow-2xl">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <div class="text-center md:text-left">
            <h3 class="text-xl font-semibold mb-2">Ready to test your knowledge?</h3>
            <p class="text-gray-400">Start the interactive quiz experience and track your progress.</p>
          </div>
          
          <div class="flex space-x-4">
            <a href="index.php" class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Back to Home
            </a>
            
            <?php if (!empty($quiz['questions'])): ?>
              <form method="post" action="quiz_take.php" class="inline">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all duration-300 animate-pulse">
                  <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                  </svg>
                  Start Quiz
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="blue-gradient py-8 px-6 mt-16">
    <div class="container mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="flex items-center space-x-2 mb-4 md:mb-0">
          <h2 class="text-2xl font-bold neon-green">PodIntellect</h2>
          <span class="text-sm text-gray-300">© 2024 All rights reserved</span>
        </div>
        <div class="flex space-x-6 text-sm">
          <a href="#" class="text-white hover:text-gray-300 transition-colors">Privacy Policy</a>
          <a href="#" class="text-white hover:text-gray-300 transition-colors">Terms of Service</a>
          <a href="#" class="text-white hover:text-gray-300 transition-colors">Support</a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Toggle question details
    function toggleQuestion(index) {
      const question = document.querySelectorAll('.question-card')[index];
      const content = question.querySelector('.question-content');
      const button = question.querySelector('button svg');
      
      if (content.style.display === 'none') {
        content.style.display = 'block';
        button.style.transform = 'rotate(180deg)';
      } else {
        content.style.display = 'none';
        button.style.transform = 'rotate(0deg)';
      }
    }

    // Add smooth entrance animations
    document.addEventListener('DOMContentLoaded', function() {
      const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -20px 0px'
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, observerOptions);

      // Observe all cards
      document.querySelectorAll('.card-hover, .question-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.8s ease';
        observer.observe(card);
      });

      // Add progress bar animation
      const progressBars = document.querySelectorAll('.progress-bar');
      progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
          bar.style.width = width;
        }, 500);
      });
    });

    // Add copy functionality for quiz data
    function copyQuizData() {
      const quizData = {
        title: '<?= addslashes($quiz['title'] ?? 'Quiz') ?>',
        questions: <?= json_encode($quiz['questions']) ?>,
        metadata: <?= json_encode($quiz['metadata'] ?? []) ?>
      };
      
      navigator.clipboard.writeText(JSON.stringify(quizData, null, 2)).then(() => {
        // Show success message
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
          button.textContent = originalText;
          button.classList.remove('bg-green-600');
        }, 2000);
      });
    }
  </script>
</body>
</html>
