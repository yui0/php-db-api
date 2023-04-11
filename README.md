# PHP-DB-API

![GitHub Repo stars](https://img.shields.io/github/stars/yui0/php-db-api?style=social)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/yui0/php-db-api)
![Lines of code](https://img.shields.io/tokei/lines/github/yui0/php-db-api)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

Single File PHP Script that adds a REST API for SQLite.

## Features

* RESTful
* Support SQLite
* Support JWT authentication

## How to use

Upload "api.php" and "config.php" to your server.

And edit "config.php".
```
 'database' => 'data/data.db',
 'algorithm' => 'HS512',
 'secret' => 'secret key is here'
```

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
curl -f $HOST/api.php/users?filter=id,lt,2
curl -f $HOST/api.php/users?filter=id,le,2
curl -f $HOST/api.php/users?filter=id,gt,2
curl -f $HOST/api.php/users?filter=id,ge,2

# Add
curl -f -X POST -H "Content-Type: application/json" -d '{"name":"yui", "email":"test@gmail.com", "password":"1234"}' $HOST/api.php/users

# Login
curl -f -X POST -H "Content-Type: application/json" -d '{"user":"yui", "password":"1234"}' $HOST/api.php/login
```

## Requirements

* PHP 7.0 or higher with PDO drivers enabled for SQLite 3.16 or higher

## Filters

Filters provide search functionality, on list calls, using the "filter" parameter. You need to specify the column name, a comma, the match type, another comma and the value you want to filter on. These are supported match types:

- "cs": contain string (string contains value)
- "sw": start with (string starts with value)
- "ew": end with (string end with value)
- "eq": equal (string or number matches exactly)
- "lt": lower than (number is lower than value)
- "le": lower or equal (number is lower than or equal to value)
- "ge": greater or equal (number is higher than or equal to value)
- "gt": greater than (number is higher than value)
- "bt": between (number is between two comma separated values)
- "in": in (number or string is in comma separated list of values)
- "is": is null (field contains "NULL" value)

