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

// Process actions if any
if (isset($_POST['action'])) {
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($_POST['action']) {
            case 'add_anime':
                if (isset($_POST['title']) && !empty($_POST['title'])) {
                    $stmt = $pdo->prepare("INSERT INTO anime (title, description, cover_image, release_year, genres, status) 
                                          VALUES (:title, :description, :cover_image, :release_year, :genres, :status)");
                    
                    $stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
                    $stmt->bindParam(':description', $_POST['description'], PDO::PARAM_STR);
                    $stmt->bindParam(':cover_image', $_POST['cover_image'], PDO::PARAM_STR);
                    $stmt->bindParam(':release_year', $_POST['release_year'], PDO::PARAM_STR);
                    $stmt->bindParam(':genres', $_POST['genres'], PDO::PARAM_STR);
                    $stmt->bindParam(':status', $_POST['status'], PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $_SESSION['message'] = 'Anime added successfully.';
                }
                break;
                
            case 'add_season':
                if (isset($_POST['anime_id']) && isset($_POST['season_number'])) {
                    $stmt = $pdo->prepare("INSERT INTO seasons (anime_id, season_number, title, description, cover_image, release_year) 
                                          VALUES (:anime_id, :season_number, :title, :description, :cover_image, :release_year)");
                    
                    $stmt->bindParam(':anime_id', $_POST['anime_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':season_number', $_POST['season_number'], PDO::PARAM_INT);
                    $stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
                    $stmt->bindParam(':description', $_POST['description'], PDO::PARAM_STR);
                    $stmt->bindParam(':cover_image', $_POST['cover_image'], PDO::PARAM_STR);
                    $stmt->bindParam(':release_year', $_POST['release_year'], PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $_SESSION['message'] = 'Season added successfully.';
                }
                break;
                
            case 'add_episode':
                if (isset($_POST['season_id']) && isset($_POST['episode_number']) && isset($_POST['title']) && isset($_POST['video_url'])) {
                    $stmt = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, description, thumbnail, video_url, duration, is_premium) 
                                          VALUES (:season_id, :episode_number, :title, :description, :thumbnail, :video_url, :duration, :is_premium)");
                    
                    $stmt->bindParam(':season_id', $_POST['season_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':episode_number', $_POST['episode_number'], PDO::PARAM_INT);
                    $stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
                    $stmt->bindParam(':description', $_POST['description'], PDO::PARAM_STR);
                    $stmt->bindParam(':thumbnail', $_POST['thumbnail'], PDO::PARAM_STR);
                    $stmt->bindParam(':video_url', $_POST['video_url'], PDO::PARAM_STR);
                    $stmt->bindParam(':duration', $_POST['duration'], PDO::PARAM_STR);
                    $isPremium = isset($_POST['is_premium']) ? 1 : 0;
                    $stmt->bindParam(':is_premium', $isPremium, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $_SESSION['message'] = 'Episode added successfully.';
                }
                break;
        }
    } catch (PDOException $e) {
        // Provide more specific error messages
        if ($e->getCode() == 1049) {
            $_SESSION['error'] = "Database '$dbname' not found. Please check your database configuration.";
        } elseif ($e->getCode() == 1045) {
            $_SESSION['error'] = "Access denied for user '$username'. Invalid database credentials.";
        } elseif ($e->getCode() == 2002) {
            $_SESSION['error'] = "Cannot connect to database server at '$host'. Server might be down or unreachable.";
        } elseif ($e->getCode() == '42S02') {
            $_SESSION['error'] = "Table not found. Please run the database setup script first.";
        } elseif ($e->getCode() == '23000') {
            $_SESSION['error'] = "Duplicate entry or foreign key constraint violation. Check your input data.";
        } else {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime Management - AnimeElite Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        /* DataTables customization for dark theme */
        table.dataTable {
            background-color: #121212;
            color: white;
            border-collapse: collapse;
        }
        
        table.dataTable thead th, 
        table.dataTable thead td {
            border-bottom: 1px solid #333;
            padding: 10px 18px;
        }
        
        table.dataTable tbody tr {
            background-color: #121212;
        }
        
        table.dataTable tbody tr:hover {
            background-color: #1a1a1a;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #a0a0a0;
            margin-bottom: 10px;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            background-color: #1a1a1a;
            color: white;
            border: 1px solid #333;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            background-color: #1a1a1a;
            color: white;
            border: 1px solid #333;
            border-radius: 0.25rem;
            padding: 0.25rem 1rem 0.25rem 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #a0a0a0 !important;
            border: 1px solid #333;
            background: #1a1a1a;
            border-radius: 0.25rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: white !important;
            border: 1px solid #7c3aed;
            background: #7c3aed;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: white !important;
            border: 1px solid #7c3aed;
            background: rgba(124, 58, 237, 0.5) !important;
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
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors duration-200">Dashboard</a>
                    <a href="users.php" class="text-gray-300 hover:text-white transition-colors duration-200">Users</a>
                    <a href="subscription_management.php" class="text-gray-300 hover:text-white transition-colors duration-200">Subscriptions</a>
                    <a href="anime_management.php" class="text-white font-medium">Anime Management</a>
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
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-900 border border-green-700 text-white px-4 py-3 rounded mb-6 flex justify-between items-center">
                <span><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
                <button class="text-white hover:text-green-300 focus:outline-none" onclick="this.parentElement.remove();">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded mb-6 flex justify-between items-center">
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                <button class="text-white hover:text-red-300 focus:outline-none" onclick="this.parentElement.remove();">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Anime Management</h1>
            <div>
                <button onclick="document.getElementById('add-anime-modal').classList.remove('hidden')" class="btn btn-primary py-2 px-4 mr-2">
                    Add Anime
                </button>
                <button onclick="document.getElementById('add-season-modal').classList.remove('hidden')" class="btn bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 mr-2">
                    Add Season
                </button>
                <button onclick="document.getElementById('add-episode-modal').classList.remove('hidden')" class="btn bg-green-600 hover:bg-green-700 text-white py-2 px-4">
                    Add Episode
                </button>
            </div>
        </div>
        
        <!-- Anime Table -->
        <div class="bg-gray-900 rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Anime List</h2>
            <table id="animeTable" class="w-full stripe hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Genres</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Seasons</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Connect to database
                        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        // Fetch anime data with seasons count
                        $stmt = $pdo->prepare("
                            SELECT a.id, a.title, a.genres, a.release_year, a.status, COUNT(s.id) as seasons_count
                            FROM anime a
                            LEFT JOIN seasons s ON a.id = s.anime_id
                            GROUP BY a.id
                            ORDER BY a.title
                        ");
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['genres']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['release_year']) . '</td>';
                            
                            // Status with color
                            $statusColor = 'bg-gray-700';
                            switch ($row['status']) {
                                case 'ongoing':
                                    $statusColor = 'bg-green-700';
                                    break;
                                case 'completed':
                                    $statusColor = 'bg-blue-700';
                                    break;
                                case 'upcoming':
                                    $statusColor = 'bg-yellow-700';
                                    break;
                            }
                            
                            echo '<td><span class="px-2 py-1 rounded ' . $statusColor . ' text-white text-xs">' . 
                                 htmlspecialchars(ucfirst($row['status'])) . '</span></td>';
                            
                            echo '<td>' . htmlspecialchars($row['seasons_count']) . '</td>';
                            
                            // Actions
                            echo '<td class="flex space-x-2">';
                            echo '<button class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" 
                                  onclick="viewSeasons(' . $row['id'] . ')">Seasons</button>';
                            echo '<button class="text-xs bg-yellow-600 hover:bg-yellow-700 text-white px-2 py-1 rounded" 
                                  onclick="editAnime(' . $row['id'] . ')">Edit</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } catch (PDOException $e) {
                        // Provide more specific error messages
                        if ($e->getCode() == 1049) {
                            echo '<tr><td colspan="7" class="text-red-500">Database not found: ' . htmlspecialchars($dbname) . '. Please check configuration.</td></tr>';
                        } elseif ($e->getCode() == 1045) {
                            echo '<tr><td colspan="7" class="text-red-500">Access denied. Invalid database credentials.</td></tr>';
                        } elseif ($e->getCode() == 2002) {
                            echo '<tr><td colspan="7" class="text-red-500">Cannot connect to database server at ' . htmlspecialchars($host) . '.</td></tr>';
                        } elseif ($e->getCode() == '42S02') {
                            echo '<tr><td colspan="7" class="text-red-500">Table "anime" not found. Please run the database setup script first.</td></tr>';
                        } else {
                            echo '<tr><td colspan="7" class="text-red-500">Database error: ' . htmlspecialchars($e->getMessage()) . ' (Code: ' . $e->getCode() . ')</td></tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Seasons & Episodes -->
        <div id="seasons-container" class="bg-gray-900 rounded-lg shadow-lg p-6 mb-8 hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Seasons & Episodes</h2>
                <button onclick="document.getElementById('seasons-container').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="seasons-content">
                <!-- Content will be loaded dynamically -->
                <div class="flex justify-center items-center h-32">
                    <div class="spinner-border animate-spin h-8 w-8 border-t-2 border-b-2 border-purple-500 rounded-full"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Anime Modal -->
    <div id="add-anime-modal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Add New Anime</h3>
                <button onclick="document.getElementById('add-anime-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="add_anime">
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Title</label>
                    <input type="text" name="title" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Description</label>
                    <textarea name="description" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white" rows="3"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Cover Image URL</label>
                    <input type="text" name="cover_image" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Release Year</label>
                        <input type="number" name="release_year" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Status</label>
                        <select name="status" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="upcoming">Upcoming</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-300 mb-2">Genres (comma separated)</label>
                    <input type="text" name="genres" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary px-6 py-2">
                        Add Anime
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Season Modal -->
    <div id="add-season-modal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Add New Season</h3>
                <button onclick="document.getElementById('add-season-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="add_season">
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Anime</label>
                    <select name="anime_id" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="">Select Anime</option>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT id, title FROM anime ORDER BY title");
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['title']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Error loading anime</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Season Number</label>
                    <input type="number" name="season_number" required min="1" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Title</label>
                    <input type="text" name="title" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                    <p class="text-gray-400 text-xs mt-1">Optional, leave blank to use "Season X"</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Description</label>
                    <textarea name="description" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white" rows="3"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Cover Image URL</label>
                    <input type="text" name="cover_image" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                    <p class="text-gray-400 text-xs mt-1">Optional, leave blank to use anime's cover</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-300 mb-2">Release Year</label>
                    <input type="number" name="release_year" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary px-6 py-2">
                        Add Season
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Episode Modal -->
    <div id="add-episode-modal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Add New Episode</h3>
                <button onclick="document.getElementById('add-episode-modal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="add_episode">
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Anime</label>
                    <select id="anime-select" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="">Select Anime</option>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT id, title FROM anime ORDER BY title");
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['title']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Error loading anime</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Season</label>
                    <select name="season_id" id="season-select" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="">Select Anime First</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Episode Number</label>
                    <input type="number" name="episode_number" required min="1" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Title</label>
                    <input type="text" name="title" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Description</label>
                    <textarea name="description" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white" rows="3"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Thumbnail URL</label>
                    <input type="text" name="thumbnail" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Video URL (iframe)</label>
                    <input type="text" name="video_url" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                    <p class="text-gray-400 text-xs mt-1">Full iframe embed URL</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Duration (minutes)</label>
                    <input type="number" name="duration" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                
                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_premium" id="is_premium" class="h-4 w-4 bg-gray-800 border-gray-700 rounded text-primary-600">
                        <label for="is_premium" class="ml-2 block text-gray-300">Premium Only</label>
                    </div>
                    <p class="text-gray-400 text-xs mt-1">Check this if the episode should be accessible only for premium subscribers</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary px-6 py-2">
                        Add Episode
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#animeTable').DataTable({
                order: [[1, 'asc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100]
            });
            
            // Handle anime selection change for episodes
            $('#anime-select').on('change', function() {
                const animeId = $(this).val();
                if (!animeId) {
                    $('#season-select').html('<option value="">Select Anime First</option>');
                    return;
                }
                
                // Fetch seasons for the selected anime
                $.ajax({
                    url: 'ajax/get_seasons.php',
                    method: 'POST',
                    data: { anime_id: animeId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value="">Select Season</option>';
                            response.seasons.forEach(function(season) {
                                const seasonTitle = season.title || `Season ${season.season_number}`;
                                options += `<option value="${season.id}">${seasonTitle}</option>`;
                            });
                            $('#season-select').html(options);
                        } else {
                            $('#season-select').html('<option value="">No seasons found</option>');
                        }
                    },
                    error: function() {
                        $('#season-select').html('<option value="">Error loading seasons</option>');
                    }
                });
            });
        });
        
        // View seasons and episodes for an anime
        function viewSeasons(animeId) {
            const container = document.getElementById('seasons-container');
            const content = document.getElementById('seasons-content');
            
            container.classList.remove('hidden');
            content.innerHTML = `<div class="flex justify-center items-center h-32">
                <div class="spinner-border animate-spin h-8 w-8 border-t-2 border-b-2 border-purple-500 rounded-full"></div>
            </div>`;
            
            // Load seasons and episodes via AJAX
            fetch('ajax/get_seasons_episodes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `anime_id=${animeId}`
            })
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
            })
            .catch(error => {
                content.innerHTML = `<div class="text-red-500">Error loading seasons and episodes: ${error}</div>`;
            });
        }
        
        // Edit anime
        function editAnime(animeId) {
            alert('Edit functionality will be implemented here for anime ID: ' + animeId);
            // This would be expanded to load the anime data and show an edit form
        }
    </script>
</body>
</html> 