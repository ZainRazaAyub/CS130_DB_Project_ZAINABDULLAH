<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_POST['video_id'])) {
    header("Location: Login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "database-project", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$video_id = intval($_POST['video_id']);

// First remove any existing dislike by this user
$removeDislike = $conn->prepare("DELETE FROM dislikes WHERE user_id = ? AND video_id = ?");
$removeDislike->bind_param("ii", $user_id, $video_id);
$removeDislike->execute();

// Now check if the like already exists
$check = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND video_id = ?");
$check->bind_param("ii", $user_id, $video_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    // Insert the like
    $insert = $conn->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $video_id);
    $insert->execute();
}

$conn->close();
header("Location: view.php?video_id=$video_id");
exit;
?>
