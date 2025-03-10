<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include the Render.com database connection fix
require_once 'render-db-fix.php';

// Load environment variables from .env file (only in local development)
function loadEnv($path = '.env') {
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!empty($name) && !getenv($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Only load .env file if we're in local development (not on Render.com)
if (!getenv('RENDER')) {
    loadEnv();
}

// Debug information - always show in this version
$debug = [];
$showDebug = true;

// List all environment variables for debugging
$debug['all_env_vars'] = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'DB_') === 0 || $key === 'RENDER' || $key === 'DATABASE_URL' || $key === 'DEBUG' || $key === 'POSTGRES_PASSWORD') {
        $debug['all_env_vars'][$key] = ($key === 'DB_PASSWORD' || $key === 'DATABASE_URL' || $key === 'POSTGRES_PASSWORD') ? '******' : $value;
    }
}

// Check for environment variables in different ways
$debug['env_vars'] = [
    'RENDER (getenv)' => getenv('RENDER'),
    'RENDER ($_ENV)' => $_ENV['RENDER'] ?? 'not set',
    'RENDER ($_SERVER)' => $_SERVER['RENDER'] ?? 'not set',
    'DATABASE_URL (getenv)' => getenv('DATABASE_URL') ? 'set (hidden)' : 'not set',
    'DATABASE_URL ($_ENV)' => isset($_ENV['DATABASE_URL']) ? 'set (hidden)' : 'not set',
    'DATABASE_URL ($_SERVER)' => isset($_SERVER['DATABASE_URL']) ? 'set (hidden)' : 'not set',
    'POSTGRES_PASSWORD (getenv)' => getenv('POSTGRES_PASSWORD') ? 'set (hidden)' : 'not set',
    'POSTGRES_PASSWORD ($_ENV)' => isset($_ENV['POSTGRES_PASSWORD']) ? 'set (hidden)' : 'not set',
    'POSTGRES_PASSWORD ($_SERVER)' => isset($_SERVER['POSTGRES_PASSWORD']) ? 'set (hidden)' : 'not set'
];

// Check if we're running on Render.com
$isRender = getenv('RENDER') || isset($_ENV['RENDER']) || isset($_SERVER['RENDER']);
$debug['is_render'] = $isRender ? 'true' : 'false';

// Get database connection details
$dbConfig = getRenderDbConnection();
$host = $dbConfig['host'];
$port = $dbConfig['port'];
$dbname = $dbConfig['dbname'];
$user = $dbConfig['user'];
$password = $dbConfig['password'];

// Check if we have a DATABASE_URL
if (isset($dbConfig['database_url'])) {
    $debug['database_url_found'] = 'Using DATABASE_URL from environment';
}

$debug['using_render_db_fix'] = 'Using database connection from render-db-fix.php';

// Add connection details to debug
$debug['connection'] = [
    'host' => $host,
    'port' => $port,
    'dbname' => $dbname,
    'user' => $user,
    'password' => '******' // Don't show actual password
];

$debug['environment'] = [
    'RENDER' => getenv('RENDER') ? 'true' : 'false',
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'PHP_VERSION' => phpversion(),
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown'
];

// Message to display if database connection fails
$errorMessage = '';
$dbConnectionStatus = false;
$currentTime = '';
$crudResults = [];
$testData = [];

// Connect to PostgreSQL
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $debug['dsn'] = $dsn;
    
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Test the connection
    $stmt = $pdo->query('SELECT NOW() as time');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentTime = $result['time'];
    $dbConnectionStatus = true;
    
    // Check if the test table exists, if not create it
    $tableExists = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_name = 'test_table'
    )")->fetchColumn();
    
    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE test_table (
                id SERIAL PRIMARY KEY,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert a sample record
        $pdo->exec("
            INSERT INTO test_table (message) 
            VALUES ('Initial test record')
        ");
        
        $crudResults[] = [
            'operation' => 'CREATE TABLE',
            'status' => 'Success',
            'details' => 'Created test_table and inserted initial record'
        ];
    }
    
    // Perform CRUD operations automatically
    
    // CREATE - Insert a new record
    $createStmt = $pdo->prepare("INSERT INTO test_table (message) VALUES (?)");
    $newMessage = 'Automatic CRUD test - ' . date('Y-m-d H:i:s');
    $createStmt->execute([$newMessage]);
    $newId = $pdo->lastInsertId();
    
    $crudResults[] = [
        'operation' => 'CREATE',
        'status' => 'Success',
        'details' => "Inserted new record with ID: $newId and message: '$newMessage'"
    ];
    
    // READ - Fetch all records
    $readStmt = $pdo->query("SELECT * FROM test_table ORDER BY created_at DESC");
    $testData = $readStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $crudResults[] = [
        'operation' => 'READ',
        'status' => 'Success',
        'details' => 'Retrieved ' . count($testData) . ' records'
    ];
    
    // UPDATE - Update the record we just created
    $updateStmt = $pdo->prepare("UPDATE test_table SET message = ? WHERE id = ?");
    $updatedMessage = 'Updated: ' . $newMessage;
    $updateStmt->execute([$updatedMessage, $newId]);
    
    $crudResults[] = [
        'operation' => 'UPDATE',
        'status' => 'Success',
        'details' => "Updated record ID: $newId with new message: '$updatedMessage'"
    ];
    
    // READ AGAIN - Verify the update
    $verifyStmt = $pdo->prepare("SELECT * FROM test_table WHERE id = ?");
    $verifyStmt->execute([$newId]);
    $updatedRecord = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    $crudResults[] = [
        'operation' => 'VERIFY UPDATE',
        'status' => 'Success',
        'details' => "Record now contains: '" . $updatedRecord['message'] . "'"
    ];
    
    // DELETE - Delete the record we created and updated
    $deleteStmt = $pdo->prepare("DELETE FROM test_table WHERE id = ?");
    $deleteStmt->execute([$newId]);
    
    $crudResults[] = [
        'operation' => 'DELETE',
        'status' => 'Success',
        'details' => "Deleted record with ID: $newId"
    ];
    
    // READ FINAL - Get final state of the table
    $finalStmt = $pdo->query("SELECT * FROM test_table ORDER BY created_at DESC");
    $testData = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $crudResults[] = [
        'operation' => 'FINAL READ',
        'status' => 'Success',
        'details' => 'Final table state has ' . count($testData) . ' records'
    ];
    
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    $debug['error'] = $errorMessage;
    $crudResults[] = [
        'operation' => 'ERROR',
        'status' => 'Failed',
        'details' => $errorMessage
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP PostgreSQL CRUD Test</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PHP PostgreSQL CRUD Test</h1>
            <p>Automatic CRUD operations for testing Render.com deployment</p>
        </header>
        
        <main>
            <section class="status-card">
                <h2>Database Connection Status</h2>
                <?php if ($dbConnectionStatus): ?>
                    <div class="status success">
                        <p>✅ Connected to PostgreSQL successfully!</p>
                        <p>Server time: <?php echo htmlspecialchars($currentTime); ?></p>
                    </div>
                <?php else: ?>
                    <div class="status error">
                        <p>❌ Failed to connect to PostgreSQL</p>
                        <p>Error: <?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                <?php endif; ?>
            </section>
            
            <?php if ($showDebug && !empty($debug)): ?>
                <section class="debug-card">
                    <h2>Debug Information</h2>
                    <div class="debug-info">
                        <pre><?php echo htmlspecialchars(json_encode($debug, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php if ($dbConnectionStatus): ?>
                <section class="crud-card">
                    <h2>Automatic CRUD Test Results</h2>
                    <div class="crud-results">
                        <?php foreach ($crudResults as $result): ?>
                            <div class="crud-operation <?php echo strtolower($result['status']); ?>">
                                <div class="operation-type"><?php echo htmlspecialchars($result['operation']); ?></div>
                                <div class="operation-status"><?php echo htmlspecialchars($result['status']); ?></div>
                                <div class="operation-details"><?php echo htmlspecialchars($result['details']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <section class="data-card">
                    <h2>Current Database Records</h2>
                    <?php if (count($testData) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Message</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testData as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No records found in the database.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> PHP PostgreSQL CRUD Test</p>
            <p>Page generated at: <?php echo date('Y-m-d H:i:s'); ?></p>
        </footer>
    </div>
    
    <script src="script.js"></script>
</body>
</html> 