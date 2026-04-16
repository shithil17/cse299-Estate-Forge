<?php
// In your dashboard or login file after authentication
session_start();
require_once 'UserActionFactory.php';
require_once '../conn.php';

$userID = $_SESSION['UserID'] ?? null;
if (!$userID) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT UserFlag FROM users WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$userFlag = $row['UserFlag'] ?? null;
$stmt->close();

try {
    // Get the correct action object from the factory
    $userAction = UserActionFactory::createAction($userFlag);
    
    // Execute the action (the redirect)
    $userAction->execute();

} catch (Exception $e) {
    // Handle the error, maybe log it and show an error page
    echo $e->getMessage();
    // header("Location: error_page.php");
    // exit();
}
?>