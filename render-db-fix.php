<?php
/**
 * Render.com Database Connection Fix
 * 
 * This file provides a hardcoded connection to the Render.com PostgreSQL database
 * when environment variables are not properly passed to the container.
 */

// Function to get database connection details for Render.com
function getRenderDbConnection() {
    // Check if we're running on Render.com
    $isRender = getenv('RENDER') || isset($_ENV['RENDER']) || isset($_SERVER['RENDER']);
    
    if ($isRender) {
        // Hardcoded connection details for Render.com
        // Replace 'postgres-crud-test-db' with your actual database name from Render.com
        return [
            'host' => 'postgres-crud-test-db.internal',
            'port' => '5432',
            'dbname' => 'postgres',
            'user' => 'postgres',
            // You need to set this in the Render.com dashboard as an environment variable
            'password' => getenv('POSTGRES_PASSWORD') ?: ($_ENV['POSTGRES_PASSWORD'] ?? ($_SERVER['POSTGRES_PASSWORD'] ?? null))
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