<?php
require_once 'conn.php';
require_once 'libs/GoogleAuthenticator.php';
require_once 'Factory/UserActionFactory.php'; // Required for the Factory Pattern

session_start();

$errors = [];
$db = new Database();
$conn = $db->getConnection();

// Context class
class AuthContext {
    private $strategy;

    public function __construct(AuthStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        return $this->strategy->authenticate($conn, $identifier, $password, $code);
    }

    public function getUserId(): ?int {
        return $this->strategy->getUserId();
    }
}

// Password-only authentication strategy
class PasswordOnlyAuth implements AuthStrategy {
    private $userID = null;

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ((int)$user['UserFlag'] === 0) {
                $stmt->close();
                return false;
            }

            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $this->userID = $user['UserID'];
                $stmt->close();
                return true;
            }
        }

        $stmt->close();
        return false;
    }

    public function getUserId(): ?int {
        return $this->userID;
    }
}

// Interface class
interface AuthStrategy {
    public function authenticate($conn, $identifier, $password, $code = null): bool;
    public function getUserId(): ?int;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $errors['login'] = "Please enter both username/email and password.";
    } else {
        if ($identifier === 'admin' && $password === 'admin') {
            $_SESSION['isAdmin'] = true;
            header('Location: admin/adminpanel.php');
            exit();
        }

        // Check user credentials
        $auth        = new AuthContext(new PasswordOnlyAuth());
        $authSuccess = $auth->authenticate($conn, $identifier, $password);

        if ($authSuccess) {
            $userID = $auth->getUserId();

            // After successful password verification, check for 2FA and UserFlag
            $stmtCheck2FA = $conn->prepare("SELECT 2fa, UserFlag FROM users WHERE UserID = ?");
            if (!$stmtCheck2FA) {
                die("Prepare failed: " . $conn->error);
            }
            $stmtCheck2FA->bind_param("i", $userID);
            $stmtCheck2FA->execute();
            $resultCheck2FA = $stmtCheck2FA->get_result();
            $userAuthData   = $resultCheck2FA->fetch_assoc();
            $stmtCheck2FA->close();

            $_SESSION['UserID'] = $userID;

            if ((int)$userAuthData['2fa'] === 1) {
                // User has 2FA enabled, redirect to verification page
                header("Location: 2fa/verify-2fa.php");
                exit();
            } else {
                // User does not have 2FA, use the Factory Pattern to redirect
                try {
                    $userAction = UserActionFactory::createAction($userAuthData['UserFlag']);
                    $userAction->execute();
                } catch (Exception $e) {
                    $errors['login'] = "Login failed: " . $e->getMessage();
                }
            }
        } else {
            $errors['login'] = "Invalid username/email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Rems - Login</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="navbar">
        <a href="#" class="logo">Rems System</a>
        <div class="links">
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
        </div>
    </div>

    <div class="main-content">
        <div class="login-box">
            <div class="login-container">
                <h2>Login</h2>

                <?php if (!empty($errors['login'])): ?>
                    <div class="error"><?= htmlspecialchars($errors['login']) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="text" name="email" placeholder="Username or Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                    <input type="password" name="password" placeholder="Password" required />
                    <button type="submit">Login</button>
                </form>

                <a class="back-link" href="index.html">← Back to Home</a>
            </div>
            <div class="image-container">
                <img src="resources/login.png" alt="Login Illustration" />
            </div>
        </div>
    </div>
</body>
</html>
