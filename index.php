<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Check if user is already logged in
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PodIntellect - Podcast Summary Generator</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body { 
      font-family: 'Inter', sans-serif; 
      background: linear-gradient(180deg, #000000 0%, #0a0a0a 50%, #111111 100%);
    }
    
    /* Enhanced animations */
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
    
    @keyframes pulse-glow {
      0%, 100% { box-shadow: 0 0 5px #00ff41, 0 0 10px #00ff41, 0 0 15px #00ff41; }
      50% { box-shadow: 0 0 10px #00ff41, 0 0 20px #00ff41, 0 0 30px #00ff41; }
    }
    
    @keyframes slideInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .animate-float { animation: float 6s ease-in-out infinite; }
    .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
    .animate-slide-up { animation: slideInUp 0.8s ease-out; }
    .animate-fade-in { animation: fadeIn 1s ease-out; }
    
    .neon-green { 
      color:#00ff41; 
      text-shadow:0 0 5px #00ff41,0 0 10px #00ff41,0 0 15px #00ff41,0 0 20px #00ff41; 
    }
    
    .neon-green-glow { 
      box-shadow:0 0 5px #00ff41,0 0 10px #00ff41,0 0 15px #00ff41,0 0 20px #00ff41; 
    }
    
    .blue-gradient { 
      background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 50%,#1e40af 100%); 
    }
    
    .input-glow:focus { 
      box-shadow:0 0 15px rgba(0,255,65,.5); 
      border-color:#00ff41; 
      transform: scale(1.02);
    }
    
    .btn-glow:hover { 
      box-shadow:0 0 20px rgba(0,255,65,.6); 
      transform: translateY(-2px) scale(1.05);
    }
    
    .neon-btn {
      background:#00ff41; 
      color:#00140a; 
      font-weight:700;
      box-shadow:0 0 10px #00ff41,0 0 20px #00ff41,0 0 30px #00ff41;
      transition: all 0.3s ease;
    }
    
    .neon-btn:hover {
      filter:brightness(1.1);
      box-shadow:0 0 14px #00ff41,0 0 26px #00ff41,0 0 36px #00ff41;
      transform: translateY(-3px) scale(1.05);
    }
    
    .card-hover {
      transition: all 0.3s ease;
    }
    
    .card-hover:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    
    .feature-icon {
      transition: all 0.3s ease;
    }
    
    .feature-icon:hover {
      transform: rotate(360deg) scale(1.1);
    }
    
    /* Background animations */
    .bg-animated {
      background: linear-gradient(-45deg, #000000, #0a0a0a, #111111, #0a0a0a);
      background-size: 400% 400%;
      animation: gradientShift 15s ease infinite;
    }
    
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    /* Particle effects */
    .particles {
      position: absolute;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1;
    }
    
    .particle {
      position: absolute;
      width: 2px;
      height: 2px;
      background: #00ff41;
      border-radius: 50%;
      animation: floatParticle 10s linear infinite;
      opacity: 0.6;
    }
    
    @keyframes floatParticle {
      0% {
        transform: translateY(100vh) translateX(0);
        opacity: 0;
      }
      10% {
        opacity: 0.6;
      }
      90% {
        opacity: 0.6;
      }
      100% {
        transform: translateY(-100px) translateX(100px);
        opacity: 0;
      }
    }
  </style>
</head>
<body class="bg-animated text-white min-h-screen flex flex-col">

  <?php include __DIR__ . '/header.php'; ?>

  <!-- Particle Background -->
  <div class="particles fixed inset-0 pointer-events-none">
    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
    <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
    <div class="particle" style="left: 50%; animation-delay: 8s;"></div>
    <div class="particle" style="left: 60%; animation-delay: 1s;"></div>
    <div class="particle" style="left: 70%; animation-delay: 3s;"></div>
    <div class="particle" style="left: 80%; animation-delay: 5s;"></div>
    <div class="particle" style="left: 90%; animation-delay: 7s;"></div>
  </div>

  <!-- HERO -->
  <section class="relative w-full z-10">
    <!-- Auth button - show different content based on login status -->
    <div class="absolute right-4 top-4 z-40 animate-fade-in">
      <?php if ($user): ?>
        <div class="flex items-center space-x-4">
          <span class="text-gray-300">Welcome, <?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
          <a href="dashboard.php" class="neon-btn px-4 py-2 rounded-lg">Dashboard</a>
          <a href="auth.php?action=logout" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all">Logout</a>
        </div>
      <?php else: ?>
        <button id="open-auth-hero" class="neon-btn px-4 py-2 rounded-lg animate-pulse-glow">
          Login / Sign up
        </button>
      <?php endif; ?>
    </div>

    <div class="flex items-center justify-center px-6 pt-14 pb-6">
      <div class="max-w-4xl w-full text-center animate-slide-up">
        <h2 class="text-5xl md:text-7xl font-bold mb-6 animate-float">
          Transform Your <span class="neon-green">Podcasts</span> Into <span class="neon-green">Intelligence</span>
        </h2>
        <p class="text-xl md:text-2xl text-gray-400 mb-8 max-w-3xl mx-auto animate-fade-in">
          Upload any podcast URL and get instant, AI-powered summaries that capture the key insights, 
          main points, and actionable takeaways in seconds.
        </p>
      </div>
    </div>
  </section>

  <!-- URL Input Section -->
  <main class="flex-1 flex items-start justify-center px-6 pb-12 z-10 relative">
    <div class="max-w-4xl w-full">
      <div class="bg-gray-900/80 backdrop-blur-lg rounded-2xl p-8 border border-gray-800 shadow-2xl card-hover animate-slide-up">
        <div class="text-center mb-8">
          <h3 class="text-2xl md:text-3xl font-semibold mb-2">Enter Podcast URL</h3>
          <p class="text-gray-400 text-lg">Paste your podcast link below to generate an intelligent summary</p>
        </div>
        
        <?php if (!$user): ?>
          <div class="bg-yellow-900/20 border border-yellow-600/30 rounded-lg p-4 mb-6 text-center">
            <p class="text-yellow-300">
              <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
              Please log in to save your podcast history and access all features
            </p>
          </div>
        <?php endif; ?>
        
        <form method="POST" action="url.php" class="space-y-6" id="url-form">
          <div class="flex flex-col md:flex-row gap-4">
            <input 
              type="url" 
              name="podcast_url" 
              placeholder="https://youtube.com/watch?v=... or https://spotify.com/..." 
              class="flex-1 px-6 py-4 bg-gray-800/70 backdrop-blur border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none input-glow transition-all duration-300"
              required
            >
            <button 
              type="submit"
              class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-300 btn-glow flex items-center justify-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
              <span>Generate Summary</span>
            </button>
          </div>
        </form>

        <!-- Supported Platforms -->
        <div class="mt-8 pt-6 border-t border-gray-800">
          <p class="text-center text-gray-400 mb-4">Supported Platforms</p>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-gray-500">
            <div class="flex items-center justify-center space-x-2 p-3 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition-all">
              <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 17.438c-.246.399-.78.523-1.189.276-3.263-1.998-7.363-2.45-12.197-1.342-.453.104-.899-.194-1.003-.647-.104-.453.194-.899.647-1.003 5.319-1.219 9.808-.703 13.467 1.551.399.246.523.78.276 1.189z"/>
              </svg>
              <span>Spotify</span>
            </div>
            <div class="flex items-center justify-center space-x-2 p-3 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition-all">
              <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-2 16l-4-4 1.414-1.414L10 14.172l7.586-7.586L19 8l-9 9z"/>
              </svg>
              <span>Apple Podcasts</span>
            </div>
            <div class="flex items-center justify-center space-x-2 p-3 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition-all">
              <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
              <span>YouTube</span>
            </div>
            <div class="flex items-center justify-center space-x-2 p-3 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition-all">
              <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5 13h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
              </svg>
              <span>Direct URLs</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Features -->
      <div class="grid md:grid-cols-3 gap-8 mt-16">
        <div class="text-center p-6 bg-gray-900/80 backdrop-blur-lg rounded-xl border border-gray-800 card-hover animate-slide-up" style="animation-delay: 0.2s;">
          <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-emerald-500 rounded-lg flex items-center justify-center mx-auto mb-4 feature-icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">Lightning Fast</h3>
          <p class="text-gray-400">Generate comprehensive summaries in under 30 seconds with our optimized AI engine</p>
        </div>
        <div class="text-center p-6 bg-gray-900/80 backdrop-blur-lg rounded-xl border border-gray-800 card-hover animate-slide-up" style="animation-delay: 0.4s;">
          <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4 feature-icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548-.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">AI-Powered</h3>
          <p class="text-gray-400">Advanced AI extracts key insights, main points, and actionable takeaways</p>
        </div>
        <div class="text-center p-6 bg-gray-900/80 backdrop-blur-lg rounded-xl border border-gray-800 card-hover animate-slide-up" style="animation-delay: 0.6s;">
          <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4 feature-icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-2">Smart Formatting</h3>
          <p class="text-gray-400">Well-structured summaries with bullet points, highlights, and key sections</p>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="blue-gradient py-8 px-6 mt-12 z-10 relative">
    <div class="container mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="flex items-center space-x-2 mb-4 md:mb-0">
          <h2 class="text-2xl font-bold neon-green animate-pulse-glow">PodIntellect</h2>
          <span class="text-sm text-gray-300">© 2024 All rights reserved</span>
        </div>
        <div class="flex space-x-6 text-sm">
          <a href="#" class="text-white hover:text-gray-300 transition-colors hover:underline">Privacy Policy</a>
          <a href="#" class="text-white hover:text-gray-300 transition-colors hover:underline">Terms of Service</a>
          <a href="#" class="text-white hover:text-gray-300 transition-colors hover:underline">Support</a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Enhanced form handling with better UX
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('url-form');
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerHTML;
      
      // Handle form submission with loading states
      form.addEventListener('submit', function(e) {
        <?php if (!$user): ?>
        // If user is not logged in, show login modal first
        e.preventDefault();
        if (typeof window.openAuthModal === 'function') {
          window.openAuthModal();
        }
        return;
        <?php endif; ?>
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2" stroke-opacity="0.25"></circle>
            <path stroke-linecap="round" stroke-width="2" d="M12 2v4"></path>
          </svg>
          <span>Processing...</span>
        `;
        
        // Reset after a delay (form will actually submit)
        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }, 2000);
      });
      
      // Open auth modal
      const authBtn = document.getElementById('open-auth-hero');
      if (authBtn) {
        authBtn.addEventListener('click', function() {
          if (typeof window.openAuthModal === 'function') {
            window.openAuthModal();
          }
        });
      }
      
      // Add smooth scroll and entrance animations
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      });
      
      document.querySelectorAll('.card-hover').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.8s ease';
        observer.observe(el);
      });
    });
  </script>
</body>
</html>
