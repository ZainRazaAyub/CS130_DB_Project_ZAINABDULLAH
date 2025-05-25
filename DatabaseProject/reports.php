=<?php
session_start();

// Connect to your "database-project"
$pdo = new PDO('mysql:host=localhost;dbname=database-project;charset=utf8mb4', 'your_db_username', 'your_db_password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to report.');
}

$user_id = $_SESSION['user_id'];
$video_id = isset($_GET['video_id']) ? (int) $_GET['video_id'] : 0;

if ($video_id <= 0) {
    die('Invalid video ID.');
}

// Check for duplicate report
$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? AND video_id = ?");
$stmt->execute([$user_id, $video_id]);

if ($stmt->rowCount() > 0) {
    echo "You have already reported this video.";
    echo '<br><a href="view.php?video_id=' . $video_id . '">Go Back</a>';
    exit;
}

// Insert the report
$stmt = $pdo->prepare("INSERT INTO reports (user_id, video_id) VALUES (?, ?)");
$stmt->execute([$user_id, $video_id]);

echo "Report submitted successfully.";
echo '<br><a href="view.php?video_id=' . $video_id . '">Go Back</a>';
?>
