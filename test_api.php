<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure we're always returning JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => true,
    'message' => 'API test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_status' => null
];

try {
    // Test database connection if config.php exists
    if (file_exists('config.php')) {
        include_once 'config.php';
        
        if (function_exists('testDatabaseConnection')) {
            $db_test = testDatabaseConnection();
            $response['database_status'] = $db_test;
        } else {
            // Try to connect manually
            $conn = @new mysqli($servername ?? 'localhost', $username ?? 'root', $password ?? '', $dbname ?? 'animeelite');
            
            if ($conn->connect_error) {
                $response['database_status'] = [
                    'success' => false,
                    'message' => 'Database connection failed: ' . $conn->connect_error
                ];
            } else {
                $response['database_status'] = [
                    'success' => true,
                    'message' => 'Database connection successful'
                ];
                $conn->close();
            }
        }
    } else {
        $response['database_status'] = [
            'success' => false,
            'message' => 'config.php file not found'
        ];
    }
    
    // Test if we can create valid JSON
    $json_test = json_encode(['test' => 'value']);
    if ($json_test === false) {
        $response['json_status'] = [
            'success' => false,
            'message' => 'JSON encoding failed: ' . json_last_error_msg()
        ];
    } else {
        $response['json_status'] = [
            'success' => true,
            'message' => 'JSON encoding works correctly'
        ];
    }
    
    // Output the response
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => 'Test failed: ' . $e->getMessage()
    ];
    echo json_encode($error_response);
}
?> 