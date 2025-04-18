<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['storyID'], $_POST['action'])) {
    $storyID = $_POST['storyID'];
    $username = $_SESSION['username'];
    $action = $_POST['action']; // 'like' or 'dislike'

    $stmt = $pdo->prepare("DELETE FROM Likes WHERE StoryID = ? AND Username = ?");
    $stmt->execute([$storyID, $username]);

    if ($action === 'like') {
        // Insert or update like in the Likes table
        $stmt = $pdo->prepare("INSERT INTO Likes (StoryID, Username, Liked) VALUES (?, ?, TRUE)
                                ON DUPLICATE KEY UPDATE Liked = TRUE");
        $stmt->execute([$storyID, $username]);
    } elseif ($action === 'dislike') {
        // Insert or update dislike in the Likes table
        $stmt = $pdo->prepare("INSERT INTO Likes (StoryID, Username, Liked) VALUES (?, ?, FALSE)
                                ON DUPLICATE KEY UPDATE Liked = FALSE");
        $stmt->execute([$storyID, $username]);
    }

    // Redirect back to the stories page
    header("Location: news.php?storyID=" . urlencode($storyID));
    exit();
}
?>