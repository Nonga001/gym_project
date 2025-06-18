# Gym Management System

A web-based gym management system that allows users to book services and administrators to manage bookings and services.

## Features

- User registration and login
- Service booking system
- Admin dashboard for managing bookings
- Service management
- Responsive design

## Requirements

- PHP 7.4 or higher
- MongoDB
- Composer

## Installation

1. Clone the repository:
```bash
git clone [your-repository-url]
cd gym
```

2. Install dependencies:
```bash
composer install
```

3. Create a `config.php` file:
```bash
cp config.php.example config.php
```

4. Update the `config.php` file with your MongoDB credentials and other settings.

5. Set up your web server to point to the project directory.

## Configuration

1. Create a MongoDB database
2. Update the `config.php` file with your MongoDB connection string
3. Set up your domain in the `SITE_URL` constant

## Security

- Never commit the `config.php` file
- Keep your MongoDB credentials secure
- Use environment variables in production

## License

[Your chosen license] 