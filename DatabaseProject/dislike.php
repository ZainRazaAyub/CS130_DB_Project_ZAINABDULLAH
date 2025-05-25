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

// First remove any existing like by this user
$removeLike = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?");
$removeLike->bind_param("ii", $user_id, $video_id);
$removeLike->execute();

// Now check if the dislike already exists
$check = $conn->prepare("SELECT * FROM dislikes WHERE user_id = ? AND video_id = ?");
$check->bind_param("ii", $user_id, $video_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    // Insert the dislike
    $insert = $conn->prepare("INSERT INTO dislikes (user_id, video_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $video_id);
    $insert->execute();
}

$conn->close();
header("Location: view.php?video_id=$video_id");
exit;
?>
