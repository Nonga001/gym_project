<!DOCTYPE html>
<html>
<head>
    <title>Gym Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: 80px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }
        .row {
            display: flex;
            justify-content: space-around;
            gap: 40px;
            flex-wrap: wrap;
        }
        .column {
            flex: 1;
            min-width: 280px;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .column h2 {
            color: #007BFF;
            margin-bottom: 20px;
        }
        .column a {
            display: block;
            margin: 15px 0;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .column a:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Online Gym Management System</h1>
        <div class="row">
            <div class="column">
                <h2>Customer</h2>
                <a href="register.php">Register</a>
                <a href="login.php">User Login</a>
            </div>
            <div class="column">
                <h2>Admin</h2>
                <a href="admin_login.php">Admin Login</a>
            </div>
        </div>
    </div>
</body>
</html>
