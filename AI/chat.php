<?php
/**
 * PHP Handler for n8n Real Estate Inquiry Workflow
 * Connects to Webhook ID: a991531e-222b-4232-bf95-cddd72c888e5
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capture user input from the chat frontend
    $userMessage = $_POST['message'] ?? '';
    $userEmail = $_POST['email'] ?? 'guest@example.com';
    $sessionId = $_POST['session_id'] ?? uniqid();

    if (empty($userMessage)) {
        echo json_encode(['error' => 'Message is empty']);
        exit;
    }

    // 2. Prepare the payload to match your "Set Node (Normalize)" structure
    // Your n8n workflow expects: source, userMessage, userEmail, and sessionId
    $payload = [
        'source'      => 'chat',
        'userMessage' => $userMessage,
        'userEmail'   => $userEmail,
        'sessionId'   => $sessionId
    ];

    // 3. n8n Webhook URL (Replace with your actual tunnel/instance URL)
    $webhookUrl = 'http://localhost:5678/webhook-test/rems_inquire';

    // 4. Initialize cURL to trigger the Webhook
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 5. Properly receive and decode the n8n message
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        // Your Respond to Webhook node returns {"reply": "{{$json.reply}}"}
        $botReply = $data['reply'] ?? "I'm sorry, I couldn't process that.";
        
        echo json_encode(['status' => 'success', 'reply' => $botReply]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reach workflow.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rems | AI Assistant</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />

    <style>
        /* Custom scrollbar for chat box */
        #chat-box::-webkit-scrollbar { width: 6px; }
        #chat-box::-webkit-scrollbar-track { background: transparent; }
        #chat-box::-webkit-scrollbar-thumb { background: #c4c6cf; border-radius: 10px; }
        
        /* Bouncing dots for typing indicator */
        .typing-indicator span {
            display: inline-block;
            width: 6px;
            height: 6px;
            background-color: #002045;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
            margin: 0 1px;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#002045",
                        secondary: "#13696a",
                        surface: "#fcf8ff",
                        "on-surface": "#171837",
                        "on-surface-variant": "#43474e",
                        "outline-variant": "#c4c6cf",
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-surface text-on-surface font-[Inter] h-screen flex items-center justify-center p-4 md:p-8">

    <!-- Chat Container (Glass Card styling) -->
    <div class="w-full max-w-2xl bg-white/80 backdrop-blur-md border border-outline-variant/30 rounded-[2rem] shadow-2xl flex flex-col h-full max-h-[800px] overflow-hidden">
        
        <!-- Header -->
        <header class="bg-primary px-6 py-5 flex items-center justify-between shadow-md z-10">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-xl text-white flex items-center justify-center">
                    <span class="material-symbols-outlined">support_agent</span>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight">Rems Assistant</h1>
                    <p class="text-xs text-white/70 font-medium">Powered by AI</p>
                </div>
            </div>
            <a href="../index.php" class="text-white/80 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </a>
        </header>

        <!-- Chat History Area -->
        <div id="chat-box" class="flex-1 overflow-y-auto p-6 space-y-4 bg-surface/50">
            <!-- Initial Greeting -->
            <div class="flex justify-start">
                <div class="bg-white border border-outline-variant/30 text-on-surface rounded-2xl rounded-tl-sm px-5 py-3.5 max-w-[85%] shadow-sm text-sm leading-relaxed">
                    Hello! I'm the Rems Assistant. How can I help you with your property inquiry today?
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-outline-variant/30">
            <form id="chatForm" class="flex items-center gap-3" onsubmit="event.preventDefault(); sendMessage();">
                <input 
                    type="text" 
                    id="userInput" 
                    placeholder="Ask about properties..." 
                    class="flex-1 bg-surface border border-outline-variant/50 rounded-full px-5 py-3.5 text-sm focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all"
                    autocomplete="off"
                >
                <button 
                    type="submit" 
                    class="bg-primary hover:bg-secondary text-white rounded-full p-3.5 flex items-center justify-center transition-colors shadow-md active:scale-95"
                >
                    <span class="material-symbols-outlined text-[20px]">send</span>
                </button>
            </form>
        </div>
    </div>

<script>
async function sendMessage() {
    const input = document.getElementById('userInput');
    const chatBox = document.getElementById('chat-box');
    const message = input.value.trim();

    if(!message) return;

    // 1. Display user message
    chatBox.innerHTML += `
        <div class="flex justify-end animate-fade-up">
            <div class="bg-primary text-white rounded-2xl rounded-tr-sm px-5 py-3.5 max-w-[85%] shadow-md text-sm leading-relaxed">
                ${escapeHTML(message)}
            </div>
        </div>
    `;
    input.value = '';
    scrollToBottom();

    // 2. Show loading indicator
    const loadingId = 'loading-' + Date.now();
    chatBox.innerHTML += `
        <div id="${loadingId}" class="flex justify-start mt-2">
            <div class="bg-white border border-outline-variant/30 text-on-surface rounded-2xl rounded-tl-sm px-5 py-4 shadow-sm">
                <div class="typing-indicator flex items-center h-full">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    `;
    scrollToBottom();

    // Trigger PHP handler
    const formData = new FormData();
    formData.append('message', message);
    formData.append('email', 'user@nsu.edu'); // Example student email

    try {
        const response = await fetch('chat.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        // Remove loading indicator
        document.getElementById(loadingId).remove();
        
        // Display n8n reply
        const replyText = result.reply ? result.reply : result.message; // handle both success and error keys
        chatBox.innerHTML += `
            <div class="flex justify-start animate-fade-up">
                <div class="bg-white border border-outline-variant/30 text-on-surface rounded-2xl rounded-tl-sm px-5 py-3.5 max-w-[85%] shadow-sm text-sm leading-relaxed whitespace-pre-wrap">
                    ${escapeHTML(replyText)}
                </div>
            </div>
        `;
        scrollToBottom();
    } catch (error) {
        console.error('Error:', error);
        document.getElementById(loadingId).remove();
        chatBox.innerHTML += `
            <div class="flex justify-start">
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl rounded-tl-sm px-5 py-3.5 max-w-[85%] shadow-sm text-sm">
                    Connection error. Please try again.
                </div>
            </div>
        `;
        scrollToBottom();
    }
}

function scrollToBottom() {
    const chatBox = document.getElementById('chat-box');
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Helper to prevent basic XSS when rendering messages
function escapeHTML(str) {
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}
</script>
</body>
</html>