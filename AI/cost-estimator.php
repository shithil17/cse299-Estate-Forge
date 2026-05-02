<?php

// n8n webhook URL (change if needed)
$url = "http://localhost:5678/webhook-test/costestrems";

// Initialize cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode JSON response from n8n
$data = json_decode($response, true);

// Debug raw response (very useful)
echo "<h3>Raw Response:</h3>";
echo "<pre>";
echo $response;
echo "</pre>";

// Check if JSON is valid
if (!$data) {
    echo "<h3 style='color:red;'>Invalid JSON response from n8n</h3>";
    exit;
}

// Display result
echo "<h2>Rod Price Result</h2>";

if (isset($data['price'])) {
    echo "<p><b>Price:</b> " . htmlspecialchars($data['price']) . "</p>";
} else {
    echo "<p style='color:red;'>Price not found in response</p>";
}

?>
