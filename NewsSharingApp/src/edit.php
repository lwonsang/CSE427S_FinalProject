<?php
session_start();
require 'db.php';

// Make sure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Handle the GET request to display the edit form
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['type'])) {
    $id = $_GET['id'];
    $type = $_GET['type'];

    if ($type === 'comment') {
        // Fetch the comment from the database
        $stmt = $pdo->prepare("SELECT Body FROM Comments WHERE CommentID = ? AND Username = ?");
        $stmt->execute([$id, $username]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($type === 'story') {
        // Fetch the story from the database
        $stmt = $pdo->prepare("SELECT Body FROM Stories WHERE StoryID = ? AND Username = ?");
        $stmt->execute([$id, $username]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // If the item exists, show it in the form
    if (!$item) {
        echo "Unauthorized access or item not found.";
        exit();
    }
}

// Handle the POST request to update the body
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['type']) && isset($_POST['newBody'])) {
    header("Location: news.php");
    $id = $_POST['id'];
    $type = $_POST['type'];
    $newBody = trim($_POST['newBody']);

    // Redirect after the update


    if (!empty($newBody)) {
        if ($type === 'comment') {
            // Update the comment
            $stmt = $pdo->prepare("UPDATE Comments SET Body = ? WHERE CommentID = ? AND Username = ?");
            $stmt->execute([$newBody, $id, $username]);
        } elseif ($type === 'story') {
            // Update the story
            $stmt = $pdo->prepare("UPDATE Stories SET Body = ? WHERE StoryID = ? AND Username = ?");
            $stmt->execute([$newBody, $id, $username]);
        }
        exit();
    } else {
        echo "Body cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($type) ?></title>
</head>
<body>
    <h2>Edit <?= htmlspecialchars(ucfirst($type)) ?></h2>
    <form method="POST">
        <textarea name="newBody" rows="4" cols="50" required><?= htmlspecialchars($item['Body']) ?></textarea>
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <button type="submit" name="update_body">Update</button>
    </form>
</body>
</html>