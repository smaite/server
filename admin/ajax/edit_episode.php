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
$response = ['success' => false, 'message' => '', 'episode' => null];

// Check request type
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET request - retrieve episode data for editing
    if (!isset($_GET['episode_id']) || empty($_GET['episode_id'])) {
        $response['message'] = 'No episode ID provided';
        echo json_encode($response);
        exit();
    }

    $episode_id = intval($_GET['episode_id']);

    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get episode info with season and anime details
        $stmt = $pdo->prepare("
            SELECT e.*, s.season_number, s.anime_id, a.title as anime_title
            FROM episodes e
            JOIN seasons s ON e.season_id = s.id
            JOIN anime a ON s.anime_id = a.id
            WHERE e.id = ?
        ");
        $stmt->execute([$episode_id]);
        $episode = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$episode) {
            $response['message'] = 'Episode not found';
        } else {
            $response['success'] = true;
            $response['episode'] = $episode;
        }
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} 
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST request - update episode data
    if (!isset($_POST['episode_id']) || empty($_POST['episode_id'])) {
        $response['message'] = 'No episode ID provided';
        echo json_encode($response);
        exit();
    }

    $episode_id = intval($_POST['episode_id']);
    
    // Check required fields
    if (!isset($_POST['title']) || empty($_POST['title']) || !isset($_POST['video_url']) || empty($_POST['video_url'])) {
        $response['message'] = 'Title and video URL are required';
        echo json_encode($response);
        exit();
    }

    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update episode
        $stmt = $pdo->prepare("
            UPDATE episodes 
            SET title = ?, 
                description = ?, 
                thumbnail = ?, 
                video_url = ?, 
                duration = ?, 
                is_premium = ?,
                episode_number = ?
            WHERE id = ?
        ");
        
        $isPremium = isset($_POST['is_premium']) ? 1 : 0;
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $thumbnail = isset($_POST['thumbnail']) ? $_POST['thumbnail'] : '';
        $duration = isset($_POST['duration']) ? $_POST['duration'] : '';
        $episode_number = isset($_POST['episode_number']) ? intval($_POST['episode_number']) : 1;
        
        $stmt->execute([
            $_POST['title'], 
            $description, 
            $thumbnail, 
            $_POST['video_url'], 
            $duration, 
            $isPremium,
            $episode_number,
            $episode_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Episode updated successfully';
        } else {
            $response['message'] = 'No changes were made or episode not found';
        }
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $response['message'] = 'Episode number already exists in this season';
        } else {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}
else {
    $response['message'] = 'Invalid request method';
}

// Return response
header('Content-Type: application/json');
echo json_encode($response);
?> 