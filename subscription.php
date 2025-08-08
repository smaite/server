<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure we're always returning JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Handle PHP errors and convert them to JSON responses
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'error_code' => $errno,
        'subscription' => null
    ];
    echo json_encode($response);
    exit;
}
set_error_handler('handleError');

// Handle uncaught exceptions
function handleException($exception) {
    $response = [
        'success' => false,
        'message' => 'Server exception: ' . $exception->getMessage(),
        'error_code' => $exception->getCode(),
        'subscription' => null
    ];
    echo json_encode($response);
    exit;
}
set_exception_handler('handleException');

try {
    include_once 'config.php';
    
    // Initialize response
    $response = [
        'success' => false,
        'message' => '',
        'subscription' => null
    ];
    
    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Connect to database
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Handle different actions
    switch ($action) {
        case 'status':
            if ($method === 'POST') {
                // Get user ID or token
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    $data = $_POST;
                }
                
                $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
                $token = isset($data['token']) ? $conn->real_escape_string($data['token']) : '';
                
                // Validate input
                if (empty($user_id) && empty($token)) {
                    $response['message'] = 'User ID or token is required';
                    break;
                }
                
                // Get user subscription status
                if (!empty($token)) {
                    // Get user by token
                    $stmt = $conn->prepare("
                        SELECT u.id, u.subscription, u.subscription_expires 
                        FROM users u
                        JOIN user_sessions s ON u.id = s.user_id
                        WHERE s.token = ? AND s.expires_at > NOW()
                    ");
                    $stmt->bind_param("s", $token);
                } else {
                    // Get user by ID
                    $stmt = $conn->prepare("SELECT id, subscription, subscription_expires FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Determine subscription status
                    $status = 'inactive';
                    if ($user['subscription'] !== 'free') {
                        if ($user['subscription_expires'] === null || strtotime($user['subscription_expires']) > time()) {
                            $status = 'active';
                        }
                    }
                    
                    // Return subscription info
                    $response['success'] = true;
                    $response['subscription'] = [
                        'status' => $status,
                        'plan' => $user['subscription'],
                        'expiresAt' => $user['subscription_expires']
                    ];
                } else {
                    $response['message'] = 'User not found';
                }
            } else {
                $response['message'] = 'Invalid request method';
            }
            break;
            
        case 'validate_coupon':
            if ($method === 'POST') {
                // Get coupon code and user token
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    $data = $_POST;
                }
                
                $coupon_code = isset($data['couponCode']) ? $conn->real_escape_string($data['couponCode']) : '';
                $token = isset($data['token']) ? $conn->real_escape_string($data['token']) : '';
                
                // Validate input
                if (empty($coupon_code)) {
                    $response['message'] = 'Coupon code is required';
                    break;
                }
                
                if (empty($token)) {
                    $response['message'] = 'User token is required';
                    break;
                }
                
                // Special premium coupons
                $premium_coupons = ['xsse3', 'ELITE100', 'ANIMEPRO', 'PREMIUM24'];
                
                if (in_array($coupon_code, $premium_coupons)) {
                    // Get user by token
                    $stmt = $conn->prepare("
                        SELECT u.id 
                        FROM users u
                        JOIN user_sessions s ON u.id = s.user_id
                        WHERE s.token = ? AND s.expires_at > NOW()
                    ");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        $user_id = $user['id'];
                        
                        // Set expiration date to 1 year from now
                        $expires = date('Y-m-d H:i:s', strtotime('+1 year'));
                        
                        // Update user subscription
                        $stmt = $conn->prepare("
                            UPDATE users 
                            SET subscription = 'premium', 
                                subscription_updated = NOW(), 
                                subscription_expires = ?,
                                coupon_used = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("ssi", $expires, $coupon_code, $user_id);
                        
                        if ($stmt->execute()) {
                            // Log coupon usage
                            $stmt = $conn->prepare("
                                INSERT INTO coupon_usage (user_id, coupon_code, applied_at)
                                VALUES (?, ?, NOW())
                            ");
                            $stmt->bind_param("is", $user_id, $coupon_code);
                            $stmt->execute();
                            
                            // Return success
                            $response['success'] = true;
                            $response['message'] = 'Premium subscription activated! You now have access to all premium content for 1 year.';
                            $response['subscription'] = [
                                'status' => 'active',
                                'plan' => 'premium',
                                'expiresAt' => $expires
                            ];
                        } else {
                            $response['message'] = 'Failed to update subscription: ' . $conn->error;
                        }
                    } else {
                        $response['message'] = 'Invalid or expired user token';
                    }
                } else {
                    // Check if coupon exists in database
                    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND active = 1");
                    $stmt->bind_param("s", $coupon_code);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $coupon = $result->fetch_assoc();
                        
                        // Get user by token
                        $stmt = $conn->prepare("
                            SELECT u.id 
                            FROM users u
                            JOIN user_sessions s ON u.id = s.user_id
                            WHERE s.token = ? AND s.expires_at > NOW()
                        ");
                        $stmt->bind_param("s", $token);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows === 1) {
                            $user = $result->fetch_assoc();
                            $user_id = $user['id'];
                            
                            // Check if coupon has been used by this user
                            $stmt = $conn->prepare("
                                SELECT id FROM coupon_usage 
                                WHERE user_id = ? AND coupon_code = ?
                            ");
                            $stmt->bind_param("is", $user_id, $coupon_code);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $response['message'] = 'You have already used this coupon';
                                break;
                            }
                            
                            // Apply coupon discount
                            $response['success'] = true;
                            $response['message'] = 'Coupon applied successfully! You saved ' . $coupon['discount'] . '% on your subscription.';
                            $response['discount'] = $coupon['discount'];
                            
                            // Log coupon usage
                            $stmt = $conn->prepare("
                                INSERT INTO coupon_usage (user_id, coupon_code, applied_at)
                                VALUES (?, ?, NOW())
                            ");
                            $stmt->bind_param("is", $user_id, $coupon_code);
                            $stmt->execute();
                        } else {
                            $response['message'] = 'Invalid or expired user token';
                        }
                    } else {
                        $response['message'] = 'Invalid coupon code';
                    }
                }
            } else {
                $response['message'] = 'Invalid request method';
            }
            break;
            
        case 'activate':
            if ($method === 'POST') {
                // Get user token and plan
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    $data = $_POST;
                }
                
                $token = isset($data['token']) ? $conn->real_escape_string($data['token']) : '';
                $plan = isset($data['plan']) ? $conn->real_escape_string($data['plan']) : '';
                
                // Validate input
                if (empty($token)) {
                    $response['message'] = 'User token is required';
                    break;
                }
                
                if (empty($plan) || !in_array($plan, ['premium', 'ultimate'])) {
                    $response['message'] = 'Valid subscription plan is required';
                    break;
                }
                
                // Get user by token
                $stmt = $conn->prepare("
                    SELECT u.id 
                    FROM users u
                    JOIN user_sessions s ON u.id = s.user_id
                    WHERE s.token = ? AND s.expires_at > NOW()
                ");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $user_id = $user['id'];
                    
                    // Set expiration date to 1 month from now
                    $expires = date('Y-m-d H:i:s', strtotime('+1 month'));
                    
                    // Update user subscription
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET subscription = ?, 
                            subscription_updated = NOW(), 
                            subscription_expires = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssi", $plan, $expires, $user_id);
                    
                    if ($stmt->execute()) {
                        // Return success
                        $response['success'] = true;
                        $response['message'] = ucfirst($plan) . ' subscription activated!';
                        $response['subscription'] = [
                            'status' => 'active',
                            'plan' => $plan,
                            'expiresAt' => $expires
                        ];
                    } else {
                        $response['message'] = 'Failed to update subscription: ' . $conn->error;
                    }
                } else {
                    $response['message'] = 'Invalid or expired user token';
                }
            } else {
                $response['message'] = 'Invalid request method';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    // Return response
    echo json_encode($response);
    
    // Close connection
    $conn->close();
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'subscription' => null
    ];
    echo json_encode($response);
}
?> 