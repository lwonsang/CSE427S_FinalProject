<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Set AWS credentials and region
$s3 = new S3Client([
    'region' => 'us-east-1',
    'version' => 'latest',
    'credentials' => [
        'key' => getenv('AWS_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
    ],
]);

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

//this is where ML gets used
function getCategoryFromML($text) {
    $data = json_encode(['input' => $text]);

    //note: we are using host.docker.interal:5000/predict because we are running within Docker containers
    // use localhost:5000/predict when testing ML locally
    $ch = curl_init('http://ml:5000/predict');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log("cURL Error: " . curl_error($ch));
    } else {
        error_log("ML Response: " . $response);
    }

    curl_close($ch);

    $result = json_decode($response, true);
    return $result['prediction'] ?? 'General';
}



// Create news stories and add it to the Stories database
if(isset($_POST['newsTitle']) & isset($_POST['newsBody'])){
    $newsTitle = $_POST['newsTitle'];
    $newsBody = $_POST['newsBody'];
    $newsLink = $_POST['newsLink'];
    //ML is used to predict genre of new posts
    $newsGenre = getCategoryFromML($_POST['newsBody']);
    $hasMedia = 0;
    if (
        isset($_FILES['file']) &&
        $_FILES['file']['error'] === UPLOAD_ERR_OK &&
        is_uploaded_file($_FILES['file']['tmp_name'])
    ) {
        $hasMedia = 1;
    }
    

    $stmt = $pdo->prepare("insert into Stories (Body, Title, Username, Link, Genre, HasMedia) values (?, ?, ?, ?, ?, ?)");
    if($stmt){
        if($stmt->execute([$newsBody, $newsTitle, $username, $newsLink, $newsGenre, $hasMedia])){
            // echo "<div style='position: absolute; top: 22vh; left: 0; right: 0; text-align: center;'>
            //         Successfully created story.</div>";
            if ($hasMedia && isset($_FILES['file'])) {
                $storyID = $pdo->lastInsertId();
                $bucket = 'cse-427-bucket';
                $key = $username . "/" . $storyID; // S3 path
                $sourceFile = $_FILES['file']['tmp_name'];
            
                try {
                    // Upload the file
                    $result = $s3->putObject([
                        'Bucket' => $bucket,
                        'Key'    => $key,
                        'SourceFile' => $sourceFile,
                        'ACL'    => 'private',
                        'ContentType' => $_FILES['file']['type'],
                    ]);
            
                    //echo "File uploaded successfully! S3 URL: " . $result['ObjectURL'];
                } catch (AwsException $e) {
                    echo "Upload failed: " . $e->getMessage();
                }
            }
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
        $stmt = $pdo->prepare("SELECT StoryID, Title, Body, Username, Link, Genre, HasMedia FROM Stories where Genre = ? ORDER BY StoryID");
        if($stmt){
            if($stmt->execute([$selectedGenre])){
                $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    else{
        $stmt = $pdo->prepare("SELECT StoryID, Title, Body, Username, Link, Genre, HasMedia FROM Stories ORDER BY StoryID");
        if($stmt){
            if($stmt->execute()){
                $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    echo "<div class='storiesList'>";
    echo "<ul>";
    foreach ($stories as $story) {
        $imageHtml = '';

        if (!empty($story['HasMedia']) && $story['HasMedia']) {
            $s3 = new S3Client([
                'region' => 'us-east-1',
                'version' => 'latest',
                'credentials' => [
                    'key' => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);


            $bucket = 'cse-427-bucket';
            $key = $story['Username'] . '/' . $story['StoryID']; 
            $expires = '+10 minutes';

            try {
                $cmd = $s3->getCommand('GetObject', [
                    'Bucket' => $bucket,
                    'Key' => $key,
                ]);
                $request = $s3->createPresignedRequest($cmd, $expires);
                $presignedUrl = (string) $request->getUri();
                $imageHtml = "<img src='$presignedUrl' alt='Story image' style='max-width:300px'><br>";
            } catch (Exception $e) {
                $imageHtml = "<em>Image not available</em><br>";
            }
        }

        if($story['Link'] != ''){
            $link = "https://" . $story['Link'];
        }
        else{
            $link = '';
        }
        printf("<li><strong>Title:</strong> %s <br><strong>Body:</strong> %s <br><strong>Author:</strong> %s <br><strong>Link:</strong> <a href='%s'>%s</a> <br><strong>Genre:</strong> %s <br>%s</li><hr>",
            htmlspecialchars($story['Title']),
            htmlspecialchars($story['Body']),
            htmlspecialchars($story['Username']),
            htmlspecialchars($link),
            htmlspecialchars($story['Link']),
            htmlspecialchars($story['Genre']),
            $imageHtml 
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
<form action="news.php" method="POST" id="createStory" enctype="multipart/form-data">
    Title: <input type = "text" name="newsTitle"><br>
    Body: <textarea name='newsBody' rows='4' cols='50'></textarea><br>
    Link: <input type = "text" name="newsLink"><br>
    <!-- With ML we don't need the Genre selector because ML already selects the Genre for us!! -->
    <!-- Genre: <select name = "newsGenre">
        <option value = "General"> General</option>
        <option value = "Politics"> Politics</option>
        <option value = "Business"> Business</option>
        <option value = "Sports"> Sports</option>
        <option value = "Science"> Science</option>
        <option value = "Entertainment"> Entertainment</option>
    </select><br> -->
    <input type="file" name="file" /><br>

    <input type = "submit" value="Create New Story">
</form>

<form action="news.php" method="POST" id="genreFilter">
    Filter Genre:
    <select name="genres" multiple>
        <option value="General">General</option>
        <option value="Adult">Adult</option>
        <option value="Art & Design">Art & Design</option>
        <option value="Software Dev">Software Dev</option>
        <option value="Crime & Law">Crime & Law</option>
        <option value="Education & Jobs">Education & Jobs</option>
        <option value="Hardware">Hardware</option>
        <option value="Entertainment">Entertainment</option>
        <option value="Social Life">Social Life</option>
        <option value="Fashion & Beauty">Fashion & Beauty</option>
        <option value="Finance & Business">Finance & Business</option>
        <option value="Food & Dining">Food & Dining</option>
        <option value="Games">Games</option>
        <option value="Health">Health</option>
        <option value="History">History</option>
        <option value="Home & Hobbies">Home & Hobbies</option>
        <option value="Industrial">Industrial</option>
        <option value="Literature">Literature</option>
        <option value="Politics">Politics</option>
        <option value="Religion">Religion</option>
        <option value="Science & Tech.">Science & Tech.</option>
        <option value="Software">Software</option>
        <option value="Sports & Fitness">Sports & Fitness</option>
        <option value="Transportation">Transportation</option>
        <option value="Travel">Travel</option>
    </select>
    <input type="submit" name="filter" value="Filter Stories">
    <button type="submit" name="clear">Clear Filters</button>
</form>


</body>
</html>
