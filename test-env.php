<?php
// Simple script to test environment variables

echo "Environment Variables Test\n";
echo "=========================\n\n";

echo "Using getenv():\n";
echo "RENDER: " . (getenv('RENDER') ?: 'not set') . "\n";
echo "DEBUG: " . (getenv('DEBUG') ?: 'not set') . "\n";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? 'set (hidden)' : 'not set') . "\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'not set') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'not set') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'not set') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'not set') . "\n";
echo "DB_PASSWORD: " . (getenv('DB_PASSWORD') ? 'set (hidden)' : 'not set') . "\n\n";

echo "Using \$_ENV:\n";
echo "RENDER: " . ($_ENV['RENDER'] ?? 'not set') . "\n";
echo "DEBUG: " . ($_ENV['DEBUG'] ?? 'not set') . "\n";
echo "DATABASE_URL: " . (isset($_ENV['DATABASE_URL']) ? 'set (hidden)' : 'not set') . "\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'not set') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'not set') . "\n";
echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? 'set (hidden)' : 'not set') . "\n\n";

echo "Using \$_SERVER:\n";
echo "RENDER: " . ($_SERVER['RENDER'] ?? 'not set') . "\n";
echo "DEBUG: " . ($_SERVER['DEBUG'] ?? 'not set') . "\n";
echo "DATABASE_URL: " . (isset($_SERVER['DATABASE_URL']) ? 'set (hidden)' : 'not set') . "\n";
echo "DB_HOST: " . ($_SERVER['DB_HOST'] ?? 'not set') . "\n";
echo "DB_PORT: " . ($_SERVER['DB_PORT'] ?? 'not set') . "\n";
echo "DB_NAME: " . ($_SERVER['DB_NAME'] ?? 'not set') . "\n";
echo "DB_USER: " . ($_SERVER['DB_USER'] ?? 'not set') . "\n";
echo "DB_PASSWORD: " . (isset($_SERVER['DB_PASSWORD']) ? 'set (hidden)' : 'not set') . "\n\n";

echo "All environment variables:\n";
print_r($_ENV);
echo "\n";
echo "All server variables:\n";
print_r($_SERVER); 