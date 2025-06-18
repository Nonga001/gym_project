<?php
include "db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $user = $db->users->findOne(['email' => $email]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = (string)$user['_id'];
        $_SESSION['username'] = $user['username'];
        header("Location: user_dashboard.php");
        exit;
    } else {
        $error = "Incorrect email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <style>
        body {
            background: #f0f2f5;
            font-family: "Segoe UI", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-box {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-box h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .login-box input[type="email"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        .login-box input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-box input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .footer-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .footer-link a {
            color: #007BFF;
            text-decoration: none;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>User Login</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
        <div class="footer-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
