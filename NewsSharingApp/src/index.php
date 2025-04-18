<?php
session_start();
require 'db.php';

if (isset($_SESSION['username'])){ 
    $username = htmlspecialchars($_SESSION['username']); //get username from logged in user
}else {
    header("Location: newssite.php");
    exit();
}

//Handle Logout requests
if(isset($_POST["logout"])){
    session_destroy();
    header("Location: newssite.php");
    exit;
}

// Create news stories and add it to the Stories database
if(isset($_POST['newsTitle']) & isset($_POST['newsBody'])){
    $newsTitle = $_POST['newsTitle'];
    $newsBody = $_POST['newsBody'];
    $newsLink = $_POST['newsLink'];
    if(isset($_POST['newsGenre'])){
        $newsGenre = $_POST['newsGenre'];
    }
    else{
        $newsGenre = 'General';
    }

    $stmt = $pdo->prepare("insert into Stories (Body, Title, Username, Link, Genre) values (?, ?, ?, ?, ?)");
    if($stmt){
        if($stmt->execute([$newsBody, $newsTitle, $username, $newsLink, $newsGenre])){
            // echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
            //         Successfully created story.</div>";
            header("Location: news.php");
            exit();
                
        }else {
            echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
                Error creating story.
                </div>";
        }
    }
    else{
        echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
                Error preparing story.
                </div>";
    }
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
        printf("<li><strong>Title:</strong> %s <br><strong>Body:</strong> %s <br><strong>Author:</strong> %s <br><strong>Link:</strong> <a href='%s'>%s</a> <br><strong>Genre:</strong> %s</li><hr>",
            htmlspecialchars($story['Title']),
            htmlspecialchars($story['Body']),
            htmlspecialchars($story['Username']),
            htmlspecialchars($link),
            htmlspecialchars($story['Link']), // Link needed twice for the output text and the link itself
            htmlspecialchars($story['Genre'])
        );

        $username = $_SESSION['username'];

        // Check if the user has liked or disliked the story
        $likeStmt = $pdo->prepare("SELECT Liked FROM Likes WHERE StoryID = ? AND Username = ?");
        $likeStmt->execute([$story['StoryID'], $username]);
        $likeData = $likeStmt->fetch(PDO::FETCH_ASSOC);
        
        // Display the like/dislike buttons based on the user's action
        if ($likeData) {
            // User has already liked or disliked the story
            $liked = $likeData['Liked'];
            if ($liked) {

                echo '<form method="POST" action="like_dislike.php" style="display:inline;">
                    <input type="hidden" name="storyID" value="' . htmlspecialchars($story['StoryID']) . '">
                    <input type="hidden" name="action" value="undo">
                    <button type="submit">Undo Like</button>
                </form>';

                echo '<form method="POST" action="like_dislike.php" style="display:inline;">
                    <input type="hidden" name="storyID" value="' . htmlspecialchars($story['StoryID']) . '">
                    <input type="hidden" name="action" value="dislike">
                    <button type="submit">Dislike</button>
                </form>';
                
            } else {

                echo '<form method="POST" action="like_dislike.php" style="display:inline;">
                    <input type="hidden" name="storyID" value="' . htmlspecialchars($story['StoryID']) . '">
                    <input type="hidden" name="action" value="like">
                    <button type="submit">Like</button>
                </form>';

                echo '<form method="POST" action="like_dislike.php" style="display:inline;">
                    <input type="hidden" name="storyID" value="' . htmlspecialchars($story['StoryID']) . '">
                    <input type="hidden" name="action" value="undo">
                    <button type="submit">Undo Dislike</button>
                </form>';
            }
        } else {
            // User has not liked or disliked the story
            echo '<form method="POST" action="like_dislike.php" style="display:inline;">
                <input type="hidden" name="storyID" value="' . htmlspecialchars($story['StoryID']) . '">
                <input type="hidden" name="action" value="like">
                <button type="submit">Like</button>
            </form>';

            echo '<form method="POST" action="like_dislike.php" style="display:inline;">
            <input type="hidden" name="storyID" value="' . htmlspecialchars($story['StoryID']) . '">
            <input type="hidden" name="action" value="dislike">
            <button type="submit">Dislike</button>
        </form>';
        }

        // Display total likes
        $totalLikesStmt = $pdo->prepare("SELECT COUNT(*) AS totalLikes FROM Likes WHERE StoryID = ? AND Liked = TRUE");
        $totalLikesStmt->execute([$story['StoryID']]);
        $totalLikes = $totalLikesStmt->fetchColumn();

        $totalDislikesStmt = $pdo->prepare("SELECT COUNT(*) AS totalLikes FROM Likes WHERE StoryID = ? AND Liked = FALSE");
        $totalDislikesStmt->execute([$story['StoryID']]);
        $totalDislikes = $totalDislikesStmt->fetchColumn();
        echo "<p>Total Likes: " . htmlspecialchars($totalLikes - $totalDislikes) . "</p>";

        if ($story['Username'] == $username) {
            echo '<form method="GET" action="edit.php" style="display:inline;">
                <input type="hidden" name="id" value="' . htmlspecialchars($story['StoryID']) . '">
                <input type="hidden" name="type" value="story">
                <button type="submit" name="edit">Edit</button>
            </form>';

            echo '<form method="GET" action="delete.php" style="display:inline;">
                <input type="hidden" name="id" value="' . htmlspecialchars($story['StoryID']) . '">
                <input type="hidden" name="type" value="story">
                <button type="submit" name="delete">Delete</button>
            </form>';
        }

        viewComments($pdo, $story['StoryID']);

        printf("<form action='news.php' method='POST'>
                <input type='hidden' name='storyID' value='%d'>
                <textarea name='commentText' rows='4' cols='50' placeholder='Write your comment here...'></textarea><br>
                <input type='submit' name='submitComment' value='Submit Comment'>
            </form>
            <hr>",
            $story['StoryID']);
    }
    echo "</ul>\n";
    echo "</div>";
}

if(isset($_POST['commentText'])){
    $storyID = $_POST['storyID'];
    $commentText = $_POST['commentText'];

    $stmt = $pdo->prepare("insert into Comments (StoryID, Username, Body) values (?, ?, ?)");
    if($stmt){
        if($stmt->execute([$storyID, $username, $commentText])){
            // echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
            //         Successfully created comment. </div>";
                    header("Location: news.php");
                    exit();
                
        }else {
            echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
                Error creating comment.
                </div>";
        }
    }
    else{
        echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
                Error preparing comment.
                </div>";
    }
}

viewStories($pdo);

function viewComments($pdo, $storyID){
    // Prepare the query to fetch comments
    $stmt = $pdo->prepare("SELECT CommentID, Username, Body FROM Comments WHERE StoryID = ? ORDER BY CommentID");

    if($stmt){
        if($stmt->execute([$storyID])){
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<ul>Comments\n";
            foreach($comments as $comment){
                echo "<li><strong>Username:</strong> " . htmlspecialchars($comment['Username']) . "<br>";
                echo "<strong>Body:</strong> " . htmlspecialchars($comment['Body']) . "<br>";
                
                // If the logged-in user is the author of the comment, show edit/delete options
                if ($comment['Username'] == $_SESSION['username']) {
                    echo '<form method="GET" action="edit.php" style="display:inline;">
                    <input type="hidden" name="id" value="' . htmlspecialchars($comment['CommentID']) . '">
                    <input type="hidden" name="type" value="comment">
                    <button type="submit" name="edit">Edit</button>
                    </form>';

                    echo '<form method="GET" action="delete.php" style="display:inline;">
                    <input type="hidden" name="id" value="' . htmlspecialchars($comment['CommentID']) . '">
                    <input type="hidden" name="type" value="comment">
                    <button type="submit" name="delete">Delete</button>
                    </form>';
            
                }
                echo "</li>\n";
            }
            echo "</ul>\n";
            

        } else {
            // Display an error message if query fails
            echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
                Error viewing comments.
                </div>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
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

        #logoutButton{
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
            right: 0vw;
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
            right: 0vw;
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

<form action="news.php" method="POST" id="logoutForm">
    <input type="submit" id="logoutButton" name="logout" value="Log out">
</form>


<!-- Create a News Story -->
<form action="news.php" method="POST" id="createStory">
    Title: <input type = "text" name="newsTitle"><br>
    Body: <textarea name='newsBody' rows='4' cols='50'></textarea><br>
    Link: <input type = "text" name="newsLink"><br>
    Genre: <select name = "newsGenre">
        <option value = "General"> General</option>
        <option value = "Politics"> Politics</option>
        <option value = "Business"> Business</option>
        <option value = "Sports"> Sports</option>
        <option value = "Science"> Science</option>
        <option value = "Entertainment"> Entertainment</option>
    </select><br>
    <input type = "submit" value="Create New Story">
</form>

<form action="news.php" method="POST" id="genreFilter">
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

</body>
</html>
