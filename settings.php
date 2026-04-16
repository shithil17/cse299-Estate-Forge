<?php
require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

// Block access if not logged in + 2FA verified
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

// Handle form submissions
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if ($newPassword === '' || $confirmPassword === '') {
            $message = "Please fill in all password fields.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Passwords do not match.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            $stmt->bind_param("si", $hashedPassword, $userID);
            if ($stmt->execute()) {
                $message = "Password successfully updated.";
            } else {
                $message = "Failed to update password.";
            }
            $stmt->close();
        }
    }

    // Handle 2FA toggle
    if (isset($_POST['toggle_2fa'])) {
        $enable2FA = ($_POST['enable_2fa'] == '1') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET 2fa = ? WHERE UserID = ?");
        $stmt->bind_param("ii", $enable2FA, $userID);
        if ($stmt->execute()) {
            $message = $enable2FA ? "2FA enabled." : "2FA disabled.";

            // Optional: if disabling 2FA, also delete from 2fa table
            if (!$enable2FA) {
                $stmtDel = $conn->prepare("DELETE FROM 2fa WHERE UserID = ?");
                $stmtDel->bind_param("i", $userID);
                $stmtDel->execute();
                $stmtDel->close();
            }
        } else {
            $message = "Failed to update 2FA status.";
        }
        $stmt->close();
    }
}

// Fetch user info and 2FA status
$stmt = $conn->prepare("SELECT 2fa FROM users WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$twoFAEnabled = (int)$user['2fa'] === 1;

// Check if 2FA setup exists in 2fa table
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM 2fa WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$twoFASetupExists = ($row['cnt'] > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - Rems System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="dash.css">
</head>
<body>

  <!-- Navbar -->
  <div class="navbar">
      <div class="navbar-left">
          <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
          <img src="https://dummyimage.com/200x40/004080/ffffff&text=Rems+System" alt="Rems Logo" class="logo">
      </div>
  </div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
      <ul>
          <li><a href="dash.php"><i class="fa fa-home"></i> Home</a></li>
          <li class="active"><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
          <li><a href="login.php"><i class="fa fa-power-off"></i> Logout</a></li>
      </ul>
  </div>

  <div class="main-content" id="main-content">
    <div class="page-header">
      <h1>Settings</h1>
    </div>

    <?php if ($message): ?>
      <div class="alert-box">
        <?= htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>


    <form method="POST" class="form-box">
      <h2>Reset Password</h2>
      <input type="password" name="new_password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit" name="reset_password">Update Password</button>
    </form>

    <!-- 2FA Settings -->
    <form method="POST" class="form-box">
      <h2>Two-Factor Authentication (2FA)</h2>
      <label><input type="radio" name="enable_2fa" value="1" <?php if($twoFAEnabled) echo 'checked'; ?>> Enable 2FA</label><br>
      <label><input type="radio" name="enable_2fa" value="0" <?php if(!$twoFAEnabled) echo 'checked'; ?>> Disable 2FA</label><br>

      <?php if ($twoFAEnabled && !$twoFASetupExists): ?>
        <p class="warning">You have enabled 2FA but have not set it up yet. 
          <a href="2fa/setup-2fa.php">Click here to set up 2FA</a>.
        </p>
      <?php endif; ?>

      <button type="submit" name="toggle_2fa">Save 2FA Settings</button>
    </form>
  </div>

  <script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("active");
        document.getElementById("main-content").classList.toggle("shift");
    }
  </script>

</body>
</html>
