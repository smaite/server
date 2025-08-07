<?php
header('Content-Type: text/html; charset=utf-8');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animeelite";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "<p>Database created successfully or already exists.</p>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Read and execute SQL file
$sql_file = file_get_contents('user_db_setup.sql');

// Split SQL file into individual statements
$statements = explode(';', $sql_file);

// Execute each statement
$success = true;
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if ($conn->query($statement) !== TRUE) {
            echo "<p>Error executing SQL: " . $conn->error . "</p>";
            $success = false;
        }
    }
}

if ($success) {
    echo "<p>Database setup completed successfully!</p>";
    
    // Create config.php file
    $config_content = <<<EOT
<?php
// Database configuration
\$servername = "localhost";
\$username = "root";
\$password = "";
\$dbname = "animeelite";

/**
 * Test database connection and return detailed error message if it fails
 * @return array - Array with success status and message
 */
function testDatabaseConnection() {
    global \$servername, \$username, \$password, \$dbname;
    
    \$result = [
        'success' => false,
        'message' => ''
    ];
    
    try {
        // Try connecting to the server first
        \$conn = @new mysqli(\$servername, \$username, \$password);
        
        if (\$conn->connect_error) {
            // Server connection failed
            \$result['message'] = 'Database server connection failed: ' . \$conn->connect_error;
            return \$result;
        }
        
        // Try selecting the database
        \$db_select = @\$conn->select_db(\$dbname);
        
        if (!\$db_select) {
            // Database doesn't exist
            \$result['message'] = 'Database not found: ' . \$dbname;
            \$conn->close();
            return \$result;
        }
        
        // Try a simple query to verify permissions
        \$query = @\$conn->query("SHOW TABLES");
        
        if (!\$query) {
            // Query failed
            \$result['message'] = 'Database access denied: ' . \$conn->error;
            \$conn->close();
            return \$result;
        }
        
        // Connection successful
        \$result['success'] = true;
        \$result['message'] = 'Database connection successful';
        \$conn->close();
        return \$result;
        
    } catch (Exception \$e) {
        \$result['message'] = 'Database connection error: ' . \$e->getMessage();
        return \$result;
    }
}
EOT;

    // Write config file
    file_put_contents('config.php', $config_content);
    echo "<p>Configuration file created successfully!</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnimeElite Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f0f0;
        }
        h1 {
            color: #6d28d9;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #10b981;
        }
        .error {
            color: #ef4444;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>AnimeElite Setup</h1>
    
    <div class="card">
        <h2>Setup Complete</h2>
        <p>The database has been set up successfully. You can now use the AnimeElite website.</p>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Make sure your PHP server is running</li>
            <li>Update the config.php file if needed with your database credentials</li>
            <li>Access the website at <a href="../index.html">index.html</a></li>
            <li>Access the admin panel at <a href="admin/login.php">admin/login.php</a> (username: admin, password: admin123)</li>
        </ol>
    </div>
</body>
</html> 