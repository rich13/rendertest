# PHP PostgreSQL CRUD Test

A simple PHP application that automatically performs CRUD (Create, Read, Update, Delete) operations with a PostgreSQL database, designed for testing deployment on Render.com using Docker.

## Features

- PHP backend with PostgreSQL database
- Automatic CRUD operations on each page load
- Detailed operation results display
- Responsive design with CSS
- Client-side enhancements with JavaScript
- Docker containerization for easy deployment

## Local Development Setup

### Prerequisites

- PHP 7.4 or higher
- PostgreSQL 12 or higher
- Web server (Apache, Nginx, etc.)

### Steps

1. Clone this repository to your local machine
2. Create a PostgreSQL database for the application
3. Configure your web server to serve the application
4. Set the following environment variables in the `.env` file:
   - `DB_HOST`: PostgreSQL host (default: localhost)
   - `DB_PORT`: PostgreSQL port (default: 5432)
   - `DB_NAME`: Database name (default: postgres)
   - `DB_USER`: Database username
   - `DB_PASSWORD`: Database password
5. Access the application through your web browser

### Running with PHP's Built-in Server

For quick testing, you can use PHP's built-in web server:

```bash
php -S localhost:8972
```

Then visit http://localhost:8972 in your browser.

### Running with Docker

You can also run the application using Docker:

```bash
# Build the Docker image
docker build -t php-postgres-crud-test .

# Run the container
docker run -p 8972:80 --env-file .env -d php-postgres-crud-test
```

Then visit http://localhost:8972 in your browser.

## Deploying to Render.com

### PostgreSQL Database Setup

1. Log in to your Render.com account
2. Go to the Dashboard and click on "New +"
3. Select "PostgreSQL"
4. Configure your database:
   - Name: postgres-crud-test-db
   - Database: postgres
   - User: Choose a username
   - Region: Select the region closest to your users
   - PostgreSQL Version: Choose the latest version
5. Click "Create Database"
6. Once created, note the connection details (host, port, database name, username, password)

### Web Service Setup with Docker

1. From the Render.com Dashboard, click on "New +"
2. Select "Web Service"
3. Connect your repository or upload your code
4. Configure your web service:
   - Name: php-postgres-crud-test
   - Environment: Docker
   - The Dockerfile is already included in the repository
5. The environment variables will be automatically set if you use the Blueprint feature with the included `render.yaml` file
6. Click "Create Web Service"

### Using Render Blueprint

For easier deployment, you can use Render's Blueprint feature:

1. Push your code to a Git repository
2. In Render.com, go to "Blueprints" and click "New Blueprint Instance"
3. Connect to your repository
4. Render will automatically set up the web service and database as defined in the `render.yaml` file

## Testing the Deployment

Once deployed, you can test the application by:

1. Visiting the URL provided by Render.com
2. The page will automatically perform CRUD operations on each load
3. Check the "Automatic CRUD Test Results" section to see the results of each operation
4. Verify that the database connection is successful

## Troubleshooting

If you encounter issues with the database connection:

1. Verify that your environment variables are set correctly
2. Check if your IP is allowed to access the PostgreSQL database
3. Ensure that the PostgreSQL service is running
4. Check the Render.com logs for any error messages

## License

This project is open-source and available under the MIT License. 