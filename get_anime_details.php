<?php
// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include database configuration
require_once 'config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'anime' => null,
    'seasons' => [],
    'current_episode' => null
];

// Get anime ID from query string
$animeId = isset($_GET['anime_id']) ? intval($_GET['anime_id']) : 0;
$seasonId = isset($_GET['season_id']) ? intval($_GET['season_id']) : 0;
$episodeId = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;

if ($animeId <= 0) {
    $response['message'] = 'Invalid anime ID';
    echo json_encode($response);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch anime details
    $stmt = $pdo->prepare("
        SELECT id, title, description, cover_image, release_year, genres, status
        FROM anime
        WHERE id = :animeId
    ");
    $stmt->bindParam(':animeId', $animeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anime) {
        $response['message'] = 'Anime not found';
        echo json_encode($response);
        exit;
    }
    
    // Fetch seasons for this anime
    $stmt = $pdo->prepare("
        SELECT id, season_number, title, description, cover_image, release_year
        FROM seasons
        WHERE anime_id = :animeId
        ORDER BY season_number
    ");
    $stmt->bindParam(':animeId', $animeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no season ID is provided, use the first season
    if ($seasonId <= 0 && count($seasons) > 0) {
        $seasonId = $seasons[0]['id'];
    }
    
    // For each season, fetch episodes
    foreach ($seasons as &$season) {
        $stmt = $pdo->prepare("
            SELECT id, episode_number, title, description, thumbnail, video_url, duration, is_premium
            FROM episodes
            WHERE season_id = :seasonId
            ORDER BY episode_number
        ");
        $stmt->bindParam(':seasonId', $season['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $season['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If this is the current season and no episode ID is provided, use the first episode
        if ($seasonId == $season['id'] && $episodeId <= 0 && count($season['episodes']) > 0) {
            $episodeId = $season['episodes'][0]['id'];
        }
    }
    
    // Fetch current episode details
    if ($episodeId > 0) {
        $stmt = $pdo->prepare("
            SELECT e.id, e.episode_number, e.title, e.description, e.thumbnail, e.video_url, e.duration, e.is_premium,
                  s.id as season_id, s.season_number, a.id as anime_id, a.title as anime_title
            FROM episodes e
            JOIN seasons s ON e.season_id = s.id
            JOIN anime a ON s.anime_id = a.id
            WHERE e.id = :episodeId
        ");
        $stmt->bindParam(':episodeId', $episodeId, PDO::PARAM_INT);
        $stmt->execute();
        
        $currentEpisode = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentEpisode) {
            $response['current_episode'] = $currentEpisode;
        }
    }
    
    // Set response data
    $response['success'] = true;
    $response['anime'] = $anime;
    $response['seasons'] = $seasons;
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?> 