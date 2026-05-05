<?php
require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

$sid = isset($_GET['sid']) ? $conn->real_escape_string($_GET['sid']) : '';

if (!empty($sid)) {
    $stmt = $conn->prepare("DELETE FROM Session WHERE SessionID = ?");
    $stmt->bind_param("s", $sid);
    $stmt->execute();
    $stmt->close();
}



// Unset all session variables
$_SESSION = array();

// Destroy the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the global session object
session_destroy();


header("Location: index.php?status=logged_out");
exit();
?>