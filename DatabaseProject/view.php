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

$video_id = $_GET['video_id'] ?? '';
if (!$video_id || !is_numeric($video_id)) {
    die("Invalid image ID.");
}

$user_id = $_SESSION['user_id'];

// ======= HANDLE ADD TO PLAYLIST POST =======
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_playlist'])) {
    $playlist_id = (int)$_POST['playlist_id'];

    // Validate playlist belongs to user
    $stmt = $conn->prepare("SELECT playlist_id FROM playlists WHERE playlist_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $playlist_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Check if video already in playlist
        $check_stmt = $conn->prepare("SELECT 1 FROM playlist_videos WHERE playlist_id = ? AND video_id = ?");
        $check_stmt->bind_param("ii", $playlist_id, $video_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows === 0) {
            // Insert video into playlist
            $insert_stmt = $conn->prepare("INSERT INTO playlist_videos (playlist_id, video_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $playlist_id, $video_id);
            $insert_stmt->execute();
            $insert_stmt->close();
            $message = "Video added to playlist!";
        } else {
            $message = "Video is already in the playlist.";
        }
        $check_stmt->close();
    } else {
        $message = "Invalid playlist selection.";
    }
    $stmt->close();
}

// ======= HANDLE NEW COMMENT SUBMISSION =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    $comment_text = trim($_POST['comment_text']);
    if (!empty($comment_text)) {
        $insert_comment_stmt = $conn->prepare("INSERT INTO comments (video_id, user_id, comment_text) VALUES (?, ?, ?)");
        $insert_comment_stmt->bind_param("iis", $video_id, $user_id, $comment_text);
        $insert_comment_stmt->execute();
        $insert_comment_stmt->close();

        // Redirect to avoid form resubmission
        header("Location: view.php?video_id=$video_id");
        exit;
    }
}

// ======= FETCH USER'S PLAYLISTS FOR DROPDOWN =======
$playlist_stmt = $conn->prepare("SELECT playlist_id, playlist_name FROM playlists WHERE user_id = ?");
$playlist_stmt->bind_param("i", $user_id);
$playlist_stmt->execute();
$playlist_result = $playlist_stmt->get_result();

$user_playlists = [];
while ($row = $playlist_result->fetch_assoc()) {
    $user_playlists[] = $row;
}
$playlist_stmt->close();

// ======= FETCH IMAGE DATA AND UPLOADER INFO =======
$sql = "
    SELECT v.title, v.description, v.file_url, u.username
    FROM videos v
    JOIN users u ON v.user_id = u.user_id
    WHERE v.video_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Image not found.");
}

$image = $result->fetch_assoc();

// ======= FETCH TAGS =======
$tag_stmt = $conn->prepare("
    SELECT t.tag_name 
    FROM tags t 
    JOIN video_tags vt ON t.tag_id = vt.tag_id 
    WHERE vt.video_id = ?
");
$tag_stmt->bind_param("i", $video_id);
$tag_stmt->execute();
$tag_result = $tag_stmt->get_result();

$tags = [];
while ($tag_row = $tag_result->fetch_assoc()) {
    $tags[] = $tag_row['tag_name'];
}

// ======= FETCH LIKE & DISLIKE COUNT =======
$likeStmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE video_id = ?");
$likeStmt->bind_param("i", $video_id);
$likeStmt->execute();
$likeStmt->bind_result($likeCount);
$likeStmt->fetch();
$likeStmt->close();

$dislikeStmt = $conn->prepare("SELECT COUNT(*) FROM dislikes WHERE video_id = ?");
$dislikeStmt->bind_param("i", $video_id);
$dislikeStmt->execute();
$dislikeStmt->bind_result($dislikeCount);
$dislikeStmt->fetch();
$dislikeStmt->close();

// ======= GET UPLOADER ID & SUBSCRIBER COUNT =======
$uploader_id_stmt = $conn->prepare("SELECT user_id FROM videos WHERE video_id = ?");
$uploader_id_stmt->bind_param("i", $video_id);
$uploader_id_stmt->execute();
$uploader_id_stmt->bind_result($uploader_id);
$uploader_id_stmt->fetch();
$uploader_id_stmt->close();

$subscriber_stmt = $conn->prepare("SELECT COUNT(*) FROM subscriptions WHERE subscribed_to_id = ?");
$subscriber_stmt->bind_param("i", $uploader_id);
$subscriber_stmt->execute();
$subscriber_stmt->bind_result($subscriberCount);
$subscriber_stmt->fetch();
$subscriber_stmt->close();

// ======= CHECK IF CURRENT USER IS UPLOADER =======
$current_user_id = $_SESSION['user_id'];
$isUploader = ($current_user_id == $uploader_id);

// ======= CHECK IF USER SUBSCRIBED TO UPLOADER =======
$isSubscribed = false;
if (!$isUploader) {
    $check_sub_stmt = $conn->prepare("SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND subscribed_to_id = ?");
    $check_sub_stmt->bind_param("ii", $current_user_id, $uploader_id);
    $check_sub_stmt->execute();
    $check_sub_stmt->store_result();
    $isSubscribed = $check_sub_stmt->num_rows > 0;
    $check_sub_stmt->close();
}

// ======= FETCH COMMENTS WITHOUT created_at =======
$comments_stmt = $conn->prepare("
    SELECT c.comment_text, u.username
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.video_id = ?
    ORDER BY c.comment_id DESC
");
$comments_stmt->bind_param("i", $video_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row;
}
$comments_stmt->close();

// Now pass all needed variables to view.html
// $message, $user_playlists, $video_id, $image, $tags, $likeCount, $dislikeCount, $isUploader, $isSubscribed, $uploader_id, $subscriberCount, $comments
// Handle Report Submission
// Handle Report Submission
$reportSubmitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_video'])) {
    $report_stmt = $conn->prepare("INSERT INTO reports (user_id, video_id) VALUES (?, ?)");
    $report_stmt->bind_param("ii", $user_id, $video_id);
    if ($report_stmt->execute()) {
        $reportSubmitted = true;
    }
    $report_stmt->close();

    // Redirect to avoid form resubmission (important for alert!)
    header("Location: view.php?video_id=$video_id&report=1");
    exit;
}
$reportPopup = isset($_GET['report']) && $_GET['report'] == '1';

// ======= HANDLE NEW RATING SUBMISSION =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = (int)$_POST['rating'];
    if ($rating >= 1 && $rating <= 5) {
        // Insert or update rating
      $stmt = $conn->prepare("
    INSERT INTO video_ratings (user_id, video_id, rating) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE rating = ?
");
$stmt->bind_param("iiii", $user_id, $video_id, $rating, $rating);

        $stmt->execute();
        $stmt->close();

        // Redirect to avoid form resubmission
        header("Location: view.php?video_id=$video_id");
        exit;
    }
}
// Fetch average rating for the video
$avgRatingStmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM video_ratings WHERE video_id = ?");
$avgRatingStmt->bind_param("i", $video_id);
$avgRatingStmt->execute();
$avgRatingResult = $avgRatingStmt->get_result()->fetch_assoc();
$avgRating = round($avgRatingResult['avg_rating'], 1);
$ratingCount = $avgRatingResult['rating_count'];
$avgRatingStmt->close();

// Fetch current user's rating for this video (if any)
$userRating = 0;
$userRatingStmt = $conn->prepare("SELECT rating FROM video_ratings WHERE user_id = ? AND video_id = ?");
$userRatingStmt->bind_param("ii", $user_id, $video_id);
$userRatingStmt->execute();
$userRatingStmt->bind_result($userRatingFetched);
if ($userRatingStmt->fetch()) {
    $userRating = $userRatingFetched;
}
$userRatingStmt->close();



include 'view.html';
