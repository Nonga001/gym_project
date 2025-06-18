<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include "db.php";

$user_id = $_SESSION['user_id'];
$service_id = $_POST['service_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_datetime = $_POST['booking_date'];

    $result = $db->bookings->insertOne([
        'user_id' => $user_id,
        'service_id' => $service_id,
        'booking_date' => $booking_datetime
    ]);

    if ($result->getInsertedCount() == 1) {
        echo "<script>alert('Service booked successfully!'); window.location.href='user_dashboard.php';</script>";
        exit;
    } else {
        echo "<script>alert('Booking failed!'); window.location.href='user_dashboard.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Service</title>
    <style>
        body {
            font-family: Arial;
            padding: 40px;
            text-align: center;
        }
        input, button {
            padding: 10px;
            margin: 15px;
            width: 250px;
        }
    </style>
</head>
<body>
    <h2>Book Your Session</h2>
    <form method="POST">
        <label>Select Booking Date & Time:</label><br>
        <input type="datetime-local" name="booking_date" required><br>
        <button type="submit">Confirm Booking</button>
    </form>
</body>
</html>
