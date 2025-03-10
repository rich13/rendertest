# PHP PostgreSQL Hello World

A simple PHP application with PostgreSQL database integration, designed for testing deployment on Render.com.

## Features

- PHP backend with PostgreSQL database
- Simple message board functionality
- Responsive design with CSS
- Client-side enhancements with JavaScript

## Local Development Setup

### Prerequisites

- PHP 7.4 or higher
- PostgreSQL 12 or higher
- Web server (Apache, Nginx, etc.)

### Steps

1. Clone this repository to your local machine
2. Create a PostgreSQL database for the application
3. Configure your web server to serve the application
4. Set the following environment variables:
   - `DB_HOST`: PostgreSQL host (default: localhost)
   - `DB_PORT`: PostgreSQL port (default: 5432)
   - `DB_NAME`: Database name (default: postgres)
   - `DB_USER`: Database username (default: postgres)
   - `DB_PASSWORD`: Database password (default: postgres)
5. Access the application through your web browser

## Deploying to Render.com

### PostgreSQL Database Setup

1. Log in to your Render.com account
2. Go to the Dashboard and click on "New +"
3. Select "PostgreSQL"
4. Configure your database:
   - Name: Choose a name for your database
   - Database: Choose a database name
   - User: Choose a username
   - Region: Select the region closest to your users
   - PostgreSQL Version: Choose the latest version
5. Click "Create Database"
6. Once created, note the connection details (host, port, database name, username, password)

### Web Service Setup

1. From the Render.com Dashboard, click on "New +"
2. Select "Web Service"
3. Connect your repository or upload your code
4. Configure your web service:
   - Name: Choose a name for your service
   - Runtime: PHP
   - Build Command: Leave empty or use `composer install` if you have dependencies
   - Start Command: `php -S 0.0.0.0:$PORT -t .`
5. Add the following environment variables:
   - `DB_HOST`: Your Render PostgreSQL host (from the database setup)
   - `DB_PORT`: Your Render PostgreSQL port (usually 5432)
   - `DB_NAME`: Your Render PostgreSQL database name
   - `DB_USER`: Your Render PostgreSQL username
   - `DB_PASSWORD`: Your Render PostgreSQL password
6. Click "Create Web Service"

## Testing the Deployment

Once deployed, you can test the application by:

1. Visiting the URL provided by Render.com
2. Checking if the database connection is successful
3. Adding messages through the form
4. Verifying that messages are stored and displayed correctly

## Troubleshooting

If you encounter issues with the database connection:

1. Verify that your environment variables are set correctly
2. Check if your IP is allowed to access the PostgreSQL database
3. Ensure that the PostgreSQL service is running
4. Check the Render.com logs for any error messages

## License

This project is open-source and available under the MIT License. 