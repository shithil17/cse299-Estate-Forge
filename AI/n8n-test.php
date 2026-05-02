<?php

$resultText = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $text = $_POST["text"];

    $payload = json_encode([
        "text" => $text
    ]);

    $ch = curl_init("http://localhost:5678/webhook/php-test");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);

    $resultText = $decoded["result"] ?? "No response from n8n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP → n8n AI Test</title>
</head>
<body>

<form method="POST">
    <textarea name="text" required placeholder="Type something..."></textarea>
    <br><br>
    <button type="submit">Send</button>
</form>

<?php if (!empty($resultText)): ?>
    <h3>AI Response:</h3>
    <pre><?= htmlspecialchars($resultText) ?></pre>
<?php endif; ?>

</body>
</html>