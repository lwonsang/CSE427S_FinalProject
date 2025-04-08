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
        $stmt = $pdo->prepare("DELETE FROM Comments WHERE CommentID = ? AND Username = ?");
        $stmt->execute([$id, $username]);
    } elseif ($type === 'story') {
        // Fetch the story from the database
        $stmt = $pdo->prepare("DELETE FROM Stories WHERE StoryID = ? AND Username = ?");
        $stmt->execute([$id, $username]);
    }

    header("Location: news.php");
    exit();
    
}
   // If the item exists, show it in the form
else{
    echo "Unauthorized access or item not found.";
    exit();
}