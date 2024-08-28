## Project Name

### Laravel Backend Setup Guide

This guide provides step-by-step instructions to set up and deploy the Laravel backend application on a server for development and collaboration purposes.

### Prerequisites

Before starting, make sure you have the following installed on your server:

-   **PHP** (version 8.0 or later)
-   **Composer** (latest version)
-   **MySQL** or any other supported database
-   **Web Server** (Apache, Nginx)
-   **Git** (for cloning the repository)

### Step 1: Clone the Repository

First, clone the project repository to your server:

```bash
git clone https://github.com/danidh05/NursingApp.git
cd your-repository
```

### Step 2: Set Up the Environment Configuration

1. **Create a `.env` File:**

    Copy the `.env.example` file to create your `.env` file:

    ```bash
    cp .env.example .env
    ```

2. **Update the `.env` File:**

    Edit the `.env` file to set up your environment variables, such as the database connection, app URL, and other configurations. Replace the placeholder values with the appropriate values for your server environment:

    ```dotenv
    APP_NAME=Laravel
    APP_ENV=local
    APP_KEY= # Will be generated in the next step
    APP_DEBUG=true
    APP_URL=http://your-server-url

    DB_CONNECTION=mysql
    DB_HOST=your_db_host
    DB_PORT=3306
    DB_DATABASE=your_db_name
    DB_USERNAME=your_db_username
    DB_PASSWORD=your_db_password

    # Other configurations...
    ```

### Step 3: Install Composer Dependencies

Run the following command to install all required Composer dependencies:

```bash
composer install
```

### Step 4: Generate the Application Key

Run the following command to generate a new application key:

```bash
php artisan key:generate
```

This command will set the `APP_KEY` value in your `.env` file.

### Step 5: Run Database Migrations

Set up the database schema by running the migrations:

```bash
php artisan migrate
```

### Step 6: (Optional) Run Database Seeders

If you need to seed the database with initial data, run the seeders:

```bash
php artisan db:seed
```

### Step 7: Set Up File Permissions

Ensure that the necessary directories have the correct write permissions:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

This ensures that Laravel can write to these directories.

### Step 8: Configure the Web Server

Configure your web server (Apache or Nginx) to point to the `public` directory of the Laravel project as the document root. Below is an example configuration for **Nginx**:

```nginx
server {
    listen 80;
    server_name your-server-url;

    root /path-to-your-project/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Step 9: Test the Application

Visit the app URL in your browser (e.g., `http://your-server-url`) to verify that everything is working correctly. Check the logs in `storage/logs/laravel.log` for any errors.

### Additional Notes

-   **Environment-Specific Settings:** Make sure that settings in `.env` file match your environment (e.g., `APP_ENV=production` for production servers).
-   **SSL Configuration:** If using HTTPS, ensure SSL certificates are properly configured.
-   **Deployments:** For ongoing development, pull the latest changes from the repository and repeat the necessary steps (`composer install`, `php artisan migrate`, etc.).

### Troubleshooting

-   **Common Issues:** Check the `storage/logs/laravel.log` file for any application errors.
-   **Permissions Issues:** Ensure the `storage` and `bootstrap/cache` directories are writable.

For further assistance, refer to the [Laravel Documentation](https://laravel.com/docs).

---

As for step 7:
Step 7: Configure the Web Server in more detail.

What is a Web Server?
A web server is software that serves web pages to users. It handles requests from users (e.g., when they visit a website) and returns the appropriate web content, such as HTML pages, images, or JSON data for APIs.

Common web servers include:

Apache
Nginx
IIS (Internet Information Services)
LiteSpeed
For a Laravel application, the web server will serve the application by pointing to the public directory, which is where Laravel's front controller (index.php) is located. The front controller handles all incoming requests to the Laravel application.

Why Do You Need to Configure a Web Server?
To deploy your Laravel application on a server, you need a web server (like Apache or Nginx) that knows how to serve your application's files to users or clients. You configure the web server to point to the Laravel app's public directory, ensuring that all requests go through Laravel's routing and controller logic.

Step 7: Configure the Web Server Explained
Here’s what you need to do in Step 7:

Point the Web Server to the public Directory:

Your Laravel project has a public folder that contains the entry point (index.php) for all requests to your application.
Configure the web server (Apache, Nginx, etc.) to use this folder as the "document root" (the directory from which web documents are served).
Set Up Web Server Configuration (Example for Nginx):

If you are using Nginx as your web server, you'll need to create a configuration file for your site. Below is an example of what an Nginx configuration file might look like for a Laravel application:

nginx
Copy code
server {
listen 80; # Listen on port 80 for HTTP requests
server_name your-server-url; # Replace with your server's domain or IP

    # Point to the public directory of your Laravel project
    root /path-to-your-project/public;
    index index.php index.html;

    # Handle requests
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Process PHP scripts
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;  # Adjust PHP version as needed
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to .htaccess files (Apache-specific files)
    location ~ /\.ht {
        deny all;
    }

}
server_name: Replace your-server-url with your domain or IP address.
root: Replace /path-to-your-project/public with the full path to the public directory of your Laravel application.
fastcgi_pass: Ensure this points to the correct socket or port for PHP-FPM (PHP FastCGI Process Manager).
Reload the Web Server:

After creating or updating the configuration file, reload or restart the web server to apply the changes. For Nginx, you would run:
bash
Copy code
sudo service nginx reload
Summary
Configuring the web server ensures that all incoming HTTP requests to your server are directed to the correct location (the public directory of your Laravel application) and that PHP scripts are processed correctly. This setup is crucial for running any web application, including those built with Laravel.
