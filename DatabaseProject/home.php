<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "database-project");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all videos with uploader names
$videos = [];
$result = $conn->query("
    SELECT v.video_id, v.title, v.description, v.file_url, u.username 
    FROM videos v
    JOIN users u ON v.user_id = u.user_id
    ORDER BY v.video_id DESC
");
while ($row = $result->fetch_assoc()) {
    $videos[] = $row;
}

// Fetch user playlists
$playlists = [];
$pl_stmt = $conn->prepare("SELECT playlist_id, playlist_name FROM playlists WHERE user_id = ?");
$pl_stmt->bind_param("i", $user_id);
$pl_stmt->execute();
$pl_result = $pl_stmt->get_result();

while ($pl = $pl_result->fetch_assoc()) {
    // Fetch videos in each playlist
    $playlist_id = $pl['playlist_id'];
    $video_stmt = $conn->prepare("
        SELECT v.video_id, v.title 
        FROM playlist_videos pv 
        JOIN videos v ON pv.video_id = v.video_id 
        WHERE pv.playlist_id = ?
    ");
    $video_stmt->bind_param("i", $playlist_id);
    $video_stmt->execute();
    $video_result = $video_stmt->get_result();

    $pl['videos'] = [];
    while ($vid = $video_result->fetch_assoc()) {
        $pl['videos'][] = $vid;
    }

    $playlists[] = $pl;
    $video_stmt->close();
}

$pl_stmt->close();
$conn->close();

// Now include the view (home.html)
include 'home.html';
