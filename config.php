<?php
// Database connection settings
$host = 'localhost';
$dbname = 'glorious_hq';
$username = 'glorious_hq';
$password = 'glorious_hq';   // Database name

// Initialize response array for API endpoints
$response = [
    'success' => false,
    'message' => '',
];

// Base URL for server
$server_url = 'https://cdn.glorioustradehub.com/server/';

/**
 * Test database connection and return detailed error information
 * 
 * @return array Connection status with details
 */
function testDatabaseConnection() {
    global $servername, $username, $password, $dbname;
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $conn->connect_error
            ];
        }
        
        $conn->close();
        return [
            'success' => true,
            'message' => 'Database connection successful'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}
?>