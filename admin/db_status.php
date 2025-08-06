<?php
// Start session for admin authentication
session_start();

// Include database configuration
require_once '../config.php';

// Check if admin is logged in, but allow access with a special parameter for initial setup
$allowAccess = isset($_SESSION['admin_id']) || (isset($_GET['setup']) && $_GET['setup'] === 'initial');

if (!$allowAccess) {
    header('Location: login.php');
    exit();
}

// Test database connection
$dbStatus = testDatabaseConnection();

// Check tables if connection is successful
$tablesStatus = [];
if ($dbStatus['success']) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // List of tables to check
        $tables = ['anime', 'seasons', 'episodes', 'subscriptions', 'coupons', 'admin_users'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
                $tablesStatus[$table] = [
                    'exists' => true,
                    'message' => 'Table exists',
                    'count' => $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn()
                ];
            } catch (PDOException $e) {
                $tablesStatus[$table] = [
                    'exists' => false,
                    'message' => 'Table does not exist: ' . $e->getMessage(),
                    'count' => 0
                ];
            }
        }
    } catch (PDOException $e) {
        // Connection worked but something else failed
    }
}

// Handle database setup if requested
$setupResult = null;
if (isset($_POST['action']) && $_POST['action'] === 'setup_db') {
    try {
        // Connect without specifying database first
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Select the database
        $pdo->exec("USE `$dbname`");
        
        // Run the setup SQL
        $sqlFile = file_get_contents('../anime_db_setup.sql');
        $pdo->exec($sqlFile);
        
        $setupResult = [
            'success' => true,
            'message' => 'Database and tables created successfully.'
        ];
        
        // Refresh database status
        $dbStatus = testDatabaseConnection();
        
    } catch (PDOException $e) {
        $setupResult = [
            'success' => false,
            'message' => 'Error setting up database: ' . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status - AnimeElite Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Admin Navigation -->
    <?php if (isset($_SESSION['admin_id'])): ?>
    <nav class="navbar py-3 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-xl font-bold logo-text">AnimeElite Admin</a>
                <div class="ml-8 hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors duration-200">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white transition-colors duration-200">Users</a>
                    <a href="subscription_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Subscriptions</a>
                    <a href="anime_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Anime Management</a>
                    <a href="db_status.php" class="text-white font-medium">Database Status</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-300"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-md text-sm transition-colors duration-200">Logout</a>
            </div>
        </div>
    </nav>
    <?php else: ?>
    <div class="py-4 px-6 bg-gray-900">
        <h1 class="text-xl font-bold logo-text">AnimeElite Database Setup</h1>
    </div>
    <?php endif; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Database Status</h1>
        
        <!-- Connection Status -->
        <div class="bg-gray-900 rounded-xl p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Connection Status</h2>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <div class="mr-3">
                        <?php if ($dbStatus['success']): ?>
                            <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        <?php else: ?>
                            <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="font-bold"><?php echo $dbStatus['success'] ? 'Connected' : 'Connection Failed'; ?></h3>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($dbStatus['message']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-800 p-4 rounded-lg mb-4">
                <h3 class="font-bold mb-2">Connection Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm"><span class="text-gray-400">Host:</span> <?php echo htmlspecialchars($host); ?></p>
                        <p class="text-sm"><span class="text-gray-400">Database:</span> <?php echo htmlspecialchars($dbname); ?></p>
                    </div>
                    <div>
                        <p class="text-sm"><span class="text-gray-400">Username:</span> <?php echo htmlspecialchars($username); ?></p>
                        <p class="text-sm"><span class="text-gray-400">Password:</span> ********</p>
                    </div>
                </div>
            </div>
            
            <?php if (!$dbStatus['success']): ?>
                <form method="post" class="mt-6">
                    <input type="hidden" name="action" value="setup_db">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        Setup Database
                    </button>
                    <p class="text-xs text-gray-400 mt-2">This will attempt to create the database and required tables.</p>
                </form>
            <?php endif; ?>
            
            <?php if ($setupResult): ?>
                <div class="mt-4 <?php echo $setupResult['success'] ? 'bg-green-900' : 'bg-red-900'; ?> border <?php echo $setupResult['success'] ? 'border-green-700' : 'border-red-700'; ?> rounded-lg p-4">
                    <p><?php echo htmlspecialchars($setupResult['message']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tables Status -->
        <?php if ($dbStatus['success'] && !empty($tablesStatus)): ?>
            <div class="bg-gray-900 rounded-xl p-6">
                <h2 class="text-xl font-bold mb-4">Tables Status</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-800">
                                <th class="py-2 px-4 text-left">Table</th>
                                <th class="py-2 px-4 text-left">Status</th>
                                <th class="py-2 px-4 text-left">Records</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tablesStatus as $table => $status): ?>
                                <tr class="border-b border-gray-800">
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($table); ?></td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <?php if ($status['exists']): ?>
                                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>OK</span>
                                            <?php else: ?>
                                                <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span>Missing</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4"><?php echo $status['exists'] ? number_format($status['count']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Back to Admin -->
        <?php if (isset($_SESSION['admin_id'])): ?>
            <div class="mt-8 text-center">
                <a href="index.php" class="text-primary-400 hover:text-primary-300">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="mt-8 text-center">
                <a href="login.php" class="text-primary-400 hover:text-primary-300">Go to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 