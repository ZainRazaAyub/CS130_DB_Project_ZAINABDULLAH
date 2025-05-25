<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "database-project", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$subscriber_id = $_SESSION['user_id'];
$subscribed_to_id = $_POST['subscribed_to_id'] ?? '';

if (!$subscribed_to_id || !is_numeric($subscribed_to_id)) {
    die("Invalid request.");
}

// Prevent subscribing to self
if ($subscriber_id == $subscribed_to_id) {
    header("Location: home.php");
    exit;
}

// Check if already subscribed
$check_stmt = $conn->prepare("SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND subscribed_to_id = ?");
$check_stmt->bind_param("ii", $subscriber_id, $subscribed_to_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    // Unsubscribe
    $del_stmt = $conn->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND subscribed_to_id = ?");
    $del_stmt->bind_param("ii", $subscriber_id, $subscribed_to_id);
    $del_stmt->execute();
    $del_stmt->close();
} else {
    // Subscribe
    $ins_stmt = $conn->prepare("INSERT INTO subscriptions (subscriber_id, subscribed_to_id) VALUES (?, ?)");
    $ins_stmt->bind_param("ii", $subscriber_id, $subscribed_to_id);
    $ins_stmt->execute();
    $ins_stmt->close();
}

$check_stmt->close();

header("Location: view.php?video_id=" . $_POST['video_id']);
exit;
