<?php
require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

// Get sid from URL
$currentSid = isset($_GET['sid']) ? $conn->real_escape_string($_GET['sid']) : '';

// Block access if not logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];
$message = '';
$statusClass = 'bg-secondary/10 text-secondary';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if ($newPassword === '' || $confirmPassword === '') {
            $message = "Please fill in all fields.";
            $statusClass = "bg-red-100 text-red-700";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Passwords do not match.";
            $statusClass = "bg-red-100 text-red-700";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            $stmt->bind_param("ss", $hashedPassword, $userID);
            if ($stmt->execute()) {
                $message = "Password successfully updated.";
            } else {
                $message = "Failed to update password.";
                $statusClass = "bg-red-100 text-red-700";
            }
            $stmt->close();
        }
    }

    // Handle 2FA toggle
    if (isset($_POST['toggle_2fa'])) {
        $enable2FA = ($_POST['enable_2fa'] == '1') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET 2fa = ? WHERE UserID = ?");
        $stmt->bind_param("is", $enable2FA, $userID);
        if ($stmt->execute()) {
            $message = $enable2FA ? "2FA enabled." : "2FA disabled.";
            if (!$enable2FA) {
                $stmtDel = $conn->prepare("DELETE FROM 2fa WHERE UserID = ?");
                $stmtDel->bind_param("s", $userID);
                $stmtDel->execute();
                $stmtDel->close();
            }
        }
        $stmt->close();
    }
}

// Fetch 2FA status
$stmt = $conn->prepare("SELECT 2fa FROM users WHERE UserID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$twoFAEnabled = (int) $user['2fa'] === 1;

// Check setup
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM 2fa WHERE UserID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$twoFASetupExists = ($row['cnt'] > 0);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rems | Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" />

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
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(196, 198, 207, 0.3);
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-[Inter] min-h-screen">

    <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-outline-variant/30">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <a href="dash.php" class="text-xl font-bold text-primary">Rems</a>
            <div class="flex items-center gap-6">
                <a href="dash.php"
                    class="text-sm font-semibold text-on-surface-variant hover:text-secondary transition-colors">Dashboard</a>
                <a href="logout.php?sid=<?= $currentSid ?>"
                    class="flex items-center gap-1 text-sm font-bold text-red-600 hover:text-red-800 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">power_settings_new</span> Logout
                </a>
            </div>
        </div>
    </header>

    <main class="pt-28 pb-20 px-6">
        <div class="mx-auto max-w-3xl">
            <h1 class="text-3xl font-black text-primary mb-8">Account Settings</h1>

            <?php if ($message): ?>
                <div class="<?= $statusClass ?> p-4 rounded-2xl mb-6 text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined">info</span> <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="space-y-8">
                <!-- Password Section -->
                <section class="glass-card p-8 rounded-[2rem] shadow-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-secondary">lock</span>
                        <h2 class="text-xl font-bold text-primary">Reset Password</h2>
                    </div>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">New
                                Password</label>
                            <input type="password" name="new_password" required
                                class="w-full bg-white border border-outline-variant/50 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Confirm New
                                Password</label>
                            <input type="password" name="confirm_password" required
                                class="w-full bg-white border border-outline-variant/50 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                        </div>
                        <button type="submit" name="reset_password"
                            class="bg-primary text-white text-sm font-bold px-6 py-3 rounded-xl hover:bg-secondary transition-all active:scale-95 shadow-md">
                            Update Password
                        </button>
                    </form>
                </section>

                <!-- 2FA Section -->
                <section class="glass-card p-8 rounded-[2rem] shadow-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-secondary">verified_user</span>
                        <h2 class="text-xl font-bold text-primary">Two-Factor Authentication</h2>
                    </div>
                    <form method="POST" class="space-y-6">
                        <div class="flex gap-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="enable_2fa" value="1" <?= $twoFAEnabled ? 'checked' : '' ?>
                                    class="hidden peer">
                                <div
                                    class="text-center p-4 rounded-2xl border-2 border-outline-variant/30 peer-checked:border-secondary peer-checked:bg-secondary/5 transition-all">
                                    <p class="text-sm font-bold text-on-surface">Enabled</p>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="enable_2fa" value="0" <?= !$twoFAEnabled ? 'checked' : '' ?>
                                    class="hidden peer">
                                <div
                                    class="text-center p-4 rounded-2xl border-2 border-outline-variant/30 peer-checked:border-red-500 peer-checked:bg-red-50 transition-all">
                                    <p class="text-sm font-bold text-on-surface">Disabled</p>
                                </div>
                            </label>
                        </div>

                        <?php if ($twoFAEnabled && !$twoFASetupExists): ?>
                            <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex items-start gap-3">
                                <span class="material-symbols-outlined text-amber-600">warning</span>
                                <p class="text-xs font-medium text-amber-800 leading-relaxed">
                                    2FA is active but not configured.
                                    <a href="2fa/setup-2fa.php" class="underline font-bold block mt-1">Set up your
                                        authenticator now &rarr;</a>
                                </p>
                            </div>
                        <?php endif; ?>

                        <button type="submit" name="toggle_2fa"
                            class="w-full bg-on-surface text-white text-sm font-bold px-6 py-3 rounded-xl hover:bg-primary transition-all active:scale-95 shadow-md">
                            Save Security Preferences
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>

</body>

</html>