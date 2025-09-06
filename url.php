<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Require authentication
require_login();

error_reporting(E_ALL);
ini_set('display_errors', 1);

function extractYouTubeVideoId($url) {
    $videoId = '';
    
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            $videoId = $matches[1];
            break;
        }
    }
    
    return $videoId;
}

function isYouTubeUrl($url) {
    $youtubePatterns = [
        '/youtube\.com/',
        '/youtu\.be/'
    ];
    
    foreach ($youtubePatterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }
    
    return false;
}

$error = '';
$videoId = '';
$videoTitle = '';
$videoDescription = '';
$podcastUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $podcastUrl = trim($_POST['podcast_url'] ?? '');
    
    if (empty($podcastUrl)) {
        $error = 'Please enter a podcast URL.';
    } elseif (!filter_var($podcastUrl, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL.';
    } elseif (!isYouTubeUrl($podcastUrl)) {
        $error = 'Currently, only YouTube URLs are supported. Please enter a YouTube URL.';
    } else {
        $videoId = extractYouTubeVideoId($podcastUrl);
        
        if (empty($videoId)) {
            $error = 'Could not extract video ID from the YouTube URL. Please check the URL and try again.';
        } else {
            // Record in history - now guaranteed to have a user since we require login
            $user = current_user();
            $uid = (int) $user['id'];
            
            // Extract basic video info (you might want to enhance this with YouTube API)
            $videoTitle = 'YouTube Video: ' . $videoId;
            $videoDescription = 'Video submitted for processing';
            
            // Store in history with more detailed information
            $historyData = [
                'url' => $podcastUrl,
                'video_id' => $videoId,
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
                'platform' => 'youtube'
            ];
            
            $historyId = record_history($uid, 'url_submission', $videoTitle, $historyData);
            
            // You could redirect to a processing page or continue with the display
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PodIntellect - Process URL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #000000 0%, #0a0a0a 50%, #111111 100%);
        }
        
        .neon-green {
            color: #00ff41;
            text-shadow: 
                0 0 5px #00ff41,
                0 0 10px #00ff41,
                0 0 15px #00ff41,
                0 0 20px #00ff41;
        }
        
        .blue-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.6);
        }
        
        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-slide-up { 
            animation: slideInUp 0.8s ease-out; 
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Header -->
    <header class="blue-gradient py-4 px-6 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <h1 class="text-3xl font-bold neon-green">PodIntellect</h1>
                <span class="text-sm text-gray-300">AI-Powered Podcast Summaries</span>
            </div>
            <nav class="flex space-x-6">
                <a href="index.php" class="text-white hover:text-gray-300 transition-colors">Home</a>
                <a href="dashboard.php" class="text-white hover:text-gray-300 transition-colors">Dashboard</a>
                <a href="history.php" class="text-white hover:text-gray-300 transition-colors">History</a>
                <a href="auth.php?action=logout" class="text-white hover:text-gray-300 transition-colors">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-6 py-12">
        <div class="max-w-6xl mx-auto">
            
            <div class="mb-8">
                <a href="index.php" class="inline-flex items-center space-x-2 text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to Home</span>
                </a>
            </div>

            <?php if (isset($error) && !empty($error)): ?>
                <div class="bg-red-900/20 border border-red-700/30 rounded-xl p-6 mb-8 animate-slide-up">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-red-200">Error</h3>
                            <p class="text-red-300"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="index.php" class="inline-flex items-center px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-600 transition-colors">
                            Try Again
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($videoId) && !empty($videoId)): ?>
                <!-- Video Display Section -->
                <div class="bg-gray-900/80 backdrop-blur-lg rounded-2xl p-8 border border-gray-800 shadow-2xl mb-8 card-hover animate-slide-up">
                    <div class="text-center mb-6">
                        <h2 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($videoTitle); ?></h2>
                        <p class="text-gray-400"><?php echo htmlspecialchars($videoDescription); ?></p>
                        <div class="mt-4 inline-flex items-center px-3 py-1 bg-green-900/20 border border-green-600/30 rounded-full text-green-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            URL saved to your history
                        </div>
                    </div>
                    
                    <!-- YouTube Video Embed -->
                    <div class="video-container mb-6">
                        <iframe 
                            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>?rel=0" 
                            title="YouTube video player" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    </div>
                    
                    <!-- Video Information and Next Steps -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-gray-800/70 backdrop-blur rounded-xl p-6 card-hover">
                            <h3 class="text-xl font-semibold mb-4 text-green-400">Video Details</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-gray-400">Video ID:</span>
                                    <span class="ml-2 font-mono text-sm"><?php echo htmlspecialchars($videoId); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Source URL:</span>
                                    <span class="ml-2 text-sm break-all"><?php echo htmlspecialchars($podcastUrl); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Status:</span>
                                    <span class="ml-2 text-sm text-green-400">Ready for processing</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800/70 backdrop-blur rounded-xl p-6 card-hover">
                            <h3 class="text-xl font-semibold mb-4 text-blue-400">Next Steps</h3>
                            <div class="space-y-4">
                                <p class="text-gray-300 text-sm">Ready to generate AI-powered summary of this podcast episode.</p>
                                
                                <form method="POST" action="transcribe_supadata.php">
                                    <input type="hidden" name="video_id" value="<?php echo htmlspecialchars($videoId); ?>">
                                    <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all duration-300 btn-glow">
                                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        Generate Summary
                                    </button>
                                </form>
                                
                                <div class="flex space-x-2">
                                    <a href="history.php" class="flex-1 text-center px-3 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                                        View History
                                    </a>
                                    <a href="index.php" class="flex-1 text-center px-3 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                                        Submit Another
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!isset($videoId) || empty($videoId)): ?>
                <div class="bg-gray-900/80 backdrop-blur-lg rounded-2xl p-8 border border-gray-800 shadow-2xl animate-slide-up">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-semibold mb-2">Enter YouTube Podcast URL</h3>
                        <p class="text-gray-400">Paste your YouTube podcast link below to display the video</p>
                    </div>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <input 
                                type="url" 
                                name="podcast_url"
                                placeholder="https://www.youtube.com/watch?v=..." 
                                class="flex-1 px-6 py-4 bg-gray-800/70 backdrop-blur border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-green-400 transition-all duration-300"
                                required
                                value="<?php echo htmlspecialchars($podcastUrl); ?>"
                            >
                            <button 
                                type="submit" 
                                class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-300 btn-glow flex items-center justify-center space-x-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Load Video</span>
                            </button>
                        </div>
                    </form>

                    <!-- Supported YouTube URL Formats -->
                    <div class="mt-8 pt-6 border-t border-gray-800">
                        <p class="text-center text-gray-400 mb-4">Supported YouTube URL Formats</p>
                        <div class="grid md:grid-cols-2 gap-4 text-sm">
                            <div class="bg-gray-800/50 backdrop-blur rounded-lg p-4">
                                <p class="text-gray-300 mb-2">Standard:</p>
                                <p class="font-mono text-green-400">youtube.com/watch?v=VIDEO_ID</p>
                            </div>
                            <div class="bg-gray-800/50 backdrop-blur rounded-lg p-4">
                                <p class="text-gray-300 mb-2">Short:</p>
                                <p class="font-mono text-green-400">youtu.be/VIDEO_ID</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="blue-gradient py-6 px-6 mt-12">
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
        // Add smooth entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.8s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html> 