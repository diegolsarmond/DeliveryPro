# DeliveryPro

DeliveryPro is a PHP based application for managing delivery orders. The project
contains a collection of scripts and classes that can be run in any standard
LAMP environment.

## Prerequisites

- **PHP** 8.0 or higher
- **MySQL** (or MariaDB) server

## Configuration

Configuration values are read from an `.env` file located in the project root.
Start by copying `.env.example` and updating the credentials for your database:

```bash
cp .env.example .env
```

Edit `.env` and set the connection parameters:

```env
DB_HOST=localhost
DB_NAME=deliverypro
DB_USER=username
DB_PASS=password
```

## Running the Application

1. Create the database referenced in your `.env` file.
2. Place the project in your web server directory or use PHP's built in server:

```bash
php -S localhost:8000
```

3. Open `http://localhost:8000/` in your browser and log in using your user credentials.

On the first login the application automatically creates the `user_tokens` table
used to store authentication tokens. Ensure your database user has permission to
create tables.

## Authenticating with a Bearer Token

Most API endpoints expect a JWT token in the `Authorization` header. To obtain
the token, send a `POST` request to `login/index.php` with the fields
`username` and `password`:

```bash
curl -X POST http://localhost:8000/login/index.php \
     -d "username=myuser" -d "password=mypass"
```

The response will include a JSON object with a `token` field. Provide this token
when calling protected endpoints:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api.php/algum-endpoint
```


