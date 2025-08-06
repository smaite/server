<?php
// No session start - this page should be accessible without authentication
// But we'll use a setup key for basic security

// Define a setup key to prevent unauthorized access
$setup_key = 'animeelite_setup2023'; // You should change this to something more secure
$key_provided = $_GET['key'] ?? '';

// Check if the key is correct or bypass if in development environment
$access_allowed = ($key_provided === $setup_key);

// Set default response
$message = '';
$success = false;
$error = '';

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_username = $_POST['db_username'] ?? '';
    $db_password = $_POST['db_password'] ?? '';
    $db_name = $_POST['db_name'] ?? 'glorious_hq';
    
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_confirm = $_POST['admin_confirm'] ?? '';
    $admin_name = $_POST['admin_name'] ?? '';
    
    // Validate form data
    if (empty($db_username)) {
        $error = 'Database username is required';
    } elseif (empty($admin_username)) {
        $error = 'Admin username is required';
    } elseif (empty($admin_password)) {
        $error = 'Admin password is required';
    } elseif ($admin_password !== $admin_confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($admin_password) < 8) {
        $error = 'Admin password must be at least 8 characters long';
    } else {
        try {
            // Connect to MySQL without specifying a database
            $pdo = new PDO("mysql:host=$db_host", $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Switch to the database
            $pdo->exec("USE `$db_name`");
            
            // Create admin_users table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `admin_users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `display_name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(255),
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `last_login` TIMESTAMP NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            
            // Hash the admin password
            $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
            
            // Check if the username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = :username");
            $stmt->bindParam(':username', $admin_username, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username already exists. Please choose another one.';
            } else {
                // Insert the new admin user
                $stmt = $pdo->prepare("
                    INSERT INTO admin_users (username, password_hash, display_name)
                    VALUES (:username, :password_hash, :display_name)
                ");
                
                $stmt->bindParam(':username', $admin_username, PDO::PARAM_STR);
                $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
                $stmt->bindParam(':display_name', $admin_name, PDO::PARAM_STR);
                $stmt->execute();
                
                // Update config.php with the new database details
                $config_path = '../config.php';
                if (file_exists($config_path)) {
                    $config_content = file_get_contents($config_path);
                    
                    // Replace database credentials
                    $config_content = preg_replace('/\$host = \'.*?\';/', "\$host = '$db_host';", $config_content);
                    $config_content = preg_replace('/\$dbname = \'.*?\';/', "\$dbname = '$db_name';", $config_content);
                    $config_content = preg_replace('/\$username = \'.*?\';/', "\$username = '$db_username';", $config_content);
                    $config_content = preg_replace('/\$password = \'.*?\';/', "\$password = '$db_password';", $config_content);
                    
                    // Write the updated config file
                    file_put_contents($config_path, $config_content);
                }
                
                $success = true;
                $message = "Admin account created successfully! You can now <a href='login.php' class='text-primary-400 hover:underline'>log in</a> with your new credentials.";
            }
            
        } catch (PDOException $e) {
            $error_code = $e->getCode();
            
            if ($error_code == 1045) {
                $error = "Access denied. The database credentials you provided are incorrect.";
            } elseif ($error_code == 2002) {
                $error = "Cannot connect to database server at '$db_host'. Server might be down or unreachable.";
            } else {
                $error = "Database error: " . $e->getMessage() . " (Code: $error_code)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin - AnimeElite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .setup-container {
            background-image: url('https://source.unsplash.com/1600x900/?anime,dark');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <div class="py-4 px-6 bg-gray-900">
        <h1 class="text-xl font-bold logo-text">AnimeElite Admin Setup</h1>
    </div>
    
    <div class="flex-grow flex items-center justify-center setup-container">
        <div class="max-w-md w-full mx-4">
            <?php if (!$access_allowed): ?>
                <div class="bg-gray-900 bg-opacity-90 backdrop-filter backdrop-blur-sm rounded-xl shadow-2xl overflow-hidden border border-gray-800 p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-purple-600">Access Restricted</h1>
                        <p class="text-gray-400 mt-2">Please provide a valid setup key to continue</p>
                    </div>
                    
                    <form method="get">
                        <div class="mb-6">
                            <label for="key" class="block text-gray-400 text-sm font-medium mb-2">Setup Key</label>
                            <input type="text" name="key" id="key" required class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                            Continue to Setup
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="../.." class="text-primary-400 hover:text-primary-300 text-sm">
                            Return to Main Site
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gray-900 bg-opacity-90 backdrop-filter backdrop-blur-sm rounded-xl shadow-2xl overflow-hidden border border-gray-800 p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-purple-600">Register Admin Account</h1>
                        <p class="text-gray-400 mt-2">Create a new admin account for AnimeElite</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-900 border border-green-700 text-white px-4 py-3 rounded mb-6">
                            <p><?php echo $message; ?></p>
                        </div>
                    <?php elseif ($error): ?>
                        <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded mb-6">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <!-- Database Connection Details -->
                        <h2 class="text-xl font-bold mb-4 text-primary-400">Database Connection</h2>
                        <div class="mb-4">
                            <label for="db_host" class="block text-gray-400 text-sm font-medium mb-2">Database Host</label>
                            <input type="text" name="db_host" id="db_host" value="localhost" class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="db_username" class="block text-gray-400 text-sm font-medium mb-2">Database Username</label>
                            <input type="text" name="db_username" id="db_username" required placeholder="Database user with CREATE privileges" class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="db_password" class="block text-gray-400 text-sm font-medium mb-2">Database Password</label>
                            <input type="password" name="db_password" id="db_password" class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-6">
                            <label for="db_name" class="block text-gray-400 text-sm font-medium mb-2">Database Name</label>
                            <input type="text" name="db_name" id="db_name" value="glorious_hq" class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <!-- Admin Account Details -->
                        <h2 class="text-xl font-bold mb-4 text-primary-400">Admin Account</h2>
                        <div class="mb-4">
                            <label for="admin_username" class="block text-gray-400 text-sm font-medium mb-2">Admin Username</label>
                            <input type="text" name="admin_username" id="admin_username" required class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="admin_password" class="block text-gray-400 text-sm font-medium mb-2">Admin Password</label>
                            <input type="password" name="admin_password" id="admin_password" required class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="admin_confirm" class="block text-gray-400 text-sm font-medium mb-2">Confirm Password</label>
                            <input type="password" name="admin_confirm" id="admin_confirm" required class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-8">
                            <label for="admin_name" class="block text-gray-400 text-sm font-medium mb-2">Display Name</label>
                            <input type="text" name="admin_name" id="admin_name" required placeholder="Administrator" class="block w-full px-4 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                            Create Admin Account
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="login.php" class="text-primary-400 hover:text-primary-300 text-sm">
                            Back to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="py-4 text-center text-gray-500 text-sm">
        &copy; 2023 AnimeElite. All rights reserved.
    </div>
</body>
</html> 