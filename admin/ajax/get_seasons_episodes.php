<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo 'Authentication required';
    exit();
}

// Database connection settings
require_once '../../config.php';

// Check if anime_id is provided
if (!isset($_POST['anime_id']) || empty($_POST['anime_id'])) {
    echo '<div class="text-red-500">No anime ID provided</div>';
    exit();
}

$anime_id = intval($_POST['anime_id']);

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get anime info
    $stmt = $pdo->prepare("SELECT title FROM anime WHERE id = ?");
    $stmt->execute([$anime_id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anime) {
        echo '<div class="text-red-500">Anime not found</div>';
        exit();
    }
    
    echo '<h3 class="text-xl font-semibold mb-4">' . htmlspecialchars($anime['title']) . '</h3>';
    
    // Get seasons
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(e.id) as episode_count 
        FROM seasons s 
        LEFT JOIN episodes e ON s.id = e.season_id 
        WHERE s.anime_id = ? 
        GROUP BY s.id 
        ORDER BY s.season_number
    ");
    $stmt->execute([$anime_id]);
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($seasons)) {
        echo '<div class="text-yellow-500 mb-4">No seasons found for this anime.</div>';
        echo '<a href="#" onclick="document.getElementById(\'add-season-modal\').classList.remove(\'hidden\'); return false;" 
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded inline-flex items-center">
              <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Add Season
            </a>';
    } else {
        echo '<div class="space-y-6">';
        
        foreach ($seasons as $season) {
            echo '<div class="bg-gray-800 rounded-lg overflow-hidden">';
            
            // Season header
            echo '<div class="bg-gray-700 px-4 py-3 flex justify-between items-center">';
            echo '<h4 class="font-medium">';
            echo 'Season ' . htmlspecialchars($season['season_number']);
            if (!empty($season['title'])) {
                echo ': ' . htmlspecialchars($season['title']);
            }
            echo '</h4>';
            
            echo '<div class="flex items-center space-x-2">';
            echo '<span class="text-sm text-gray-300">' . htmlspecialchars($season['episode_count']) . ' episodes</span>';
            echo '<button class="text-xs bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded" 
                  onclick="document.getElementById(\'add-episode-modal\').classList.remove(\'hidden\');">
                  Add Episode
                  </button>';
            echo '</div>';
            echo '</div>';
            
            // Episodes
            $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
            $stmt->execute([$season['id']]);
            $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($episodes)) {
                echo '<div class="p-4 text-gray-400">No episodes found for this season.</div>';
            } else {
                echo '<div class="overflow-x-auto">';
                echo '<table class="min-w-full divide-y divide-gray-700">';
                echo '<thead class="bg-gray-800">';
                echo '<tr>';
                echo '<th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Episode</th>';
                echo '<th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Title</th>';
                echo '<th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Duration</th>';
                echo '<th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Premium</th>';
                echo '<th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody class="bg-gray-800 divide-y divide-gray-700">';
                
                foreach ($episodes as $episode) {
                    echo '<tr>';
                    echo '<td class="px-4 py-3 whitespace-nowrap">' . htmlspecialchars($episode['episode_number']) . '</td>';
                    echo '<td class="px-4 py-3">' . htmlspecialchars($episode['title']) . '</td>';
                    echo '<td class="px-4 py-3">' . (empty($episode['duration']) ? '-' : htmlspecialchars($episode['duration']) . ' min') . '</td>';
                    echo '<td class="px-4 py-3">';
                    if ($episode['is_premium'] == 1) {
                        echo '<span class="px-2 py-1 text-xs rounded-full bg-yellow-600 text-white">Premium</span>';
                    } else {
                        echo '<span class="px-2 py-1 text-xs rounded-full bg-gray-600 text-white">Free</span>';
                    }
                    echo '</td>';
                    echo '<td class="px-4 py-3 text-right">';
                    echo '<button class="text-xs bg-yellow-600 hover:bg-yellow-700 text-white px-2 py-1 rounded mr-1" 
                          onclick="editEpisode(' . $episode['id'] . ')">Edit</button>';
                    echo '<button class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded"
                          onclick="deleteEpisode(' . $episode['id'] . ', \'' . addslashes($episode['title']) . '\')">Delete</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="text-red-500">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
} catch (Exception $e) {
    echo '<div class="text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?> 