<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection settings
require_once '../config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get stats
    // Total anime
    $animeCount = $pdo->query("SELECT COUNT(*) FROM anime")->fetchColumn();
    
    // Total seasons
    $seasonCount = $pdo->query("SELECT COUNT(*) FROM seasons")->fetchColumn();
    
    // Total episodes
    $episodeCount = $pdo->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
    
    // Premium episodes
    $premiumCount = $pdo->query("SELECT COUNT(*) FROM episodes WHERE is_premium = 1")->fetchColumn();
    
    // Active subscriptions
    $activeSubsCount = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
    
    // Recent anime
    $recentAnime = $pdo->query("
        SELECT id, title, release_year 
        FROM anime 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Latest episodes
    $latestEpisodes = $pdo->query("
        SELECT e.id, e.title, e.episode_number, s.season_number, a.title as anime_title, e.created_at
        FROM episodes e
        JOIN seasons s ON e.season_id = s.id
        JOIN anime a ON s.anime_id = a.id
        ORDER BY e.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Provide more specific error messages
    if ($e->getCode() == 1049) {
        $error = "Database '$dbname' not found. Please check your database configuration.";
    } elseif ($e->getCode() == 1045) {
        $error = "Access denied for user '$username'. Invalid database credentials.";
    } elseif ($e->getCode() == 2002) {
        $error = "Cannot connect to database server at '$host'. Server might be down or unreachable.";
    } elseif ($e->getCode() == '42S02') {
        $error = "Table not found. Please run the database setup script first.";
    } else {
        $error = 'Database error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AnimeElite Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Admin Navigation -->
    <nav class="navbar py-3 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-xl font-bold logo-text">AnimeElite Admin</a>
                <div class="ml-8 hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-white font-medium">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white transition-colors duration-200">Users</a>
                    <a href="subscription_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Subscriptions</a>
                    <a href="anime_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Anime Management</a>
                    <a href="coupons.php" class="text-gray-300 hover:text-white transition-colors duration-200">Coupons</a>
                    <a href="db_status.php" class="text-gray-300 hover:text-white transition-colors duration-200">DB Status</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-300"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-md text-sm transition-colors duration-200">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="stat-card bg-gradient-to-br from-purple-900 to-primary-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-medium text-gray-300">Total Anime</h3>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($animeCount ?? 0); ?></p>
                <a href="anime_management.php" class="text-xs text-gray-300 mt-4 block hover:text-white">View All &rarr;</a>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-blue-900 to-blue-700 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-medium text-gray-300">Total Seasons</h3>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($seasonCount ?? 0); ?></p>
                <a href="anime_management.php" class="text-xs text-gray-300 mt-4 block hover:text-white">View All &rarr;</a>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-green-900 to-green-700 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-medium text-gray-300">Total Episodes</h3>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($episodeCount ?? 0); ?></p>
                <a href="anime_management.php" class="text-xs text-gray-300 mt-4 block hover:text-white">View All &rarr;</a>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-yellow-900 to-yellow-700 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-medium text-gray-300">Premium Episodes</h3>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($premiumCount ?? 0); ?></p>
                <span class="text-xs text-gray-300 mt-1 block"><?php echo ($episodeCount > 0) ? round(($premiumCount / $episodeCount) * 100) . '% of total' : '0% of total'; ?></span>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-pink-900 to-red-700 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-medium text-gray-300">Active Subscriptions</h3>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($activeSubsCount ?? 0); ?></p>
                <a href="subscription_management.php" class="text-xs text-gray-300 mt-4 block hover:text-white">View All &rarr;</a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-gray-900 rounded-xl p-6 mb-8 shadow-lg">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="anime_management.php?action=add" class="bg-gray-800 hover:bg-gray-700 rounded-lg p-4 text-center transition-colors duration-200">
                    <div class="text-primary-400 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <span>Add New Anime</span>
                </a>
                
                <a href="anime_management.php?action=add_episode" class="bg-gray-800 hover:bg-gray-700 rounded-lg p-4 text-center transition-colors duration-200">
                    <div class="text-blue-400 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <span>Add New Episode</span>
                </a>
                
                <a href="users.php" class="bg-gray-800 hover:bg-gray-700 rounded-lg p-4 text-center transition-colors duration-200">
                    <div class="text-green-400 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span>Manage Users</span>
                </a>
                
                <a href="subscription_management.php" class="bg-gray-800 hover:bg-gray-700 rounded-lg p-4 text-center transition-colors duration-200">
                    <div class="text-yellow-400 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                    <span>Manage Subscriptions</span>
                </a>
            </div>
        </div>
        
        <!-- Content Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Recent Anime -->
            <div class="bg-gray-900 rounded-xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Recently Added Anime</h2>
                <?php if (isset($recentAnime) && count($recentAnime) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($recentAnime as $anime): ?>
                            <div class="bg-gray-800 rounded-lg p-4 flex justify-between items-center">
                                <div>
                                    <h3 class="font-medium"><?php echo htmlspecialchars($anime['title']); ?></h3>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($anime['release_year']); ?></p>
                                </div>
                                <a href="anime_management.php?anime_id=<?php echo $anime['id']; ?>" class="text-primary-400 hover:text-primary-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400 text-center py-4">No anime found.</p>
                <?php endif; ?>
                <a href="anime_management.php" class="block text-center mt-4 text-primary-400 hover:text-primary-300">View all anime</a>
            </div>
            
            <!-- Latest Episodes -->
            <div class="bg-gray-900 rounded-xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Latest Episodes</h2>
                <?php if (isset($latestEpisodes) && count($latestEpisodes) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($latestEpisodes as $episode): ?>
                            <div class="bg-gray-800 rounded-lg p-4">
                                <h3 class="font-medium"><?php echo htmlspecialchars($episode['title']); ?></h3>
                                <p class="text-sm text-gray-400">
                                    <?php echo htmlspecialchars($episode['anime_title']); ?> - 
                                    Season <?php echo htmlspecialchars($episode['season_number']); ?>, 
                                    Episode <?php echo htmlspecialchars($episode['episode_number']); ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Added <?php echo date('M j, Y', strtotime($episode['created_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400 text-center py-4">No episodes found.</p>
                <?php endif; ?>
                <a href="anime_management.php" class="block text-center mt-4 text-primary-400 hover:text-primary-300">View all episodes</a>
            </div>
        </div>
    </div>
    
    <script>
        // Simple animation for stat cards when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html> 