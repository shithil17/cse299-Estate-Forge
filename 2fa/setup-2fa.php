<?php
session_start();
require_once '../conn.php';
require_once '../libs/GoogleAuthenticator.php';
$g = new PHPGangsta_GoogleAuthenticator();

$userID = $_SESSION['UserID'] ?? null;
if (!$userID) {
    header("Location: ../login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Check if 2FA secret already exists
$stmt = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row && !empty($row['TwoFASecret'])) {
    // Secret exists, reuse
    $secret = $row['TwoFASecret'];
} else {
    // Generate new secret and insert
    $secret = $g->createSecret();
    $stmt = $conn->prepare("INSERT INTO 2fa (UserID, TwoFASecret) VALUES (?, ?)");
    $stmt->bind_param("is", $userID, $secret);
    $stmt->execute();
    $stmt->close();
}

// Fetch email for app name
$sqlquery = $conn->prepare("SELECT Email FROM users WHERE UserID = ?");
$sqlquery->bind_param("i", $userID);
$sqlquery->execute();
$result = $sqlquery->get_result();
$row = $result->fetch_assoc();
$email = $row['Email'] ?? 'unknown';
$sqlquery->close();

$appName = 'REMS: ' . $email;
$qrCodeUrl = $g->getQRCodeGoogleUrl($appName, $secret);
$manualCode = $secret;
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Setup 2FA - Eduor</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200 flex items-center justify-center">
  <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full text-center">
    <h1 class="text-3xl font-bold mb-6">Set Up Two-Factor Authentication</h1>

    <p class="mb-4 text-gray-700">Scan the QR code below with your <strong>Google Authenticator</strong> app:</p>

    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code" class="mx-auto mb-6 border rounded-lg shadow-sm" />

    <p class="mb-2 text-gray-700">Or enter this secret manually into your app:</p>
    <code class="block mb-6 text-lg font-mono bg-gray-100 p-3 rounded"><?php echo htmlspecialchars($manualCode); ?></code>

    <form method="post" action="verify-2fa.php">
      <button type="submit" class="btn btn-primary w-full">Continue to Verify</button>
    </form>

    <a href="../dashboard.php" class="mt-6 inline-block text-sm text-blue-600 hover:underline">← Back to Dashboard</a>
  </div>
</body>
</html>
