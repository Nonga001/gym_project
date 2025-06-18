<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit;
}
include "db.php";

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_service'])) {
    $service_id = $_POST['service_id'];
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    
    // Insert booking with created_at timestamp
    $result = $db->bookings->insertOne([
        'user_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id']),
        'service_id' => new MongoDB\BSON\ObjectId($service_id),
        'booking_date' => $booking_date,
        'booking_time' => $booking_time,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => null
    ]);
    
    if ($result->getInsertedCount() > 0) {
        $_SESSION['message'] = "Service booked successfully!";
    } else {
        $_SESSION['message'] = "Error booking service.";
    }
    header("Location: view_services.php");
    exit;
}

// Handle unbooking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unbook_service'])) {
    $booking_id = $_POST['booking_id'];
    $db->bookings->deleteOne(['_id' => new MongoDB\BSON\ObjectId($booking_id)]);
    $_SESSION['message'] = "Booking cancelled successfully!";
    header("Location: view_services.php");
    exit;
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    if (isset($_SESSION['user_id'])) {
        try {
            // Delete all user's bookings
            $db->bookings->deleteMany(['user_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
            
            // Delete the user account
            $result = $db->users->deleteOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
            
            if ($result->getDeletedCount() > 0) {
                session_destroy();
                header("Location: login.php?message=Account deleted successfully");
                exit;
            } else {
                $_SESSION['message'] = "Failed to delete account";
            }
        } catch (Exception $e) {
            $_SESSION['message'] = "Error deleting account: " . $e->getMessage();
        }
    }
}

// Fetch all services
$services = $db->services->find();

// Fetch user's bookings with service details
$user_bookings = $db->bookings->aggregate([
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
        '$unwind' => '$service'
    ],
    [
        '$sort' => ['booking_date' => -1, 'booking_time' => -1]
    ]
])->toArray();

// Available time slots
$time_slots = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];

// Get message from session and clear it
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Services</title>
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

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .service-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--primary-color);
        }

        .service-card:hover {
            transform: translateY(-5px);
        }

        .service-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .service-card p {
            margin-bottom: 1rem;
            color: #4b5563;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--success-color);
            margin: 1rem 0;
        }

        .btn {
            background-color: var(--primary-color);
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
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn i {
            font-size: 1.1rem;
        }

        .bookings-section {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 3rem;
            box-shadow: var(--shadow);
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
            overflow: hidden;
        }

        .bookings-table th,
        .bookings-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
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

        .account-section {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 3rem;
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--card-background);
            margin: 5% auto;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: var(--shadow);
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--danger-color);
        }

        .service-details {
            background-color: #f9fafb;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4b5563;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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

            .services-grid {
                grid-template-columns: 1fr;
            }

            .bookings-table {
                display: block;
                overflow-x: auto;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .service-card {
                padding: 1rem;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Available Services</h2>
            <div class="nav-links">
                <a href="user_dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error-message' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="service-card">
                <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                <p><?php echo htmlspecialchars($service['description']); ?></p>
                <p class="price">KSH <?php echo number_format($service['price'], 2); ?></p>
                <button onclick="openBookingModal('<?php echo $service['_id']; ?>', 
                                              '<?php echo htmlspecialchars($service['service_name']); ?>', 
                                              '<?php echo htmlspecialchars($service['description']); ?>', 
                                              '<?php echo htmlspecialchars($service['price']); ?>')" 
                        class="btn">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bookings-section">
            <div class="section-header">
                <h3 class="section-title">Your Bookings</h3>
            </div>
            <?php if (!empty($user_bookings)): ?>
            <div class="table-responsive">
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Approval Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['service']['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                            <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($booking['status'] === 'approved' || $booking['status'] === 'rejected') {
                                    echo htmlspecialchars($booking['updated_at'] ?? 'N/A');
                                } else {
                                    echo 'Pending';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'pending'): ?>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['_id']; ?>">
                                    <button type="submit" name="unbook" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
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
                <p>You have no bookings yet.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="account-section">
            <div class="section-header">
                <h3 class="section-title">Account Management</h3>
            </div>
            <div class="account-actions">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone and will delete all your bookings.');">
                    <button type="submit" name="delete_account" class="btn btn-danger">
                        <i class="fas fa-user-times"></i> Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Book Service</h2>
            <div class="service-details" id="modalServiceDetails">
                <!-- Service details will be populated here -->
            </div>
            <form method="POST" action="" id="bookingForm">
                <input type="hidden" name="service_id" id="service_id">
                <div class="form-group">
                    <label for="booking_date">Select Date:</label>
                    <input type="date" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="booking_time">Select Time:</label>
                    <select id="booking_time" name="booking_time" required>
                        <option value="">Select a time</option>
                        <?php
                        foreach ($time_slots as $time) {
                            echo "<option value='$time'>$time</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="book_service" class="btn">
                    <i class="fas fa-check"></i> Confirm Booking
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('bookingModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });

    function openBookingModal(serviceId, serviceName, serviceDescription, servicePrice) {
        document.getElementById('bookingModal').style.display = 'block';
        document.getElementById('service_id').value = serviceId;
        
        const serviceDetails = document.getElementById('modalServiceDetails');
        serviceDetails.innerHTML = `
            <h3>${serviceName}</h3>
            <div class="service-info">
                <p><strong>Description:</strong> ${serviceDescription}</p>
                <p class="service-price">KSH ${servicePrice}</p>
            </div>
        `;
        
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('booking_date').min = today;
    }

    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const date = document.getElementById('booking_date').value;
        const time = document.getElementById('booking_time').value;
        
        if (!date || !time) {
            e.preventDefault();
            alert('Please select both date and time for your appointment.');
        }
    });
    </script>
</body>
</html> 