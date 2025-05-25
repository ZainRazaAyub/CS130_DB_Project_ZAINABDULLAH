<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "", "database-project", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Input sanitization
$user_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$tags = trim($_POST['tags'] ?? '');

// Validate fields
if (!$title || !isset($_FILES['image'])) {
    die("Title and image are required.");
}

// Handle file upload
$targetDir = "uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$originalName = basename($_FILES["image"]["name"]);
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$uniqueName = uniqid("img_", true) . "." . $ext;
$targetFilePath = $targetDir . $uniqueName;

if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
    die("Failed to upload file.");
}

// Save video data
$stmt = $conn->prepare("INSERT INTO videos (user_id, title, description, file_url) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $title, $description, $targetFilePath);
if (!$stmt->execute()) {
    die("Error saving video: " . $stmt->error);
}
$video_id = $stmt->insert_id;

// Handle tags
if ($tags !== '') {
    $tagList = array_map('trim', explode(',', $tags));

    foreach ($tagList as $tag) {
        if ($tag === '') continue;

        // Insert tag if not exists
        $tagStmt = $conn->prepare("INSERT IGNORE INTO tags (tag_name) VALUES (?)");
        $tagStmt->bind_param("s", $tag);
        $tagStmt->execute();

        // Get tag_id
        $getTag = $conn->prepare("SELECT tag_id FROM tags WHERE tag_name = ?");
        $getTag->bind_param("s", $tag);
        $getTag->execute();
        $tagResult = $getTag->get_result();
        if ($tagRow = $tagResult->fetch_assoc()) {
            $tag_id = $tagRow['tag_id'];

            // Link video and tag
            $linkStmt = $conn->prepare("INSERT INTO video_tags (video_id, tag_id) VALUES (?, ?)");
            $linkStmt->bind_param("ii", $video_id, $tag_id);
            $linkStmt->execute();
        }
    }
}

echo "Upload successful! Redirecting...";
header("refresh:2;url=home.php");
exit;

$conn->close();
?>
