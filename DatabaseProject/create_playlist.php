<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$playlist_name = trim($_POST['playlist_name']);
if (empty($playlist_name)) {
    die("Playlist name is required.");
}

$conn = new mysqli("localhost", "root", "", "database-project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("INSERT INTO playlists (user_id, playlist_name) VALUES (?, ?)");
$stmt->bind_param("is", $_SESSION['user_id'], $playlist_name);
$stmt->execute();
$stmt->close();

header("Location: home.php");
exit;
