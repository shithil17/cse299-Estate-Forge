<?php
// Error reporting — remove these two lines once everything works
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conn.php';
session_start();

$errors = [];
$db   = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier   = trim($_POST['identifier'] ?? '');   // changed field name to 'identifier'
    $password     = trim($_POST['password'] ?? '');
    $stayLoggedIn = isset($_POST['stay_signed_in']) ? 1 : 0;

    if ($identifier === '' || $password === '') {
        $errors['login'] = "Please enter both email/username and password.";
    } else {

        // ── Admin Bypass ──────────────────────────────────────────────────────
        if ($identifier === 'admin' && $password === 'admin') {
            $_SESSION['isAdmin'] = true;
            header('Location: admin/adminpanel.php');
            exit();
        }

        // ── Normal Authentication ─────────────────────────────────────────────
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ?");
        if (!$stmt) {
            $errors['login'] = "DB error (prepare): " . $conn->error;
        } else {
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {

                    $userID    = $user['UserID'];
                    $sessionID = bin2hex(random_bytes(15));   // 30-char unique ID
                    $lastPage  = "index.php";

                    // Upsert into Session table
                    $stmtSession = $conn->prepare(
                        "REPLACE INTO Session (SessionID, UserID, LastLogin, LastPageLink, StayLoggedIn)
                         VALUES (?, ?, CURRENT_TIMESTAMP, ?, ?)"
                    );
                    if (!$stmtSession) {
                        $errors['login'] = "DB error (session prepare): " . $conn->error;
                    } else {
                        $stmtSession->bind_param("sssi", $sessionID, $userID, $lastPage, $stayLoggedIn);
                        if ($stmtSession->execute()) {
                            // Store in PHP session as backup
                            $_SESSION['UserID']    = $userID;
                            $_SESSION['SessionID'] = $sessionID;

                            // Redirect with sid in URL so index.php can pick it up
                            header("Location: index.php?sid=" . urlencode($sessionID));
                            exit();
                        } else {
                            $errors['login'] = "DB error (session execute): " . $stmtSession->error;
                        }
                    }

                } else {
                    $errors['login'] = "Invalid password.";
                }
            } else {
                $errors['login'] = "No account found with that email.";
            }

            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rems | Secure Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#002045",
                        secondary: "#13696a",
                        surface: "#fcf8ff",
                        "on-surface": "#171837",
                        "outline-variant": "#c4c6cf",
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-surface text-on-surface font-[Inter]">

    <div class="flex min-h-screen">

        <!-- ── Form Panel ─────────────────────────────────────────────────── -->
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-24">
            <div class="mx-auto w-full max-w-sm">

                <a href="index.php" class="text-2xl font-black tracking-tighter text-primary">Rems</a>
                <h2 class="mt-8 text-3xl font-extrabold tracking-tight text-primary">Welcome back</h2>
                <p class="mt-2 text-sm text-slate-500">Enter your details to access your account.</p>

                <?php if (!empty($errors['login'])): ?>
                    <div class="mt-6 rounded-xl border border-red-100 bg-red-50 p-4 text-sm font-medium text-red-800">
                        <?= htmlspecialchars($errors['login']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="mt-10 space-y-6">

                    <!--
                        Field name is 'identifier' (not 'email') so the browser
                        does NOT enforce e-mail format — this lets 'admin' work
                        while still accepting real e-mail addresses.
                    -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-on-surface">
                            Email Address
                        </label>
                        <input
                            type="text"
                            name="identifier"
                            required
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                            class="mt-2 block w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm outline-none transition-all focus:border-secondary focus:ring-1 focus:ring-secondary"
                            placeholder="name@example.com"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-on-surface">
                            Password
                        </label>
                        <input
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="mt-2 block w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm outline-none transition-all focus:border-secondary focus:ring-1 focus:ring-secondary"
                            placeholder="••••••••"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="stay_signed_in"
                                class="rounded border-outline-variant text-secondary focus:ring-secondary" />
                            <span class="text-sm font-medium text-slate-600">Stay signed in</span>
                        </label>
                        <a href="#" class="text-sm font-bold text-secondary hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit"
                        class="w-full rounded-xl bg-primary py-4 text-sm font-bold text-white shadow-lg transition-all hover:bg-primary/90 hover:scale-[1.01] active:scale-95">
                        Sign In
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-slate-500">
                    Don't have an account?
                    <a href="register.php" class="font-bold text-secondary hover:underline">Create an account</a>
                </p>

            </div>
        </div>

        <!-- ── Decorative Panel ───────────────────────────────────────────── -->
        <div class="relative hidden w-1/2 overflow-hidden bg-primary lg:block">
            <div class="absolute inset-0 opacity-40">
                <img src="resources/login.png" alt="Background" class="h-full w-full object-cover" />
            </div>
            <div class="relative flex h-full flex-col justify-end p-16 text-white">
                <blockquote class="text-2xl font-medium leading-relaxed">
                    "The best way to manage real estate is through intelligence and simplicity.
                    Rems brings both to your fingertips."
                </blockquote>
                <p class="mt-6 text-sm font-bold uppercase tracking-widest text-secondary">Rems Core Systems</p>
            </div>
        </div>

    </div>

</body>
</html>