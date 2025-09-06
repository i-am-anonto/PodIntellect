<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Require authentication
require_login();

$user = current_user();

$type = isset($_GET['type']) ? $_GET['type'] : null; // summary/content/quiz
$items = get_user_history((int)$user['id'], $type, 50);

// Handle share POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_id'])) {
  $hid = (int)$_POST['share_id'];
  // optionally validate ownership
  $owns = false;
  foreach ($items as $it) if ((int)$it['id'] === $hid) $owns = true;
  if ($owns) {
    $link = create_share_link($hid);
    $_SESSION['share_link'] = $link;
    header("Location: " . BASE_URL . "/history.php?type=" . urlencode((string)$type) . "#shared");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History - PodIntellect</title>
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
    
    .animate-slide-up { animation: slideInUp 0.8s ease-out; }
    .animate-fade-in { animation: fadeIn 1s ease-out; }
    
    .card-hover {
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }
    
    .card-hover:hover {
      transform: translateY(-5px) scale(1.01);
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    
    .history-item {
      transition: all 0.3s ease;
    }
    
    .history-item:hover {
      background: rgba(255,255,255,0.05);
      transform: translateX(5px);
    }
    
    .filter-btn {
      transition: all 0.3s ease;
    }
    
    .filter-btn.active {
      background: linear-gradient(135deg, #00ff41 0%, #00cc33 100%);
      color: #001100;
      box-shadow: 0 0 20px rgba(0,255,65,0.3);
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
          <span class="text-gray-400">History</span>
        </div>
        <div class="flex items-center space-x-6">
          <a href="index.php" class="text-gray-300 hover:text-white transition-colors">Home</a>
          <a href="dashboard.php" class="text-gray-300 hover:text-white transition-colors">Dashboard</a>
          <div class="flex items-center space-x-3">
            <span class="text-white font-medium"><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
            <a href="auth.php?action=logout" class="text-red-400 hover:text-red-300 transition-colors">Logout</a>
          </div>
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
              <svg class="w-8 h-8 mr-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Your Activity History
            </h2>
            <p class="text-gray-400">
              <?php if ($type): ?>
                Showing <span class="text-white font-medium"><?= ucfirst($type) ?></span> items
              <?php else: ?>
                All your podcast analysis activities
              <?php endif; ?>
            </p>
          </div>
          
          <!-- Filter Buttons -->
          <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
            <a href="<?= BASE_URL ?>/history.php" 
               class="filter-btn px-4 py-2 rounded-lg <?= !$type ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
              All
            </a>
            <a href="<?= BASE_URL ?>/history.php?type=summary" 
               class="filter-btn px-4 py-2 rounded-lg <?= $type === 'summary' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
              Summaries
            </a>
            <a href="<?= BASE_URL ?>/history.php?type=content" 
               class="filter-btn px-4 py-2 rounded-lg <?= $type === 'content' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
              Content
            </a>
            <a href="<?= BASE_URL ?>/history.php?type=quiz" 
               class="filter-btn px-4 py-2 rounded-lg <?= $type === 'quiz' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
              Quizzes
            </a>
          </div>
        </div>
        
        <?php if (!empty($_SESSION['share_link'])): ?>
          <div id="shared" class="mt-6 p-4 bg-green-900/20 border border-green-600/30 rounded-lg animate-fade-in">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-green-300">Share link created successfully!</span>
            </div>
            <div class="mt-2 flex items-center space-x-2">
              <input type="text" value="<?= htmlspecialchars($_SESSION['share_link']) ?>" 
                     class="flex-1 px-3 py-2 bg-gray-800 border border-gray-600 rounded text-white text-sm" readonly>
              <button onclick="copyShareLink()" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors text-sm">
                Copy
              </button>
            </div>
          </div>
          <?php unset($_SESSION['share_link']); ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- History Items -->
    <?php if (!$items): ?>
      <section class="animate-slide-up" style="animation-delay: 0.2s;">
        <div class="bg-gray-900/80 backdrop-blur-lg rounded-xl p-12 border border-gray-800 shadow-2xl text-center">
          <svg class="w-20 h-20 text-gray-600 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="text-2xl font-medium text-gray-400 mb-3">No items found</h3>
          <p class="text-gray-500 mb-8">
            <?php if ($type): ?>
              You haven't created any <?= $type ?> items yet.
            <?php else: ?>
              You haven't analyzed any podcasts yet.
            <?php endif; ?>
          </p>
          <a href="index.php" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Start Your First Analysis
          </a>
        </div>
      </section>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($items as $index => $item): ?>
          <section class="bg-gray-900/80 backdrop-blur-lg rounded-xl p-6 border border-gray-800 shadow-2xl card-hover animate-slide-up" 
                   style="animation-delay: <?= 0.1 * ($index + 1) ?>s;">
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-start space-x-4 flex-1">
                <!-- Type Icon -->
                <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center
                          <?php
                          echo match($item['item_type']) {
                            'summary', 'url_submission' => 'bg-gradient-to-r from-green-400 to-emerald-500',
                            'content' => 'bg-gradient-to-r from-blue-400 to-blue-600',
                            'quiz' => 'bg-gradient-to-r from-purple-400 to-purple-600',
                            default => 'bg-gradient-to-r from-gray-400 to-gray-600'
                          };
                          ?>">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php
                    echo match($item['item_type']) {
                      'summary', 'url_submission' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
                      'content' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>',
                      'quiz' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                      default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'
                    };
                    ?>
                  </svg>
                </div>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-3 mb-2">
                    <h3 class="text-lg font-semibold truncate"><?= htmlspecialchars($item['title'] ?? 'Untitled') ?></h3>
                    <span class="px-2 py-1 bg-gray-700 text-gray-300 rounded-full text-xs font-medium">
                      <?= ucfirst($item['item_type']) ?>
                    </span>
                  </div>
                  
                  <div class="flex items-center text-gray-400 text-sm mb-3">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?= date('M j, Y \a\t g:i A', strtotime($item['created_at'])) ?>
                  </div>
                  
                  <!-- Data Preview -->
                  <details class="group">
                    <summary class="cursor-pointer text-blue-400 hover:text-blue-300 transition-colors text-sm font-medium list-none flex items-center">
                      <svg class="w-4 h-4 mr-1 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                      Show data
                    </summary>
                    <div class="mt-3 p-4 bg-gray-800/70 backdrop-blur rounded-lg border border-gray-700">
                      <pre class="text-sm text-gray-300 whitespace-pre-wrap break-words max-h-40 overflow-y-auto"><?= htmlspecialchars($item['data']) ?></pre>
                    </div>
                  </details>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex-shrink-0 flex items-center space-x-2">
                <button onclick="openViewModal(<?= (int)$item['id'] ?>, '<?= htmlspecialchars($item['title'] ?? 'Untitled', ENT_QUOTES) ?>', '<?= $item['item_type'] ?>', <?= htmlspecialchars($item['data'], ENT_QUOTES) ?>)" 
                        class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" 
                        title="View Details">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
                
                <button onclick="openShareModal(<?= (int)$item['id'] ?>, '<?= htmlspecialchars($item['title'] ?? 'Untitled', ENT_QUOTES) ?>', '<?= $item['item_type'] ?>')" 
                        class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" 
                        title="Share">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                  </svg>
                </button>
                
                <button class="p-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors" 
                        title="More Options">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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
    function copyShareLink() {
      const input = document.querySelector('input[readonly]');
      input.select();
      document.execCommand('copy');
      
      // Show feedback
      const button = event.target;
      const originalText = button.textContent;
      button.textContent = 'Copied!';
      button.classList.add('bg-green-700');
      
      setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('bg-green-700');
      }, 2000);
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
      document.querySelectorAll('.card-hover').forEach((card, index) => {
        if (!card.style.animationDelay) {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';
          card.style.transition = 'all 0.8s ease';
          observer.observe(card);
        }
      });
    });
  </script>

  <!-- View Modal -->
  <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="bg-gray-900 rounded-xl p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-800">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-white">View Content</h3>
          <button onclick="closeViewModal()" class="text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="mb-4">
          <h4 class="text-lg font-medium text-white mb-2" id="viewTitle"></h4>
          <p class="text-gray-400 text-sm" id="viewType"></p>
        </div>

        <div id="viewContent" class="text-gray-200">
          <!-- Content will be dynamically loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Share Modal -->
  <div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="bg-gray-900 rounded-xl p-6 max-w-md w-full border border-gray-800">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-white">Share Content</h3>
          <button onclick="closeShareModal()" class="text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="mb-4">
          <p class="text-gray-300 text-sm mb-2">Share this <span id="shareType" class="text-blue-400"></span>:</p>
          <p class="text-white font-medium" id="shareTitle"></p>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <button onclick="shareToSocial('facebook')" class="flex items-center justify-center space-x-2 p-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            <span>Facebook</span>
          </button>
          
          <button onclick="shareToSocial('twitter')" class="flex items-center justify-center space-x-2 p-3 bg-black hover:bg-gray-800 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
            <span>X (Twitter)</span>
          </button>
          
          <button onclick="shareToSocial('linkedin')" class="flex items-center justify-center space-x-2 p-3 bg-blue-700 hover:bg-blue-800 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
            </svg>
            <span>LinkedIn</span>
          </button>
          
          <button onclick="shareToSocial('instagram')" class="flex items-center justify-center space-x-2 p-3 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323c-.875.807-2.026 1.297-3.323 1.297zm7.718-1.297c-.875.807-2.026 1.297-3.323 1.297s-2.448-.49-3.323-1.297c-.807-.875-1.297-2.026-1.297-3.323s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323z"/>
            </svg>
            <span>Instagram</span>
          </button>
          
          <button onclick="shareToSocial('tiktok')" class="flex items-center justify-center space-x-2 p-3 bg-black hover:bg-gray-800 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
            </svg>
            <span>TikTok</span>
          </button>
          
          <button onclick="shareToSocial('messenger')" class="flex items-center justify-center space-x-2 p-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16c-.169 1.858-.896 3.46-2.128 4.66-1.232 1.2-2.9 1.9-4.44 1.9-.169 0-.338-.01-.507-.02-.169.01-.338.01-.507.01-.169 0-.338 0-.507-.01-.169.01-.338.01-.507.02-.169 0-.338 0-.507 0-1.54 0-3.208-.7-4.44-1.9-1.232-1.2-1.959-2.802-2.128-4.66-.01-.169-.01-.338-.01-.507s0-.338.01-.507c.169-1.858.896-3.46 2.128-4.66 1.232-1.2 2.9-1.9 4.44-1.9.169 0 .338.01.507.02.169-.01.338-.01.507-.01.169 0 .338 0 .507.01.169-.01.338-.01.507-.02.169 0 .338 0 .507 0 1.54 0 3.208.7 4.44 1.9 1.232 1.2 1.959 2.802 2.128 4.66.01.169.01.338.01.507s0 .338-.01.507z"/>
            </svg>
            <span>Messenger</span>
          </button>
        </div>

        <div class="border-t border-gray-700 pt-4">
          <div class="flex items-center space-x-2 mb-2">
            <input type="text" id="shareLink" readonly class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded text-white text-sm" placeholder="Share link will appear here">
            <button onclick="copyShareLink()" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm transition-colors">
              Copy
            </button>
          </div>
          <p class="text-gray-400 text-xs">Or copy the link to share anywhere</p>
        </div>
      </div>
    </div>
  </div>

  <script>
    let currentShareId = null;
    let currentShareTitle = '';
    let currentShareType = '';

    // View Modal Functions
    function openViewModal(id, title, type, data) {
      document.getElementById('viewTitle').textContent = title;
      document.getElementById('viewType').textContent = `Type: ${type.charAt(0).toUpperCase() + type.slice(1)}`;
      document.getElementById('viewModal').classList.remove('hidden');
      
      // Parse and display content based on type
      try {
        const parsedData = JSON.parse(data);
        displayContent(type, parsedData);
      } catch (e) {
        // If not JSON, display as plain text
        displayContent(type, data);
      }
    }

    function closeViewModal() {
      document.getElementById('viewModal').classList.add('hidden');
    }

    function displayContent(type, data) {
      const contentDiv = document.getElementById('viewContent');
      
      switch(type) {
        case 'summary':
          displaySummary(data, contentDiv);
          break;
        case 'quiz':
          displayQuiz(data, contentDiv);
          break;
        case 'content':
          displayGenericContent(data, contentDiv);
          break;
        default:
          contentDiv.innerHTML = `<pre class="whitespace-pre-wrap bg-gray-800 p-4 rounded-lg">${JSON.stringify(data, null, 2)}</pre>`;
      }
    }

    function displaySummary(data, container) {
      let html = '';
      
      if (data.summary_long) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-blue-400 mb-3">Detailed Summary</h5>
          <div class="bg-gray-800 p-4 rounded-lg">
            <p class="leading-relaxed">${data.summary_long.replace(/\n/g, '<br>')}</p>
          </div>
        </div>`;
      }
      
      if (data.summary_tldr) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-green-400 mb-3">TL;DR</h5>
          <div class="bg-gray-800 p-4 rounded-lg">
            <p class="leading-relaxed">${data.summary_tldr.replace(/\n/g, '<br>')}</p>
          </div>
        </div>`;
      }
      
      if (data.phrases && Array.isArray(data.phrases)) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-purple-400 mb-3">Key Phrases</h5>
          <div class="flex flex-wrap gap-2">
            ${data.phrases.map(phrase => `<span class="px-3 py-1 bg-gray-800 rounded-lg text-sm border border-gray-700">${phrase}</span>`).join('')}
          </div>
        </div>`;
      }
      
      if (data.url) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-yellow-400 mb-3">Source URL</h5>
          <div class="bg-gray-800 p-4 rounded-lg">
            <a href="${data.url}" target="_blank" class="text-blue-400 hover:text-blue-300 break-all">${data.url}</a>
          </div>
        </div>`;
      }
      
      container.innerHTML = html || '<p class="text-gray-400">No content available</p>';
    }

    function displayQuiz(data, container) {
      let html = '';
      
      if (data.title) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-purple-400 mb-3">Quiz Title</h5>
          <div class="bg-gray-800 p-4 rounded-lg">
            <p class="text-lg font-medium">${data.title}</p>
          </div>
        </div>`;
      }
      
      if (data.questions && Array.isArray(data.questions)) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-blue-400 mb-3">Questions (${data.questions.length})</h5>
          <div class="space-y-4">`;
        
        data.questions.forEach((q, index) => {
          html += `<div class="bg-gray-800 p-4 rounded-lg">
            <h6 class="font-semibold text-white mb-2">Q${index + 1}. ${q.question}</h6>
            <div class="ml-4 space-y-1">`;
          
          if (q.choices && Array.isArray(q.choices)) {
            q.choices.forEach((choice, i) => {
              const isCorrect = i === q.answer;
              const choiceClass = isCorrect ? 'text-green-400 font-medium' : 'text-gray-300';
              html += `<p class="${choiceClass}">${String.fromCharCode(65 + i)}. ${choice}</p>`;
            });
          }
          
          if (q.explanation) {
            html += `<p class="text-sm text-gray-400 mt-2"><strong>Explanation:</strong> ${q.explanation}</p>`;
          }
          
          html += `</div></div>`;
        });
        
        html += `</div></div>`;
      }
      
      if (data.metadata) {
        html += `<div class="mb-6">
          <h5 class="text-lg font-semibold text-yellow-400 mb-3">Quiz Metadata</h5>
          <div class="bg-gray-800 p-4 rounded-lg">
            <pre class="text-sm">${JSON.stringify(data.metadata, null, 2)}</pre>
          </div>
        </div>`;
      }
      
      container.innerHTML = html || '<p class="text-gray-400">No quiz content available</p>';
    }

    function displayGenericContent(data, container) {
      let html = '';
      
      if (typeof data === 'string') {
        html = `<div class="bg-gray-800 p-4 rounded-lg">
          <pre class="whitespace-pre-wrap">${data}</pre>
        </div>`;
      } else if (typeof data === 'object') {
        html = `<div class="bg-gray-800 p-4 rounded-lg">
          <pre class="text-sm">${JSON.stringify(data, null, 2)}</pre>
        </div>`;
      }
      
      container.innerHTML = html || '<p class="text-gray-400">No content available</p>';
    }

    // Close view modal when clicking outside
    document.getElementById('viewModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeViewModal();
      }
    });

    function openShareModal(id, title, type) {
      currentShareId = id;
      currentShareTitle = title;
      currentShareType = type;
      
      document.getElementById('shareTitle').textContent = title;
      document.getElementById('shareType').textContent = type;
      document.getElementById('shareModal').classList.remove('hidden');
      
      // Generate share link
      const shareLink = window.location.origin + '/share.php?t=' + generateShareToken(id);
      document.getElementById('shareLink').value = shareLink;
    }

    function closeShareModal() {
      document.getElementById('shareModal').classList.add('hidden');
    }

    function shareToSocial(platform) {
      const shareLink = document.getElementById('shareLink').value;
      const shareText = `Check out this ${currentShareType} from PodIntellect: ${currentShareTitle}`;
      
      let url = '';
      switch(platform) {
        case 'facebook':
          url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`;
          break;
        case 'twitter':
          url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareLink)}`;
          break;
        case 'linkedin':
          url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareLink)}`;
          break;
        case 'instagram':
          // Instagram doesn't support direct sharing, so we'll copy to clipboard
          copyShareLink();
          alert('Link copied! You can now paste it in your Instagram story or post.');
          return;
        case 'tiktok':
          // TikTok doesn't support direct sharing, so we'll copy to clipboard
          copyShareLink();
          alert('Link copied! You can now paste it in your TikTok video description.');
          return;
        case 'messenger':
          url = `https://www.facebook.com/dialog/send?link=${encodeURIComponent(shareLink)}&app_id=YOUR_APP_ID`;
          break;
      }
      
      if (url) {
        window.open(url, '_blank', 'width=600,height=400');
      }
    }

    function copyShareLink() {
      const shareLink = document.getElementById('shareLink');
      shareLink.select();
      shareLink.setSelectionRange(0, 99999);
      document.execCommand('copy');
      
      // Show feedback
      const button = event.target;
      const originalText = button.textContent;
      button.textContent = 'Copied!';
      button.classList.add('bg-green-600');
      
      setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('bg-green-600');
      }, 2000);
    }

    function generateShareToken(id) {
      // Simple token generation - in production, use proper encryption
      return btoa('share_' + id + '_' + Date.now()).replace(/[^a-zA-Z0-9]/g, '');
    }

    // Close modal when clicking outside
    document.getElementById('shareModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeShareModal();
      }
    });
  </script>
</body>
</html>
