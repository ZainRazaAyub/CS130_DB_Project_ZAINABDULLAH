<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "database-project", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$action = $_POST['action'] ?? '';

if ($action === 'signup') {
    // Signup
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$email || !$password) {
        die("Please fill out all signup fields.");
    }

    // Check if user exists
    $check = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "Username or email already exists.";
        exit;
    }

    // Insert new user (plain password as requested)
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        echo "Signup successful! Redirecting to login...";
        header("refresh:2;url=Login.html");
        exit;
    } else {
        echo "Signup error: " . $stmt->error;
        exit;
    }

} elseif ($action === 'login') {
    // Login
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        die("Please fill out all login fields.");
    }

    // Look for user by username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Plain password check
        if ($password === $user['password']) {
            // Successful login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            echo "Login successful! Redirecting to home...";
            header("refresh:2;url=home.php");
            exit;
        } else {
            echo "Incorrect password.";
            exit;
        }
    } else {
        echo "User not found.";
        exit;
    }

} else {
    echo "Invalid action.";
    exit;
}

$conn->close();
?>
