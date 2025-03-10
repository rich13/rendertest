<?php
/**
 * Render.com Database Connection Fix
 * 
 * This file provides a connection to the Render.com PostgreSQL database
 * using the DATABASE_URL environment variable.
 */

// Function to get database connection details for Render.com
function getRenderDbConnection() {
    // Check if we're running on Render.com
    $isRender = getenv('RENDER') || isset($_ENV['RENDER']) || isset($_SERVER['RENDER']);
    
    if ($isRender) {
        // Try to get the DATABASE_URL environment variable
        $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null));
        
        if ($databaseUrl) {
            // Parse the DATABASE_URL
            $dbParams = parse_url($databaseUrl);
            
            return [
                'host' => $dbParams['host'] ?? null,
                'port' => $dbParams['port'] ?? '5432',
                'dbname' => ltrim($dbParams['path'] ?? '', '/'),
                'user' => $dbParams['user'] ?? null,
                'password' => $dbParams['pass'] ?? null,
                'database_url' => $databaseUrl
            ];
        }
        
        // Fallback to using the password directly
        $password = getenv('POSTGRES_PASSWORD') ?: ($_ENV['POSTGRES_PASSWORD'] ?? ($_SERVER['POSTGRES_PASSWORD'] ?? null));
        
        // Try to construct a connection using the database name and password
        // The actual hostname format can vary, so we'll try a few possibilities
        $dbId = 'postgres-crud-test-db';
        
        return [
            'host' => $dbId . '.onrender.com', // External hostname
            'port' => '5432',
            'dbname' => 'postgres',
            'user' => 'postgres',
            'password' => $password
        ];
    }
    
    // For local development, use environment variables or defaults
    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        'dbname' => getenv('DB_NAME') ?: 'postgres',
        'user' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: null
    ];
} 