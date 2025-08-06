<?php
// Set content type to JSON
header('Content-Type: application/json');

require_once 'config.php';
// Check if userId is provided
if (!isset($_POST['userId']) || empty($_POST['userId'])) {
    $response['message'] = 'User ID is required';
    echo json_encode($response);
    exit();
}

$userId = $_POST['userId'];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query for subscription data
    $stmt = $pdo->prepare("
        SELECT s.plan, s.start_date, s.end_date, s.status, s.price, s.recurring 
        FROM subscriptions s 
        WHERE s.user_id = :userId 
        AND s.status = 'active' 
        AND (s.end_date IS NULL OR s.end_date >= CURDATE())
        ORDER BY s.created_at DESC 
        LIMIT 1
    ");
    
    $stmt->bindParam(':userId', $userId, PDO::PARAM_STR);
    $stmt->execute();
    
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // Format response
        $response['success'] = true;
        $response['subscription'] = [
            'plan' => $subscription['plan'],
            'startDate' => $subscription['start_date'],
            'expiresAt' => $subscription['end_date'],
            'price' => (float)$subscription['price'],
            'recurring' => (bool)$subscription['recurring'],
            'status' => $subscription['status']
        ];
    } else {
        // User has no active subscription, return free plan
        $response['success'] = true;
        $response['subscription'] = [
            'plan' => 'free',
            'startDate' => null,
            'expiresAt' => null,
            'price' => 0,
            'recurring' => false,
            'status' => 'active'
        ];
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?> 