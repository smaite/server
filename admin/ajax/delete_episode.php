<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Database connection settings
require_once '../../config.php';

// Initialize response
$response = ['success' => false, 'message' => ''];

// Check if episode_id is provided
if (!isset($_POST['episode_id']) || empty($_POST['episode_id'])) {
    $response['message'] = 'No episode ID provided';
    echo json_encode($response);
    exit();
}

$episode_id = intval($_POST['episode_id']);

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get episode info before deleting (for confirmation message)
    $stmt = $pdo->prepare("
        SELECT e.title, e.episode_number, s.season_number, a.title as anime_title
        FROM episodes e
        JOIN seasons s ON e.season_id = s.id
        JOIN anime a ON s.anime_id = a.id
        WHERE e.id = ?
    ");
    $stmt->execute([$episode_id]);
    $episode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$episode) {
        $response['message'] = 'Episode not found';
        echo json_encode($response);
        exit();
    }
    
    // Delete the episode
    $stmt = $pdo->prepare("DELETE FROM episodes WHERE id = ?");
    $stmt->execute([$episode_id]);
    
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = "Episode {$episode['episode_number']} '{$episode['title']}' from {$episode['anime_title']} Season {$episode['season_number']} deleted successfully";
    } else {
        $response['message'] = 'Failed to delete episode';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return response
header('Content-Type: application/json');
echo json_encode($response);
?> 