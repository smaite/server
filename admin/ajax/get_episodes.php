<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Check if season_id is provided
if (!isset($_POST['season_id']) || empty($_POST['season_id'])) {
    echo '<div class="text-red-500 p-4">Error: Season ID is required</div>';
    exit();
}

$seasonId = intval($_POST['season_id']);

// Database connection settings
require_once '../../config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch episodes for the season
    $stmt = $pdo->prepare("
        SELECT id, episode_number, title, description, thumbnail, video_url, duration, is_premium
        FROM episodes 
        WHERE season_id = :seasonId
        ORDER BY episode_number
    ");
    $stmt->bindParam(':seasonId', $seasonId, PDO::PARAM_INT);
    $stmt->execute();
    
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start building HTML output
    if (count($episodes) === 0) {
        echo '<div class="p-4 text-center text-gray-400">No episodes found for this season.</div>';
    } else {
        echo '
        <div class="p-4">
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b border-gray-700">
                        <th class="py-2 px-4">Episode</th>
                        <th class="py-2 px-4">Title</th>
                        <th class="py-2 px-4">Duration</th>
                        <th class="py-2 px-4">Status</th>
                        <th class="py-2 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($episodes as $episode) {
            echo '<tr class="border-b border-gray-800 hover:bg-gray-700">';
            echo '<td class="py-3 px-4">' . htmlspecialchars($episode['episode_number']) . '</td>';
            echo '<td class="py-3 px-4">' . htmlspecialchars($episode['title']) . '</td>';
            echo '<td class="py-3 px-4">' . htmlspecialchars($episode['duration'] ?? '-') . ' min</td>';
            
            // Premium status badge
            $statusClass = $episode['is_premium'] ? 'bg-yellow-600' : 'bg-green-600';
            $statusText = $episode['is_premium'] ? 'Premium' : 'Free';
            echo '<td class="py-3 px-4"><span class="px-2 py-1 rounded ' . $statusClass . ' text-xs">' . $statusText . '</span></td>';
            
            // Actions
            echo '<td class="py-3 px-4">';
            echo '<div class="flex space-x-2">';
            echo '<button onclick="editEpisode(' . $episode['id'] . ')" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs">Edit</button>';
            echo '<button onclick="previewEpisode(\'' . htmlspecialchars($episode['video_url']) . '\')" class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded text-xs">Preview</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '
                </tbody>
            </table>
        </div>';
        
        // Add JavaScript for handling episode actions
        echo '
        <script>
            function editEpisode(episodeId) {
                alert(`Edit episode functionality will be implemented here for episode ID: ${episodeId}`);
            }
            
            function previewEpisode(videoUrl) {
                const modal = document.createElement("div");
                modal.className = "fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-75";
                modal.innerHTML = `
                    <div class="bg-gray-800 p-4 rounded-lg w-full max-w-3xl">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">Episode Preview</h3>
                            <button class="text-gray-400 hover:text-white" onclick="this.closest(\'.fixed\').remove()">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="relative pt-[56.25%]">
                            <iframe class="absolute top-0 left-0 w-full h-full" src="${videoUrl}" allowfullscreen></iframe>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
        </script>';
    }
    
} catch (PDOException $e) {
    echo '<div class="p-4 text-red-500">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
} catch (Exception $e) {
    echo '<div class="p-4 text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?> 