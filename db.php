<?php
// db.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

try {
    $client = new MongoDB\Client(MONGODB_URI);
    
    // Select the database
    $db = $client->{DB_NAME};
    
    // Make the database connection available globally
    $GLOBALS['db'] = $db;
    
    // Ensure a default admin exists for first-time login
    $defaultAdmin = $db->admin->findOne(['email' => ADMIN_EMAIL]);
    if (!$defaultAdmin) {
        $db->admin->insertOne([
            'email' => ADMIN_EMAIL,
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'username' => 'admin',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
