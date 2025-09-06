<?php

$ASSEMBLYAI_API_KEY = 'c4619c9266524495b6656f1bb8f0220a';


define('MAX_VIDEO_LENGTH_HOURS', 3);
define('POLLING_TIMEOUT_MINUTES', 15);
define('POLLING_INTERVAL_SECONDS', 5);


function extractYouTubeVideoId($url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/'
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    return '';
}

function getYouTubeAudioUrl($videoId) {
    
    return "https://www.youtube.com/watch?v=$videoId";
}

function startTranscription($audioUrl, $apiKey) {
    $endpoint = 'https://api.assemblyai.com/v2/transcript';
    $data = [
        'audio_url' => $audioUrl
    ];
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
   
    error_log("AssemblyAI API Response: HTTP $httpcode");
    error_log("Response: " . $response);
    if ($curlError) {
        error_log("cURL Error: " . $curlError);
    }
    
    if ($httpcode !== 200 && $httpcode !== 201) {
        return [false, "HTTP $httpcode: " . $response];
    }
    $result = json_decode($response, true);
    return [true, $result['id'] ?? null];
}

function getTranscriptionResult($transcriptId, $apiKey) {
    $endpoint = 'https://api.assemblyai.com/v2/transcript/' . $transcriptId;
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
  
    error_log("AssemblyAI Get Result: HTTP $httpcode");
    error_log("Response: " . $response);
    if ($curlError) {
        error_log("cURL Error: " . $curlError);
    }
    
    $result = json_decode($response, true);
    return $result;
}


$error = '';
$transcript = '';
$status = '';
$transcriptId = '';
$videoId = '';
$submittedUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUrl = trim($_POST['podcast_url'] ?? '');
    if (empty($ASSEMBLYAI_API_KEY) || $ASSEMBLYAI_API_KEY === 'YOUR_ASSEMBLYAI_API_KEY_HERE') {
        $error = 'Please set your AssemblyAI API key in the script.';
    } elseif (empty($submittedUrl)) {
        $error = 'Please enter a YouTube URL.';
    } elseif (!filter_var($submittedUrl, FILTER_VALIDATE_URL)) {
        $error = 'Invalid URL.';
    } else {
        $videoId = extractYouTubeVideoId($submittedUrl);
        if (empty($videoId)) {
            $error = 'Could not extract video ID from the YouTube URL.';
        } else {
            $audioUrl = getYouTubeAudioUrl($videoId);
          
            list($ok, $result) = startTranscription($audioUrl, $ASSEMBLYAI_API_KEY);
            if (!$ok) {
                $error = 'Failed to start transcription: ' . htmlspecialchars($result);
            } else {
                $transcriptId = $result;
  
                $maxTries = (POLLING_TIMEOUT_MINUTES * 60) / POLLING_INTERVAL_SECONDS; 
                $waitSeconds = POLLING_INTERVAL_SECONDS;
                $progress = 0;
                $status = 'queued'; 
                error_log("Starting polling for transcript ID: $transcriptId");
                
                for ($i = 0; $i < $maxTries; $i++) {
                    sleep($waitSeconds);
                    $res = getTranscriptionResult($transcriptId, $ASSEMBLYAI_API_KEY);
                    error_log("Poll attempt " . ($i + 1) . ": " . json_encode($res));
                    
                    if (isset($res['status'])) {
                        $status = $res['status'];
                        error_log("Status: $status");
                        
                        if ($status === 'completed') {
                            $transcript = $res['text'] ?? '[No transcript text returned]';
                            error_log("Transcription completed! Text length: " . strlen($transcript));
                            break;
                        } elseif ($status === 'failed') {
                            $error = 'Transcription failed: ' . ($res['error'] ?? 'Unknown error');
                            error_log("Transcription failed: $error");
                            break;
                        } elseif ($status === 'processing' || $status === 'queued') {
                            
                            $progress = min(($i / $maxTries) * 100, 95); // Cap at 95% until complete
                            error_log("Still processing... Progress: $progress%");
                        }
                    } else {
                        error_log("No status in response: " . json_encode($res));
                    }
                }
                
                if ($status !== 'completed' && !$error) {
                    $error = "Transcription is taking longer than expected. Current status: $status. Please try again in a few minutes.";
                    error_log("Timeout reached. Final status: $status");
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PodIntellect - AI Transcription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .neon-green { color: #00ff41; text-shadow: 0 0 5px #00ff41,0 0 10px #00ff41,0 0 15px #00ff41,0 0 20px #00ff41; }
        .blue-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .input-glow:focus { box-shadow: 0 0 15px rgba(0,255,65,0.5); border-color: #00ff41; }
        .btn-glow:hover { box-shadow: 0 0 20px rgba(0,255,65,0.6); }
        textarea { min-height: 200px; }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <header class="blue-gradient py-4 px-6 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <h1 class="text-3xl font-bold neon-green">PodIntellect</h1>
                <span class="text-sm text-gray-300">AI-Powered Podcast Summaries</span>
            </div>
            <nav class="hidden md:flex space-x-6">
                <a href="index.php" class="text-white hover:text-gray-300 transition-colors">Home</a>
                <a href="process.php" class="text-white hover:text-gray-300 transition-colors">Video</a>
                <a href="#" class="text-white hover:text-gray-300 transition-colors">Contact</a>
            </nav>
        </div>
    </header>
    <main class="flex-1 px-6 py-12">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-4xl font-bold neon-green mb-8 text-center">AI Transcription</h2>
            <div class="bg-gray-800 rounded-xl p-4 mb-6 text-center">
                <p class="text-gray-300 text-sm">Supports videos up to <?php echo MAX_VIDEO_LENGTH_HOURS; ?> hours long</p>
                <p class="text-gray-400 text-xs mt-1">Longer videos may take 10-15 minutes to process</p>
            </div>
            <form method="POST" action="transcribe.php" class="space-y-6 mb-8">
                <div class="flex flex-col md:flex-row gap-4">
                    <input type="url" name="podcast_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($submittedUrl); ?>" class="flex-1 px-6 py-4 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none input-glow transition-all duration-300" required>
                    <button type="submit" class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-300 btn-glow flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Transcribe</span>
                    </button>
                </div>
            </form>
            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 rounded-xl p-6 mb-8">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <h3 class="text-lg font-semibold text-red-200">Error</h3>
                            <p class="text-red-300"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
                         <?php elseif ($transcript): ?>
                 <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800 shadow-2xl mb-8">
                     <div class="flex justify-between items-center mb-6">
                         <h3 class="text-2xl font-semibold text-green-400">Transcription Result</h3>
                         <div class="flex space-x-2">
                             <button onclick="copyTranscript()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                 Copy Text
                             </button>
                             <button onclick="downloadTranscript()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                 Download
                             </button>
                         </div>
                     </div>
                     
                     
                     <div class="grid md:grid-cols-4 gap-4 mb-6">
                         <div class="bg-gray-800 rounded-lg p-4 text-center">
                             <div class="text-2xl font-bold text-blue-400"><?php echo number_format(str_word_count($transcript)); ?></div>
                             <div class="text-gray-400 text-sm">Words</div>
                         </div>
                         <div class="bg-gray-800 rounded-lg p-4 text-center">
                             <div class="text-2xl font-bold text-green-400"><?php echo number_format(strlen($transcript)); ?></div>
                             <div class="text-gray-400 text-sm">Characters</div>
                         </div>
                         <div class="bg-gray-800 rounded-lg p-4 text-center">
                             <div class="text-2xl font-bold text-purple-400"><?php echo number_format(substr_count($transcript, '.')); ?></div>
                             <div class="text-gray-400 text-sm">Sentences</div>
                         </div>
                         <div class="bg-gray-800 rounded-lg p-4 text-center">
                             <div class="text-2xl font-bold text-yellow-400"><?php echo number_format(substr_count($transcript, "\n")); ?></div>
                             <div class="text-gray-400 text-sm">Paragraphs</div>
                         </div>
                     </div>
                     
                    
                     <div class="space-y-4">
                         <div class="flex justify-between items-center">
                             <h4 class="text-lg font-semibold text-gray-300">Full Transcript</h4>
                             <span class="text-gray-500 text-sm">Click to expand</span>
                         </div>
                         <textarea id="transcriptText" readonly class="w-full bg-gray-800 border border-gray-700 rounded-xl text-white p-4 text-sm leading-relaxed" style="min-height: 300px; max-height: 600px; overflow-y: auto;"><?php echo htmlspecialchars($transcript); ?></textarea>
                     </div>
                     
                     
                     <div class="mt-6 p-4 bg-gray-800 rounded-lg border border-gray-700">
                         <h4 class="text-lg font-semibold text-blue-400 mb-2">Next Steps</h4>
                         <div class="grid md:grid-cols-2 gap-4 text-sm">
                             <div>
                                 <p class="text-gray-300 mb-2">Ready to generate AI summary from this transcript?</p>
                                 <button class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all duration-300">
                                     Generate AI Summary
                                 </button>
                             </div>
                             <div>
                                 <p class="text-gray-300 mb-2">Or analyze the transcript further:</p>
                                 <div class="space-y-1 text-gray-400">
                                     <p>• Extract key topics</p>
                                     <p>• Identify speakers</p>
                                     <p>• Generate timestamps</p>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 
                 <script>
                 function copyTranscript() {
                     const textarea = document.getElementById('transcriptText');
                     textarea.select();
                     document.execCommand('copy');
                     
                    
                     const button = event.target;
                     const originalText = button.textContent;
                     button.textContent = 'Copied!';
                     button.classList.add('bg-green-700');
                     setTimeout(() => {
                         button.textContent = originalText;
                         button.classList.remove('bg-green-700');
                     }, 2000);
                 }
                 
                 function downloadTranscript() {
                     const textarea = document.getElementById('transcriptText');
                     const text = textarea.value;
                     const blob = new Blob([text], { type: 'text/plain' });
                     const url = window.URL.createObjectURL(blob);
                     const a = document.createElement('a');
                     a.href = url;
                     a.download = 'transcript.txt';
                     document.body.appendChild(a);
                     a.click();
                     document.body.removeChild(a);
                     window.URL.revokeObjectURL(url);
                 }
                 </script>
                         <?php elseif ($status && !$error): ?>
                 <div class="bg-blue-900 border border-blue-700 rounded-xl p-8 mb-8 text-center">
                     <div class="flex items-center justify-center space-x-3 mb-4">
                         <svg class="w-8 h-8 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle></svg>
                         <span class="text-blue-200 font-semibold text-lg">Transcription in progress...</span>
                     </div>
                     <div class="mb-4">
                         <div class="w-full bg-gray-700 rounded-full h-3 mb-2">
                             <div class="bg-blue-500 h-3 rounded-full transition-all duration-500" style="width: <?php echo isset($progress) ? $progress : 0; ?>%"></div>
                         </div>
                         <p class="text-blue-300 text-sm"><?php echo isset($progress) ? round($progress, 1) : 0; ?>% complete</p>
                         <p class="text-blue-400 text-xs mt-1">Status: <?php echo htmlspecialchars($status); ?></p>
                     </div>
                     <div class="space-y-2 text-blue-300 text-sm">
                         <p>Processing your video... This may take 10-15 minutes for 3-hour videos.</p>
                         <p>Please don't close this page. You can leave it open in a tab.</p>
                         <p class="text-yellow-300">Debug: Check server error logs for detailed progress</p>
                     </div>
                 </div>
            <?php endif; ?>
            <div class="text-gray-500 text-sm mt-8">
                <p>Powered by <a href="https://www.assemblyai.com/" class="underline text-blue-400" target="_blank">AssemblyAI</a> (free tier, API key required)</p>
                <p class="mt-2">To get your free API key, <a href="https://www.assemblyai.com/" class="underline text-green-400" target="_blank">sign up here</a> and paste it in <code>transcribe.php</code>.</p>
            </div>
        </div>
    </main>
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
</body>
</html> 