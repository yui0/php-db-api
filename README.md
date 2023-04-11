# PHP-DB-API

![GitHub Repo stars](https://img.shields.io/github/stars/yui0/php-db-api?style=social)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/yui0/php-db-api)
![Lines of code](https://img.shields.io/tokei/lines/github/yui0/php-db-api)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/yui0/php-db-api)](https://github.com/yui0/php-db-api/releases)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

Single File PHP Script that adds a REST API for SQLite.

## How to use

Upload "api.php" to your server.

For local development you may run PHP's built-in web server:

```
php -S localhost:8080
```

Test the script by opening the following URL:

```
http://localhost:8080/api.php/records/posts/1
```

```
HOST=http://localhost:8080

# Create a table
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"integer primary key autoincrement", "name":"text", "email":"text", "password":"text"}' $HOST/api.php/users/create

# List
curl -f $HOST/api.php/users

# Get
curl -f $HOST/api.php/users/1
curl -f $HOST/api.php/users?filter=password,eq,1234

# Add
curl -f -X POST -H "Content-Type: application/json" -d '{"name":"yui", "email":"test@gmail.com", "password":"1234"}' $HOST/api.php/users

# Login
curl -f -X POST -H "Content-Type: application/json" -d '{"user":"yui", "password":"1234"}' $HOST/api.php/login
```

## Requirements

* PHP 7.0 or higher with PDO drivers enabled for SQLite 3.16 or higher


