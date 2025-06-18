<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_booking'])) {
        $booking_id = $_POST['booking_id'];
        $db->bookings->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($booking_id)],
            [
                '$set' => [
                    'status' => 'approved',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]
        );
        $_SESSION['message'] = "Booking approved successfully!";
        header("Location: admin_dashboard.php");
        exit;
    } elseif (isset($_POST['reject_booking'])) {
        $booking_id = $_POST['booking_id'];
        $db->bookings->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($booking_id)],
            [
                '$set' => [
                    'status' => 'rejected',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]
        );
        $_SESSION['message'] = "Booking rejected successfully!";
        header("Location: admin_dashboard.php");
        exit;
    }
}

try {
    // Fetch all bookings with user and service info
    $bookings = $db->bookings->aggregate([
        [
            '$lookup' => [
                'from' => 'users',
                'localField' => 'user_id',
                'foreignField' => '_id',
                'as' => 'user'
            ]
        ],
        [
            '$unwind' => [
                'path' => '$user',
                'preserveNullAndEmptyArrays' => true
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
    ])->toArray(); // Convert cursor to array for easier handling

} catch (Exception $e) {
    $_SESSION['message'] = "Error fetching bookings: " . $e->getMessage();
    $bookings = [];
}

// Get message from session and clear it
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
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
        }

        .dashboard-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
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
        }

        .nav-links a:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .nav-links a i {
            font-size: 1.1rem;
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

        .bookings-section {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            max-height: 600px;
            overflow-y: auto;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: var(--card-background);
            border-radius: var(--border-radius);
        }

        .bookings-table th,
        .bookings-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .bookings-table td.description {
            white-space: normal;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bookings-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #4b5563;
        }

        .bookings-table tr:hover {
            background-color: #f9fafb;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #fef3c7;
            color: var(--warning-color);
        }

        .status-approved {
            background-color: #d1fae5;
            color: var(--success-color);
        }

        .status-rejected {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }

        .btn-reject {
            background-color: var(--danger-color);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .no-bookings {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
            font-size: 1.1rem;
        }

        .no-bookings i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
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

            .bookings-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .bookings-table th,
            .bookings-table td {
                padding: 0.75rem;
            }
        }

        /* Add Service Form Styles */
        .add-service-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            resize: none;
            height: 150px;
            overflow-y: auto;
        }

        .add-service-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Admin Dashboard</h2>
            <div class="nav-links">
                <a href="manage_services.php"><i class="fas fa-cog"></i> Manage Services</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error-message' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bookings-section">
            <div class="section-header">
                <h3 class="section-title">All Bookings</h3>
            </div>
            
            <?php if (!empty($bookings)): ?>
            <div class="table-responsive">
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User Name</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Approval Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['user']['username'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['service']['service_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['booking_date'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['booking_time'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars($row['status'] ?? 'pending'); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if (($row['status'] ?? '') === 'approved' || ($row['status'] ?? '') === 'rejected') {
                                    echo htmlspecialchars($row['updated_at'] ?? 'N/A');
                                } else {
                                    echo 'Pending';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (($row['status'] ?? '') == 'pending'): ?>
                                <div class="action-buttons">
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to approve this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['_id']; ?>">
                                        <button type="submit" name="approve_booking" class="btn btn-approve">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reject this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['_id']; ?>">
                                        <button type="submit" name="reject_booking" class="btn btn-reject">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-bookings">
                <i class="fas fa-calendar-times"></i>
                <p>No bookings found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
