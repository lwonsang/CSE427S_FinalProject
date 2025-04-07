<!DOCTYPE html>
<html lang="en">

<head>
    <!-- for unregistered users -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module 3 News</title>
    <style>
        body {
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            background-color: #AFDDE5;
            overflow-x: hidden;
        }

        #header {
            font-family: Georgia, 'Times New Roman', Times, serif;
            position: absolute;
            top: 0vh;
            left: 0vw;
            background-color: #003135;
            width: 105%;
            height: 11vh;
            color: white;
        }

        h1 {
            position: relative;
            left: 2vw;
            top: 1vh;
        }

        #info {
            font-family: Georgia, 'Times New Roman', Times, serif;
            position: absolute;
            left: 0vw;
            top: 11vh;
            background-color: #0FA4AF;
            width: 120vw;
            height: 10vh;
            color: #fafafa;
        }

        h2 {
            position: relative;
            left: 3vw;
            top: 0vh;
        }

        #loginButton{
            position: absolute;
            top: 3.5vh;
            right: 3vw;
            background-color: #024950;
            font-weight:bold;
            color:white;
            border:.1vh solid black;
            border-radius:1vh;
            border-color: #2696FA;
            padding:1vh;
            padding-left:1vw;
            padding-right:1vw;
            cursor: pointer;
            font-size: 2vh;
            color: white;
            z-index: 2;
        }

        #createStory{
            position: absolute;
            top: 18vh;
            left: 5vw;
        }

        .storiesList{
            position: absolute;
            top: 24vh;
            left: 30vw;
            width: 50%;
            height: 75vh;
            overflow-y: scroll;
        }

        .storiesList ul{
            list-style-type: none;
            padding-left: 1vw;
        }

        .storiesList li {
            margin-bottom: 5vh;
        }

        #genreFilter{
            position: absolute;
            top: 14vh;
            left: 45vw;
            width: 50%;
        }

    </style>
</head>

<body>
<header>
    <div id="header">
        <h1>News Site</h1>
    </div>
    <!-- <div id="info">
        <h2>Users can upload, view, and delete files in a simple and efficient manner!</h2>
    </div> -->
</header>

<form action="newssite.php" method="POST" id="loginForm">
    <input type="submit" id="loginButton" name="login" value="Log in">
</form>

<form action="newssite.php" method="POST" id="genreFilter">
    Filter Genre:<select name = "genres" multiple>
        <option value = "General"> General</option>
        <option value = "Politics"> Politics</option>
        <option value = "Business"> Business</option>
        <option value = "Sports"> Sports</option>
        <option value = "Science"> Science</option>
        <option value = "Entertainment"> Entertainment</option>
    </select>
    <input type = "submit" name="filter" value="Filter Stories">
    <button type = "submit" name="clear">Clear Filters</button>
</form>



<?php
session_start();
require 'db.php';

//Handle Logout requests
if(isset($_POST["login"])){
    header("Location: newslogin.php");
    exit;
}

//show all new stories
function viewStories($pdo) {
    if(isset($_POST['genres']) && !isset($_POST['clear'])){
        $selectedGenre = $_POST['genres'];
        $stmt = $pdo->prepare("SELECT StoryID, Title, Body, Username, Link, Genre FROM Stories where Genre = ? ORDER BY StoryID");
        if($stmt){
            if($stmt->execute([$selectedGenre])){
                $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    else{
        $stmt = $pdo->prepare("SELECT StoryID, Title, Body, Username, Link, Genre FROM Stories ORDER BY StoryID");
        if($stmt){
            if($stmt->execute()){
                $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    echo "<div class='storiesList'>";
    echo "<ul>";
    foreach ($stories as $story) {
        if($story['Link'] != ''){
            $link = "https://" . $story['Link'];
        }
        else{
            $link = '';
        }
        printf("<li><strong>Title:</strong> %s <br><strong>Body:</strong> %s <br><strong>Author:</strong> %s <br><strong>Link:</strong> <a href='%s'>%s</a> <br><strong>Genre:</strong> %s",
            htmlspecialchars($story['Title']),
            htmlspecialchars($story['Body']),
            htmlspecialchars($story['Username']),
            htmlspecialchars($link),
            htmlspecialchars($story['Link']), // Link needed twice for the output text and the link itself
            htmlspecialchars($story['Genre'])
        );

        // Display total likes
        $totalLikesStmt = $pdo->prepare("SELECT COUNT(*) AS totalLikes FROM Likes WHERE StoryID = ? AND Liked = TRUE");
        $totalLikesStmt->execute([$story['StoryID']]);
        $totalLikes = $totalLikesStmt->fetchColumn();

        $totalDislikesStmt = $pdo->prepare("SELECT COUNT(*) AS totalLikes FROM Likes WHERE StoryID = ? AND Liked = FALSE");
        $totalDislikesStmt->execute([$story['StoryID']]);
        $totalDislikes = $totalDislikesStmt->fetchColumn();
        echo "<p>Total Likes: " . htmlspecialchars($totalLikes - $totalDislikes) . "</p><br>";

        viewComments($pdo, $story['StoryID']);
        echo "<hr></li>";
    }
    echo "</ul>\n";
    echo "</div>";
}

viewStories($pdo);

function viewComments($pdo, $storyID){
    // Prepare the query to fetch comments
    $stmt = $pdo->prepare("SELECT CommentID, Username, Body FROM Comments WHERE StoryID = ? ORDER BY CommentID");

    if($stmt){
        if($stmt->execute([$storyID])){
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if($comments){
                echo "<ul><li><strong>Comments:</strong><br><br>";
                foreach($comments as $comment){
                    echo "<strong>Username:</strong> " . htmlspecialchars($comment['Username']) . "<br>";
                    echo "<strong>Body:</strong> " . htmlspecialchars($comment['Body']) . "<br>";
                    echo "</li>\n";
                }
                echo "</ul>\n";
            }
            

        } else {
            // Display an error message if query fails
            echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
                Error viewing comments.
                </div>";
        }
    }
}

?>

</body>
</html>