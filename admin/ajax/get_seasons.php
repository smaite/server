<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session for admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'seasons' => []
];

// Check if anime_id is provided
if (!isset($_POST['anime_id']) || empty($_POST['anime_id'])) {
    $response['message'] = 'Anime ID is required';
    echo json_encode($response);
    exit();
}

$animeId = intval($_POST['anime_id']);

// Database connection settings
require_once '../../config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch seasons for the selected anime
    $stmt = $pdo->prepare("
        SELECT id, season_number, title
        FROM seasons 
        WHERE anime_id = :animeId
        ORDER BY season_number
    ");
    $stmt->bindParam(':animeId', $animeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['seasons'] = $seasons;
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?> 