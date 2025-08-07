<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include_once 'config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'user' => null
];

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// Handle different actions
switch ($action) {
    case 'register':
        if ($method === 'POST') {
            // Get registration data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $username = isset($data['username']) ? $conn->real_escape_string($data['username']) : '';
            $email = isset($data['email']) ? $conn->real_escape_string($data['email']) : '';
            $password = isset($data['password']) ? $data['password'] : '';
            
            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
                $response['message'] = 'All fields are required';
                break;
            }
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $response['message'] = 'Email already registered';
                break;
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at, subscription) VALUES (?, ?, ?, NOW(), 'free')");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                
                // Create user session
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $token, $expires);
                $stmt->execute();
                
                // Return success with user data
                $response['success'] = true;
                $response['message'] = 'Registration successful';
                $response['user'] = [
                    'id' => $user_id,
                    'username' => $username,
                    'email' => $email,
                    'subscription' => 'free',
                    'token' => $token
                ];
            } else {
                $response['message'] = 'Registration failed: ' . $conn->error;
            }
        } else {
            $response['message'] = 'Invalid request method';
        }
        break;
        
    case 'login':
        if ($method === 'POST') {
            // Get login data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $email = isset($data['email']) ? $conn->real_escape_string($data['email']) : '';
            $password = isset($data['password']) ? $data['password'] : '';
            
            // Validate input
            if (empty($email) || empty($password)) {
                $response['message'] = 'Email and password are required';
                break;
            }
            
            // Get user by email
            $stmt = $conn->prepare("SELECT id, username, email, password, subscription, subscription_expires FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Create user session
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user['id'], $token, $expires);
                    $stmt->execute();
                    
                    // Return success with user data
                    $response['success'] = true;
                    $response['message'] = 'Login successful';
                    $response['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'subscription' => $user['subscription'],
                        'subscription_expires' => $user['subscription_expires'],
                        'token' => $token
                    ];
                } else {
                    $response['message'] = 'Invalid email or password';
                }
            } else {
                $response['message'] = 'Invalid email or password';
            }
        } else {
            $response['message'] = 'Invalid request method';
        }
        break;
        
    case 'verify_token':
        if ($method === 'POST') {
            // Get token
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $token = isset($data['token']) ? $conn->real_escape_string($data['token']) : '';
            
            if (empty($token)) {
                $response['message'] = 'Token is required';
                break;
            }
            
            // Verify token
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.email, u.subscription, u.subscription_expires 
                FROM users u
                JOIN user_sessions s ON u.id = s.user_id
                WHERE s.token = ? AND s.expires_at > NOW()
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Return success with user data
                $response['success'] = true;
                $response['message'] = 'Token valid';
                $response['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'subscription' => $user['subscription'],
                    'subscription_expires' => $user['subscription_expires']
                ];
            } else {
                $response['message'] = 'Invalid or expired token';
            }
        } else {
            $response['message'] = 'Invalid request method';
        }
        break;
        
    case 'logout':
        if ($method === 'POST') {
            // Get token
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            $token = isset($data['token']) ? $conn->real_escape_string($data['token']) : '';
            
            if (empty($token)) {
                $response['message'] = 'Token is required';
                break;
            }
            
            // Delete session
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Logout successful';
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
?> 