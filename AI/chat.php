<?php
/*
|--------------------------------------------------------------------------
| realtime-chat.php
|--------------------------------------------------------------------------
| A simple real-time style chat page that sends user messages to your n8n
| webhook and receives the AI response instantly.
|
| Replace $webhookUrl with your actual n8n webhook production URL.
|--------------------------------------------------------------------------
*/

session_start();

$webhookUrl = "https://your-n8n-domain/webhook/inquiry"; // CHANGE THIS

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

/*
|--------------------------------------------------------------------------
| AJAX Handler
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    header('Content-Type: application/json');

    $message = trim($_POST['message']);

    if ($message === '') {
        echo json_encode([
            "status" => "error",
            "reply" => "Empty message."
        ]);
        exit;
    }

    $payload = [
        "source"      => "php",
        "userMessage" => $message,
        "userEmail"   => "guest@rems.local",
        "sessionId"   => session_id()
    ];

    $ch = curl_init($webhookUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo json_encode([
            "status" => "error",
            "reply" => "Connection failed: " . curl_error($ch)
        ]);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $decoded = json_decode($response, true);

    if (!$decoded) {
        echo json_encode([
            "status" => "error",
            "reply" => "Invalid response from workflow."
        ]);
        exit;
    }

    $_SESSION['chat_history'][] = [
        "user" => $message,
        "bot"  => $decoded['reply'] ?? 'No reply received.'
    ];

    echo json_encode([
        "status" => "success",
        "reply"  => $decoded['reply'] ?? 'No reply.'
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>REMS AI Inquiry Chat</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 0;
}
.chat-container {
    width: 90%;
    max-width: 800px;
    margin: 30px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}
.chat-header {
    background: #222;
    color: #fff;
    padding: 15px;
    font-size: 20px;
}
.chat-box {
    height: 500px;
    overflow-y: auto;
    padding: 20px;
    background: #fafafa;
}
.message {
    margin-bottom: 15px;
}
.user {
    text-align: right;
}
.user .bubble {
    background: #007bff;
    color: white;
}
.bot .bubble {
    background: #e9ecef;
    color: #222;
}
.bubble {
    display: inline-block;
    padding: 12px 16px;
    border-radius: 16px;
    max-width: 75%;
}
.chat-input {
    display: flex;
    border-top: 1px solid #ddd;
}
.chat-input input {
    flex: 1;
    padding: 15px;
    border: none;
    outline: none;
}
.chat-input button {
    padding: 15px 25px;
    border: none;
    background: #28a745;
    color: white;
    cursor: pointer;
}
.typing {
    font-style: italic;
    color: #777;
}
</style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header">REMS AI Inquiry Assistant</div>

    <div class="chat-box" id="chatBox">
        <?php foreach ($_SESSION['chat_history'] as $chat): ?>
            <div class="message user">
                <div class="bubble"><?= htmlspecialchars($chat['user']) ?></div>
            </div>
            <div class="message bot">
                <div class="bubble"><?= htmlspecialchars($chat['bot']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="chat-input">
        <input type="text" id="messageInput" placeholder="Ask something...">
        <button onclick="sendMessage()">Send</button>
    </div>
</div>

<script>
function sendMessage() {
    const input = document.getElementById("messageInput");
    const chatBox = document.getElementById("chatBox");
    const message = input.value.trim();

    if (!message) return;

    chatBox.innerHTML += `
        <div class="message user">
            <div class="bubble">${escapeHtml(message)}</div>
        </div>
    `;

    chatBox.innerHTML += `
        <div class="message bot" id="typingMsg">
            <div class="bubble typing">Typing...</div>
        </div>
    `;

    chatBox.scrollTop = chatBox.scrollHeight;
    input.value = "";

    const formData = new FormData();
    formData.append("message", message);

    fetch("", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("typingMsg").remove();

        chatBox.innerHTML += `
            <div class="message bot">
                <div class="bubble">${escapeHtml(data.reply)}</div>
            </div>
        `;
        chatBox.scrollTop = chatBox.scrollHeight;
    })
    .catch(err => {
        document.getElementById("typingMsg").remove();

        chatBox.innerHTML += `
            <div class="message bot">
                <div class="bubble">Error: ${escapeHtml(err.message)}</div>
            </div>
        `;
    });
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.innerText = text;
    return div.innerHTML;
}

document.getElementById("messageInput").addEventListener("keypress", function(e) {
    if (e.key === "Enter") sendMessage();
});
</script>

</body>
</html>