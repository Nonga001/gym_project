<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}
include "db.php";

// Handle service deletion
if (isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];
    $db->services->deleteOne(['_id' => new MongoDB\BSON\ObjectId($service_id)]);
    $message = "Service deleted successfully!";
}

// Handle service update
if (isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    
    if (!empty($service_name) && !empty($description)) {
        $db->services->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($service_id)],
            ['$set' => [
                'service_name' => $service_name,
                'description' => $description
            ]]
        );
        $message = "Service updated successfully!";
    }
}

// Handle new service addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']); // Convert to float for proper storage
    
    $result = $db->services->insertOne([
        'service_name' => $service_name,
        'description' => $description,
        'price' => $price,
        'currency' => 'KSH',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result->getInsertedCount() > 0) {
        $_SESSION['message'] = "Service added successfully!";
    } else {
        $_SESSION['message'] = "Error adding service.";
    }
    header("Location: manage_services.php");
    exit;
}

// Fetch all services
$services = $db->services->find();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Services</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        h2 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
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
        }

        .nav-links a:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .add-service-form {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow);
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
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .price-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .currency-symbol {
            position: absolute;
            left: 1rem;
            color: #6b7280;
            font-weight: 500;
        }

        .price-input input {
            padding-left: 4rem;
        }

        .service-list {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
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
            display: inline-block;
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-edit {
            background-color: var(--warning-color);
        }

        .btn-edit:hover {
            background-color: #b45309;
        }

        .message {
            background-color: #d1fae5;
            color: var(--success-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .error-message {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .add-service-form,
            .service-list {
                padding: 1.5rem;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .nav-links {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-links a {
                text-align: center;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 1.5rem;
            }

            .form-group input,
            .form-group textarea {
                font-size: 16px; /* Prevent zoom on mobile */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2>Manage Services</h2>
        
        <?php if (isset($message)) echo "<div class='message'>{$message}</div>"; ?>

        <div class="add-service-form">
            <h3>Add New Service</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="service_name">Service Name:</label>
                    <input type="text" id="service_name" name="service_name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (KSH):</label>
                    <div class="price-input">
                        <span class="currency-symbol">KSH</span>
                        <input type="number" id="price" name="price" min="0" step="0.01" required>
                    </div>
                </div>
                <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
            </form>
        </div>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="service-card">
                <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                <p><?php echo htmlspecialchars($service['description']); ?></p>
                <div class="service-actions">
                    <button onclick="editService('<?php echo $service['_id']; ?>', '<?php echo htmlspecialchars($service['service_name']); ?>', '<?php echo htmlspecialchars($service['description']); ?>')" class="btn btn-edit">Edit</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="service_id" value="<?php echo $service['_id']; ?>">
                        <button type="submit" name="delete_service" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this service?')">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; width: 80%; max-width: 500px; margin: 50px auto; padding: 20px; border-radius: 10px;">
            <h3>Edit Service</h3>
            <form method="POST" action="">
                <input type="hidden" name="service_id" id="edit_service_id">
                <input type="text" name="service_name" id="edit_service_name" placeholder="Service Name" required>
                <textarea name="description" id="edit_description" placeholder="Service Description" rows="4" required></textarea>
                <button type="submit" name="update_service" class="btn btn-edit">Update Service</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-delete">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function editService(id, name, description) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_service_id').value = id;
            document.getElementById('edit_service_name').value = name;
            document.getElementById('edit_description').value = description;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html> 