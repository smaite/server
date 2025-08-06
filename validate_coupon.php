<?php
// Set content type to JSON
header('Content-Type: application/json');

require_once 'config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'discount' => 0,
    'coupon' => null
];

// Check if couponCode is provided
if (!isset($_POST['couponCode']) || empty($_POST['couponCode'])) {
    $response['message'] = 'Coupon code is required';
    echo json_encode($response);
    exit();
}

$couponCode = $_POST['couponCode'];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query for coupon data
    $stmt = $pdo->prepare("
        SELECT c.id, c.code, c.discount_percent, c.description, 
               c.valid_from, c.valid_to, c.max_uses, c.current_uses,
               c.plan_restriction
        FROM coupons c 
        WHERE c.code = :code 
        AND c.active = 1
        AND (c.valid_from IS NULL OR c.valid_from <= NOW())
        AND (c.valid_to IS NULL OR c.valid_to >= NOW())
        AND (c.max_uses IS NULL OR c.current_uses < c.max_uses)
        LIMIT 1
    ");
    
    $stmt->bindParam(':code', $couponCode, PDO::PARAM_STR);
    $stmt->execute();
    
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($coupon) {
        // Coupon is valid
        $response['success'] = true;
        $response['discount'] = (float)$coupon['discount_percent'];
        $response['message'] = 'Coupon applied successfully!';
        
        // Remove sensitive data from response
        unset($coupon['id']);
        $response['coupon'] = $coupon;
        
        // Optionally increment usage count (commented out for testing)
        /*
        $updateStmt = $pdo->prepare("
            UPDATE coupons 
            SET current_uses = current_uses + 1 
            WHERE id = :id
        ");
        $updateStmt->bindParam(':id', $coupon['id'], PDO::PARAM_INT);
        $updateStmt->execute();
        */
    } else {
        // Coupon not found or not valid
        $response['message'] = 'Invalid or expired coupon code';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?> 