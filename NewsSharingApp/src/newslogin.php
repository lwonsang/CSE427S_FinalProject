<?php
session_start();
require 'db.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare SQL statement to retrieve the user
    $stmt = $pdo->prepare("SELECT Password FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password
    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['username'] = $username; // Set session variable
        //echo "Login successful! Welcome, " . htmlspecialchars($username) . "!";
        header("Location: news.php");
        exit();
    } else {
        echo "Invalid username or password."; // Changed to a generic error message
    }
} 

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $password = trim($_POST['reg_password']);

    // Validate inputs
    if (!empty($username) && !empty($password)) {
        // Check if the username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ?");
        $stmt->execute([$username]);
        $userCount = $stmt->fetchColumn();

        if ($userCount > 0) {
            echo "Username already exists. Please choose a different one.";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Prepare SQL statement
            $stmt = $pdo->prepare("INSERT INTO Users (Username, Password) VALUES (?, ?)");
            if ($stmt->execute([$username, $hashedPassword])) {
                echo "Registration successful! You can now log in.";
            } else {
                echo "Error registering user. Please try again.";
            }
        }
    } else {
        echo "Username and password cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 2px;
            margin: 10px 0;
            border: .5px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .message {
            text-align: center;
            color: red;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Login Form -->
        <h2> News Site Login</h2>
        <h2>Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <!-- Registration Form -->
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="reg_username" placeholder="Username" required>
            <input type="password" name="reg_password" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
        </form>
    </div>
</body>
</html>
