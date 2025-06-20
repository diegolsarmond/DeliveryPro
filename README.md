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

## Folder Overview

- **ajax/** – PHP endpoints accessed via AJAX for actions such as managing
  orders, products and settings.
- **classes/** – Core PHP classes that interact with external services and
  implement the application logic.
- **includes/** – Reusable page fragments that compose the main interfaces of
  the application.

Additional folders like `assets/`, `css/`, `database/` and others contain
static resources and auxiliary scripts used by the system.

