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
    'episodes' => []
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch latest episodes with anime and season information
    $stmt = $pdo->prepare("
        SELECT 
            e.id, e.title, e.description, e.thumbnail, e.video_url, e.duration, e.is_premium,
            e.episode_number, s.season_number, s.id as season_id, a.id as anime_id, a.title as anime_title
        FROM episodes e
        JOIN seasons s ON e.season_id = s.id
        JOIN anime a ON s.anime_id = a.id
        ORDER BY e.created_at DESC
        LIMIT 12
    ");
    
    $stmt->execute();
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($episodes) > 0) {
        $response['success'] = true;
        $response['episodes'] = $episodes;
    } else {
        $response['message'] = 'No episodes found';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?> 