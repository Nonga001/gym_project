<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit;
}
include "db.php";

// Fetch user data
$user = $db->users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);

if (!$user) {
    header("Location: login.php");
    exit;
}

// Format the date
$dateJoined = $user['created_at'] instanceof MongoDB\BSON\UTCDateTime 
    ? date('F j, Y', $user['created_at']->toDateTime()->getTimestamp())
    : date('F j, Y', strtotime($user['created_at']));

// Fetch user's bookings
$bookings = $db->bookings->aggregate([
    [
        '$match' => [
            'user_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])
        ]
    ],
    [
        '$lookup' => [
            'from' => 'services',
            'localField' => 'service_id',
            'foreignField' => '_id',
            'as' => 'service'
        ]
    ],
    [
        '$unwind' => [
            'path' => '$service',
            'preserveNullAndEmptyArrays' => true
        ]
    ],
    [
        '$sort' => ['booking_date' => -1]
    ]
]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-color: #1f2937;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            background-color: var(--card-background);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(37, 99, 235, 0.1), rgba(37, 99, 235, 0.05));
            z-index: 0;
        }

        .dashboard-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            background-color: var(--card-background);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .nav-links a:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }

        .nav-links a i {
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .dashboard-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(37, 99, 235, 0.05));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover .card-icon {
            transform: scale(1.1);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(37, 99, 235, 0.1));
        }

        .card-title {
            font-size: 1.2rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .card-content {
            color: #4b5563;
            margin-bottom: 1.5rem;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -1px rgba(37, 99, 235, 0.1);
        }

        .btn i {
            font-size: 1.1rem;
        }

        .btn-secondary {
            background-color: #6b7280;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        .message {
            background-color: #d1fae5;
            color: var(--success-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .error-message {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .user-info {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(37, 99, 235, 0.1);
            position: relative;
            overflow: hidden;
        }

        .user-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(37, 99, 235, 0.05), transparent);
            z-index: 0;
        }

        .user-info h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1.5rem;
            background-color: #f9fafb;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .info-item:hover {
            transform: translateX(5px);
            background-color: #f3f4f6;
        }

        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .info-value {
            font-size: 1.125rem;
            color: var(--text-color);
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-links {
                flex-direction: column;
            }

            .nav-links a {
                width: 100%;
                justify-content: center;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 1.5rem;
            }

            .card-title {
                font-size: 1.1rem;
            }

            .dashboard-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <div class="nav-links">
                <a href="view_services.php"><i class="fas fa-dumbbell"></i> Book Services</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error-message' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="card-title">Book a Service</h3>
                </div>
                <p class="card-content">Schedule your next workout session or book any of our premium services.</p>
                <a href="view_services.php" class="btn">
                    <i class="fas fa-plus"></i> Book Now
                </a>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="card-title">Booking History</h3>
                </div>
                <p class="card-content">View and manage your existing bookings and appointments.</p>
                <a href="view_services.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> View Bookings
                </a>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3 class="card-title">Account Settings</h3>
                </div>
                <p class="card-content">Manage your account settings and preferences.</p>
                <a href="view_services.php" class="btn btn-secondary">
                    <i class="fas fa-cog"></i> Manage Account
                </a>
            </div>
        </div>

        <div class="user-info">
            <h3>Account Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Joined</div>
                    <div class="info-value"><?php echo $dateJoined; ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
