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

$stmt = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$secret = $row['TwoFASecret'] ?? null;
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    // Use verifyCode with the correct secret from the database
    if ($secret && $g->verifyCode($secret, $code)) { // Removed the drift parameter of 2, as it's not part of the standard usage
        $_SESSION['2FA_Verified'] = true; // Set a flag to indicate successful verification
        header("Location: ../index.html"); // Redirect to the dashboard
        exit();
    } else {
        $error = "Invalid code. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Verify 2FA - REMS</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200 flex items-center justify-center">
  <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full text-center">
    <h1 class="text-3xl font-bold mb-6">Enter your 6-digit 2FA code</h1>

    <?php if ($error): ?>
      <p class="mb-4 text-red-600 font-semibold"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input 
        type="text" 
        name="code" 
        placeholder="123456" 
        maxlength="6" 
        pattern="\d{6}" 
        required 
        class="input input-bordered w-full text-center text-xl tracking-widest"
        autocomplete="one-time-code"
      />
      <button type="submit" class="btn btn-primary w-full">Verify</button>
    </form>

    <a href="../dashboard.php" class="mt-6 inline-block text-sm text-blue-600 hover:underline">← Back to Dashboard</a>
  </div>
</body>
</html>
