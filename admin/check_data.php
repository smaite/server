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
    
    // Check anime table
    $stmt = $pdo->query("SELECT COUNT(*) FROM anime");
    $animeCount = $stmt->fetchColumn();
    
    // Check seasons table
    $stmt = $pdo->query("SELECT COUNT(*) FROM seasons");
    $seasonsCount = $stmt->fetchColumn();
    
    // Check episodes table
    $stmt = $pdo->query("SELECT COUNT(*) FROM episodes");
    $episodesCount = $stmt->fetchColumn();
    
    // Get detailed info for debugging
    $animeInfo = [];
    if ($animeCount > 0) {
        $stmt = $pdo->query("SELECT id, title FROM anime");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $animeId = $row['id'];
            $animeTitle = $row['title'];
            
            // Get seasons for this anime
            $seasonStmt = $pdo->prepare("SELECT id, season_number FROM seasons WHERE anime_id = ?");
            $seasonStmt->execute([$animeId]);
            $seasons = $seasonStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $seasonInfo = [];
            foreach ($seasons as $season) {
                $seasonId = $season['id'];
                $seasonNumber = $season['season_number'];
                
                // Get episodes for this season
                $episodeStmt = $pdo->prepare("SELECT id, episode_number, title FROM episodes WHERE season_id = ?");
                $episodeStmt->execute([$seasonId]);
                $episodes = $episodeStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $seasonInfo[] = [
                    'id' => $seasonId,
                    'season_number' => $seasonNumber,
                    'episodes' => $episodes
                ];
            }
            
            $animeInfo[] = [
                'id' => $animeId,
                'title' => $animeTitle,
                'seasons' => $seasonInfo
            ];
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check - AnimeElite Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Admin Navigation -->
    <nav class="navbar py-3 px-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-xl font-bold logo-text">AnimeElite Admin</a>
                <div class="ml-8 hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors duration-200">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white transition-colors duration-200">Users</a>
                    <a href="subscription_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Subscriptions</a>
                    <a href="anime_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Anime Management</a>
                    <a href="coupons.php" class="text-gray-300 hover:text-white transition-colors duration-200">Coupons</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-300"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-md text-sm transition-colors duration-200">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Database Check</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold mb-2">Anime</h2>
                    <p class="text-4xl font-bold text-primary-500"><?php echo $animeCount; ?></p>
                    <p class="text-gray-400">Total anime in database</p>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold mb-2">Seasons</h2>
                    <p class="text-4xl font-bold text-primary-500"><?php echo $seasonsCount; ?></p>
                    <p class="text-gray-400">Total seasons in database</p>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold mb-2">Episodes</h2>
                    <p class="text-4xl font-bold text-primary-500"><?php echo $episodesCount; ?></p>
                    <p class="text-gray-400">Total episodes in database</p>
                </div>
            </div>
            
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-4">Detailed Information</h2>
                
                <?php if (empty($animeInfo)): ?>
                    <p class="text-yellow-500">No anime found in the database.</p>
                <?php else: ?>
                    <?php foreach ($animeInfo as $anime): ?>
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-2">
                                Anime ID: <?php echo htmlspecialchars($anime['id']); ?> - 
                                <?php echo htmlspecialchars($anime['title']); ?>
                            </h3>
                            
                            <?php if (empty($anime['seasons'])): ?>
                                <p class="text-yellow-500 ml-4">No seasons found for this anime.</p>
                            <?php else: ?>
                                <?php foreach ($anime['seasons'] as $season): ?>
                                    <div class="ml-4 mb-4">
                                        <h4 class="font-medium mb-2">
                                            Season ID: <?php echo htmlspecialchars($season['id']); ?> - 
                                            Season <?php echo htmlspecialchars($season['season_number']); ?>
                                        </h4>
                                        
                                        <?php if (empty($season['episodes'])): ?>
                                            <p class="text-yellow-500 ml-4">No episodes found for this season.</p>
                                        <?php else: ?>
                                            <ul class="ml-4 list-disc pl-4">
                                                <?php foreach ($season['episodes'] as $episode): ?>
                                                    <li>
                                                        Episode ID: <?php echo htmlspecialchars($episode['id']); ?> - 
                                                        Episode <?php echo htmlspecialchars($episode['episode_number']); ?>: 
                                                        <?php echo htmlspecialchars($episode['title']); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 