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
    'anime' => []
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch featured anime (for now, just get the most recently added anime)
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.description, a.cover_image, a.release_year, a.genres, a.status,
               EXISTS(SELECT 1 FROM seasons s 
                      JOIN episodes e ON s.id = e.season_id 
                      WHERE s.anime_id = a.id AND e.is_premium = 1) as is_premium
        FROM anime a
        ORDER BY a.created_at DESC
        LIMIT 8
    ");
    
    $stmt->execute();
    $anime = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($anime) > 0) {
        $response['success'] = true;
        $response['anime'] = $anime;
    } else {
        $response['message'] = 'No featured anime found';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?> 