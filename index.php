<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$allEnvVars = [];
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'DB_') === 0 || $key === 'RENDER') {
        $allEnvVars[$key] = ($key === 'DB_PASSWORD') ? '******' : $value;
    }
}
$debug['env_vars'] = $allEnvVars;

// Check for Render-specific database URL
$renderDbUrl = getenv('DATABASE_URL');
if ($renderDbUrl) {
    $debug['render_db_url'] = 'Found DATABASE_URL environment variable';
    
    // Parse the DATABASE_URL
    $dbParams = parse_url($renderDbUrl);
    $host = $dbParams['host'] ?? null;
    $port = $dbParams['port'] ?? 5432;
    $dbname = ltrim($dbParams['path'] ?? '', '/');
    $user = $dbParams['user'] ?? null;
    $password = $dbParams['pass'] ?? null;
    
    $debug['parsed_db_url'] = [
        'host' => $host,
        'port' => $port,
        'dbname' => $dbname,
        'user' => $user,
        'password' => '******'
    ];
} else {
    $debug['render_db_url'] = 'DATABASE_URL not found, using individual environment variables';
    
    // Database connection settings from individual environment variables
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
}

// Fallback to defaults if environment variables are not set
if (empty($host)) $host = 'localhost';
if (empty($port)) $port = '5432';
if (empty($dbname)) $dbname = 'postgres';
if (empty($user)) $user = 'postgres';
// No default for password

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
    'PHP_VERSION' => phpversion()
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