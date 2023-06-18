# PHP-DB-API

![GitHub Repo stars](https://img.shields.io/github/stars/yui0/php-db-api?style=social)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/yui0/php-db-api)
![Lines of code](https://img.shields.io/tokei/lines/github/yui0/php-db-api)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

Single File PHP Script that adds a REST API for SQLite.

Example: https://github.com/yui0/rn-auth-template

## Features

* Very little code, easy to adapt and maintain
* RESTful
* Support SQLite
* Support JWT authentication

## How to use

Upload "api.php" and "config.php" to your server.

And edit "config.php" for your environment.
```
 'database' => 'data/data.db',
 'algorithm' => 'HS512',
 'secret' => 'secret key is here',
 'use_jwt' => false,
 'noauth' => [ // all table is required login
   ['table' => 'login', 'method' => 'POST'], // always need
   ['table' => 'users', 'method' => 'PUT,POST'], // FIXME: POST
   ['table' => 'population', 'method' => 'GET,PUT,POST,DELETE'],
 ],
 'auth' => [ // access right required
   ['table' => 'users', 'method' => 'GET,DELETE', 'user' => 'admin'],
 ],
```

For local development you may run PHP's built-in web server:

```
php -S localhost:8080 -t . _router.php 
```

Test the script by opening the following URL:

```
http://localhost:8080/api.php/records/posts/1
```

Using with curl:

```
HOST=http://localhost:8080

# Create a table
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"integer primary key", "tp":"integer", "tpm":"integer", "tpf":"integer", "jp":"integer", "jpm":"integer", "jpf":"integer"}' $HOST/api.php/population/create

# Add
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"2000", "tp":"126926", "tpm":"62111", "tpf":"64815", "jp":"125613", "jpm":"61488", "jpf":"64125"}' $HOST/api.php/population
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"2001", "tp":"127316", "tpm":"62265", "tpf":"65051", "jp":"125930", "jpm":"61615", "jpf":"64316"}' $HOST/api.php/population
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"2002", "tp":"127486", "tpm":"62295", "tpf":"65190", "jp":"126053", "jpm":"61629", "jpf":"64424"}' $HOST/api.php/population
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"2003", "tp":"127694", "tpm":"62368", "tpf":"65326", "jp":"126206", "jpm":"61677", "jpf":"64529"}' $HOST/api.php/population
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"2100", "tp":"127486", "tpm":"62295", "tpf":"65190", "jp":"126053", "jpm":"61629", "jpf":"64424"}' $HOST/api.php/population

# List
curl -f $HOST/api.php/population

# Get
curl -f $HOST/api.php/population/2000
curl -f $HOST/api.php/population?filter=tp,eq,127694
curl -f $HOST/api.php/population?filter=id,lt,2002
curl -f $HOST/api.php/population?filter=id,le,2002
curl -f $HOST/api.php/population?filter=id,gt,2002
curl -f $HOST/api.php/population?filter=id,ge,2002

# Update
curl -f -X PUT -H "Content-Type: application/json" -d '{"tp":"100", "tpm":"200"}' $HOST/api.php/population/2100

# Delete
curl -f -X DELETE $HOST/api.php/population/2100

# Vacuum
curl -f $HOST/api.php/population/vacuum
```

```
HOST=http://localhost:8080

# Create a table
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"integer primary key autoincrement", "name":"text", "email":"text", "password":"text"}' $HOST/api.php/users/create

# Add
curl -f -X POST -H "Content-Type: application/json" -d '{"name":"test", "email":"test@gmail.com", "password":"1234"}' $HOST/api.php/users

# List
curl -f $HOST/api.php/users
curl -f -b /tmp/cookie.txt -H "X-XSRF-TOKEN: $CSRF" $HOST/api.php/users

# Get
curl -f $HOST/api.php/users/1
curl -f $HOST/api.php/users?filter=name,eq,ai
curl -f $HOST/api.php/users?filter=password,eq,1234
curl -f $HOST/api.php/users?filter=id,lt,2
curl -f $HOST/api.php/users?filter=id,le,2
curl -f $HOST/api.php/users?filter=id,gt,2
curl -f $HOST/api.php/users?filter=id,ge,2

# Update
curl -f -X PUT -H "Content-Type: application/json" -d '{"name":"ai", "email":"ai@gmail.com", "password":"pass"}' $HOST/api.php/users/1

# Delete
curl -f -X DELETE $HOST/api.php/users/3

# Vacuum
curl -f $HOST/api.php/users/vacuum
```

Authentication:

```
$ curl -f $HOST/api.php/auth
curl: (22) The requested URL returned error: 401

# Login
$ curl -f -X POST -c /tmp/cookie.txt -H "Content-Type: application/json" -d '{"user":"test@gmail.com", "password":"1234"}' $HOST/api.php/login
$ curl -v -f -X POST -c /tmp/cookie.txt -H "Content-Type: application/json" -H "Origin: http://localhost:19006" -d '{"user":"test@gmail.com", "password":"1234"}' $HOST/api.php/login

JWT=`curl -f -X POST -c /tmp/cookie.txt -H "Content-Type: application/json" -d '{"user":"test@gmail.com", "password":"1234"}' $HOST/api.php/login | sed 's/^.*":"//' | sed 's/"}//'`
curl -f -i -X POST -c /tmp/cookie.txt $HOST/api.php/ -H "Content-Type: application/json" -d '{"token":"'$JWT'"}'
CSRF=`curl -f -X POST -c /tmp/cookie.txt $HOST/api.php/ -H "Content-Type: application/json" -d '{"token":"'$JWT'"}' | sed 's/"//g'`

curl -f -b /tmp/cookie.txt -H "X-XSRF-TOKEN: $CSRF" -X POST -H "Content-Type: application/json" -d "{\"id\":\"integer primary key autoincrement\", \"secret\":\"text\", \"date\":\"timestamp NOT NULL default (DATETIME(CURRENT_TIMESTAMP, 'LOCALTIME'))\"}" $HOST/api.php/auth/create
curl -f -b /tmp/cookie.txt -H "X-XSRF-TOKEN: $CSRF" -X POST -H "Content-Type: application/json" -d '{"secret":"secret"}' $HOST/api.php/auth
curl -f -b /tmp/cookie.txt $HOST/api.php/auth -H "X-XSRF-TOKEN: $CSRF"
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

## ref

* https://github.com/simplonco/php-rest-sqlite
* https://jwt.io
* https://knooto.info/php-jwt-simple-auth/
* https://zipcloud.ibsnet.co.jp/
