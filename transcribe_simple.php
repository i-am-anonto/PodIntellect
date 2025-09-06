<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


$ASSEMBLYAI_API_KEY = 'c4619c9266524495b6656f1bb8f0220a';

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

function startTranscription($audioUrl, $apiKey) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    
    $YOUR_API_KEY = "c4619c9266524495b6656f1bb8f0220a";

   
    $FILE_URL = "https://assembly.ai/wildfires.mp3";



    $transcript_endpoint = "https://api.assemblyai.com/v2/transcript";

 
    $data = array(
        "audio_url" => $FILE_URL 
    );


    $headers = array(
        "authorization: " . $YOUR_API_KEY,
        "content-type: application/json"
    );


    $curl = curl_init($transcript_endpoint);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $response = json_decode($response, true);
    curl_close($curl);


    $transcript_id = $response['id'];
    $polling_endpoint = "https://api.assemblyai.com/v2/transcript/" . $transcript_id;

    while (true) {
        $polling_response = curl_init($polling_endpoint);
        curl_setopt($polling_response, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($polling_response, CURLOPT_RETURNTRANSFER, true);
        $transcription_result = json_decode(curl_exec($polling_response), true);
        
        if ($transcription_result['status'] === "completed") {
            echo $transcription_result['text'];
            break;
        } else if ($transcription_result['status'] === "error") {
            throw new Exception("Transcription failed: " . $transcription_result['error']);
        }

        sleep(3);
    }
}

function getTranscriptionResult($transcriptId, $apiKey) {
    $endpoint = 'https://api.assemblyai.com/v2/transcript/' . $transcriptId;
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Handle form submission
$error = '';
$transcript = '';
$transcriptId = '';
$videoId = '';
$submittedUrl = '';
$showPollingForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'check_status') {
        
        $transcriptId = $_POST['transcript_id'] ?? '';
        if (!empty($transcriptId)) {
            $result = getTranscriptionResult($transcriptId, $ASSEMBLYAI_API_KEY);
            if (isset($result['status']) && $result['status'] === 'completed') {
                $transcript = $result['text'] ?? '[No transcript text returned]';
            } elseif (isset($result['status']) && $result['status'] === 'failed') {
                $error = 'Transcription failed: ' . ($result['error'] ?? 'Unknown error');
            } else {
                $showPollingForm = true;
            }
        }
    } else {
       
        $submittedUrl = trim($_POST['podcast_url'] ?? '');
        
        if (empty($submittedUrl)) {
            $error = 'Please enter a YouTube URL.';
        } elseif (!filter_var($submittedUrl, FILTER_VALIDATE_URL)) {
            $error = 'Invalid URL.';
        } else {
            $videoId = extractYouTubeVideoId($submittedUrl);
            if (empty($videoId)) {
                $error = 'Could not extract video ID from the YouTube URL.';
            } else {
                $audioUrl = "https://www.youtube.com/watch?v=$videoId";
                list($ok, $result) = startTranscription($audioUrl, $ASSEMBLYAI_API_KEY);
                
                if (!$ok) {
                    $error = 'Failed to start transcription: ' . htmlspecialchars($result);
                } else {
                    $transcriptId = $result;
                    $showPollingForm = true;
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
    <title>PodIntellect - Simple AI Transcription</title>
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
            <h2 class="text-4xl font-bold neon-green mb-8 text-center">Simple AI Transcription</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 rounded-xl p-6 mb-8">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-red-200">Error</h3>
                            <p class="text-red-300"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($showPollingForm): ?>
               
                <div class="bg-blue-900 border border-blue-700 rounded-xl p-8 mb-8 text-center">
                    <h3 class="text-2xl font-semibold text-blue-200 mb-4">Transcription Started!</h3>
                    <p class="text-blue-300 mb-6">Your transcription is being processed. Click the button below to check if it's ready.</p>
                    
                    <form method="POST" action="transcribe_simple.php">
                        <input type="hidden" name="action" value="check_status">
                        <input type="hidden" name="transcript_id" value="<?php echo htmlspecialchars($transcriptId); ?>">
                        <button type="submit" class="px-8 py-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-300">
                            Check Transcription Status
                        </button>
                    </form>
                    
                    <div class="mt-4 text-blue-300 text-sm">
                        <p>Transcription ID: <?php echo htmlspecialchars($transcriptId); ?></p>
                        <p class="mt-2">This may take 5-15 minutes depending on video length.</p>
                    </div>
                </div>
            <?php elseif ($transcript): ?>
               
                <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800 shadow-2xl mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-semibold text-green-400">Transcription Complete!</h3>
                        <div class="flex space-x-2">
                            <button onclick="copyTranscript()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                Copy Text
                            </button>
                            <button onclick="downloadTranscript()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                Download
                            </button>
                        </div>
                    </div>
                    
                    <textarea id="transcriptText" readonly class="w-full bg-gray-800 border border-gray-700 rounded-xl text-white p-4 text-sm leading-relaxed" style="min-height: 300px;"><?php echo htmlspecialchars($transcript); ?></textarea>
                    
                    <div class="mt-4 text-center">
                        <a href="transcribe_simple.php" class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            Transcribe Another Video
                        </a>
                    </div>
                </div>
            <?php else: ?>
             
                <form method="POST" action="transcribe_simple.php" class="space-y-6 mb-8">
                    <div class="flex flex-col md:flex-row gap-4">
                        <input type="url" name="podcast_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($submittedUrl); ?>" class="flex-1 px-6 py-4 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none input-glow transition-all duration-300" required>
                        <button type="submit" class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-300 btn-glow flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>Start Transcription</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-gray-500 text-sm mt-8">
                <p>Powered by <a href="https://www.assemblyai.com/" class="underline text-blue-400" target="_blank">AssemblyAI</a></p>
                <p class="mt-2">This simplified version doesn't use blocking loops and provides immediate feedback.</p>
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
</body>
</html> 