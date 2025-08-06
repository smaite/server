<?php
// Start session
session_start();

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Database connection settings
require_once '../config.php';

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Connect to database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if admin exists
            $stmt = $pdo->prepare("SELECT id, username, password_hash, display_name FROM admin_users WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Set admin session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['display_name'];
                
                // Redirect to dashboard
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            // Provide more specific error messages
            if ($e->getCode() == 1049) {
                $error = "Database '$dbname' not found. Please check your database configuration.";
            } elseif ($e->getCode() == 1045) {
                $error = "Access denied for user '$username'. Invalid database credentials.";
            } elseif ($e->getCode() == 2002) {
                $error = "Cannot connect to database server at '$host'. Server might be down or unreachable.";
            } else {
                $error = 'Database connection error: ' . $e->getMessage();
            }
        }
    }
}

// For development/demo purposes - create a default admin if none exists
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin table exists, if not create it
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
    
    // Check if any admin exists
    $adminCount = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    
    if ($adminCount === 0) {
        // Create default admin (admin/admin123)
        $defaultUsername = 'admin';
        $defaultPassword = 'admin123';
        $defaultDisplayName = 'Administrator';
        
        $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, password_hash, display_name)
            VALUES (:username, :password_hash, :display_name)
        ");
        
        $stmt->bindParam(':username', $defaultUsername, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(':display_name', $defaultDisplayName, PDO::PARAM_STR);
        $stmt->execute();
        
        // Display a message about the default admin
        $defaultAdminCreated = true;
    }
} catch (PDOException $e) {
    // Silent fail - this is just for development convenience
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AnimeElite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .login-container {
            background-image: url('https://source.unsplash.com/1600x900/?anime,japan');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <div class="flex-grow flex items-center justify-center login-container">
        <div class="max-w-md w-full mx-4">
            <div class="bg-gray-900 bg-opacity-90 backdrop-filter backdrop-blur-sm rounded-xl shadow-2xl overflow-hidden border border-gray-800 p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-purple-600">AnimeElite Admin</h1>
                    <p class="text-gray-400 mt-2">Sign in to access the admin dashboard</p>
                </div>
                
                <?php if (isset($defaultAdminCreated)): ?>
                    <div class="bg-blue-900 border border-blue-700 text-white px-4 py-3 rounded mb-6">
                        <p class="font-bold">Default admin account created:</p>
                        <p>Username: admin</p>
                        <p>Password: admin123</p>
                        <p class="mt-2 text-xs">Please change this password after logging in!</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-6">
                        <label for="username" class="block text-gray-400 text-sm font-medium mb-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input id="username" name="username" type="text" required class="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Enter username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <label for="password" class="block text-gray-400 text-sm font-medium mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" required class="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Enter password">
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                        Sign In to Admin
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="../.." class="text-primary-400 hover:text-primary-300 text-sm">
                        Return to Main Site
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="py-4 text-center text-gray-500 text-sm">
        &copy; 2023 AnimeElite. All rights reserved.
    </div>
</body>
</html> 