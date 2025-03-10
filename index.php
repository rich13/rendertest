<?php
// Database connection settings
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

// Message to display if database connection fails
$errorMessage = '';
$dbConnectionStatus = false;
$currentTime = '';
$testData = [];

// Connect to PostgreSQL
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
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
            VALUES ('Hello from Render.com PHP deployment!')
        ");
    }
    
    // Fetch data from the test table
    $stmt = $pdo->query("SELECT * FROM test_table ORDER BY created_at DESC");
    $testData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
}

// Handle form submission to add a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $dbConnectionStatus) {
    try {
        $message = $_POST['message'];
        $stmt = $pdo->prepare("INSERT INTO test_table (message) VALUES (?)");
        $stmt->execute([$message]);
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Error adding message: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP PostgreSQL Hello World</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PHP PostgreSQL Hello World</h1>
            <p>A simple application for testing Render.com deployment</p>
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
            
            <?php if ($dbConnectionStatus): ?>
                <section class="form-card">
                    <h2>Add a New Message</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <input type="text" id="message" name="message" required>
                        </div>
                        <button type="submit">Save Message</button>
                    </form>
                </section>
                
                <section class="data-card">
                    <h2>Stored Messages</h2>
                    <?php if (count($testData) > 0): ?>
                        <ul class="message-list">
                            <?php foreach ($testData as $row): ?>
                                <li>
                                    <div class="message-content"><?php echo htmlspecialchars($row['message']); ?></div>
                                    <div class="message-time">Added on: <?php echo htmlspecialchars($row['created_at']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No messages found in the database.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> PHP PostgreSQL Demo</p>
        </footer>
    </div>
    
    <script src="script.js"></script>
</body>
</html> 