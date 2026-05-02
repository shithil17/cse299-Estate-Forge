<?php
$data = json_encode([
    "query" => "get website info"
]);

$ch = curl_init("http://localhost:5678/webhook-test/costestrems");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
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