<?php
/*
curl -f -X POST -H "Content-Type: application/json" -d '{"id":"integer primary key autoincrement", "name":"text", "email":"text", "password":"text"}' $HOST/api.php/users/create
curl -f $HOST/api.php/users
curl -f -X POST -H "Content-Type: application/json" -d '{"name":"yui", "email":"test@gmail.com", "password":"1234"}' $HOST/api.php/users

curl -f -X POST -H "Content-Type: application/json" -d '{"user":"yui", "password":"1234"}' $HOST/api.php/login
*/

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


require 'auth.php';
$auth = new PHP_API_AUTH(array(
  'path'=>'login',
  //'algorithm'=>'HS512',
  'secret'=>'secret key is here',
  'authenticator'=>function($user, $pass) {
    //$sql = "select * from users where email=`".$email."` and password=`".$pass."`";
    //if ($user=='user' && $pass=='pass') {
      $_SESSION['user']=$user;
      //echo $user;
    //}
  }
));
if ($auth->executeCommand()) exit(0);
if (/*empty($_SESSION['user']) ||*/ !$auth->hasValidCsrfToken()) {
//	header('HTTP/1.0 401 Unauthorized');
//	exit(0);
}


//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Credentials: true');


// defines
$DATABASE_NAME = dirname(__FILE__).'/data.db';

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));

// Decode raw body typ aplication/json
$body = json_decode(file_get_contents('php://input'), true);

// connect to the sqlite database
try {
  $pdo = new PDO('sqlite:'.$DATABASE_NAME);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // WARNING | EXCEPTION | SILENT
  $pdo->query("CREATE TABLE IF NOT EXISTS users ( 
    id           INTEGER         PRIMARY KEY AUTOINCREMENT,
    name         TEXT,
    email        TEXT,
    password     TEXT
  );");
} catch (Exception $e) {
  echo "Can't connect SQLite: ".$e->getMessage();
  die();
}

// compare le premier element de notre tableau de requete contre une regexp 
$table = preg_replace('/[^a-z0-9_]+/i', '', array_shift($request));
// id est le deuxième élément du tableau (+0 en fait un int)
//$id = array_shift($request)+0;
$cmd = preg_replace('/[^a-z0-9_]+/i', '', array_shift($request));
switch ($cmd) {
case 'create':
  $id = 0;
  break;
default:
  $cmd = NULL;
  $id = $cmd+0;
}

$columns;
$values;

if ($body) {
  // escape the columns and values from the input object
  $columns = preg_replace('/[^a-z0-9_]+/i', '', array_keys($body));
  $values = array_map(function ($value) {
    if ($value===null) return null;
    else if (is_string($value)) return '"'.$value.'"';
    else return $value;
  }, array_values($body));

  $set = '';
  for ($i=0; $i<count($columns); $i++) {
    $set .= ($i>0?',':'').$columns[$i].'=';
    $set .= ($values[$i]===null?'NULL':$values[$i]);
  }
}

// create SQL based on HTTP method
switch ($method) {
case 'GET':
  //$sql = "SELECT * from `$table`".($id?" WHERE id=$id":'');
  $sql = 'SELECT * from `'.$table.'`'.($id?' WHERE id='.$id:'');
  break;
case 'PUT':
  //$sql = "UPDATE `$table` SET ".$set." WHERE id=$id";
  $sql = 'UPDATE `'.$table.'` SET '.$set.' WHERE id='.$id;
  break;
case 'POST':
  switch ($table) {
  case 'login':
    /*echo $table."\n";
    echo implode(", ", $columns)."\n";
    echo implode(", ", $values)."\n";*/
    if ($auth->executeCommand()) exit(0);
    if (/*empty($_SESSION['user']) ||*/ !$auth->hasValidCsrfToken()) {
    	header('HTTP/1.0 401 Unauthorized');
    	exit(0);
    }
    die();
  }

  //$sql = "INSERT INTO `$table` (".implode(", ", $columns).") VALUES (".implode(", ", $values).")";
  if (empty($cmd)) {
    $sql = 'INSERT INTO `'.$table.'` ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).')';
  } else {
    // create table
    $s = '';
    for ($i=0; $i<count($columns); $i++) {
      $s .= $columns[$i].' '.substr($values[$i], 1, -1).", ";
    }
    $s = substr($s, 0, -2);
    //$sql = 'DROP TABLE `'.$table.'`; CREATE TABLE `'.$table.'` ('.$s.')';
    $sql = 'CREATE TABLE IF NOT EXISTS `'.$table.'` ('.$s.')';
  }
  break;
case 'DELETE':
  //$sql = $pdo->prepare("DELETE `$table` WHERE id=$id");
  $sql = $pdo->prepare('DELETE `'.$table.'` WHERE id='.$id);
  break;
}
//echo $sql."\n";
 
// excecute SQL statement
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $result = $stmt->fetchAll();
} catch (Exception $e) {
  echo $e->getMessage()."\n";
  http_response_code(404);
  die();
}

// die if SQL statement failed
/*if (!$result) {
  http_response_code(404);
  die();
} else {
  header('Content-Type: application/json');
  echo json_encode($result);
  die();
}*/
		/*if ($result = $this->db->query($sql,$params)) {
			echo '"columns":';
			$keys = array_keys($fields[$table]);
			echo json_encode($keys);
			$keys = array_flip($keys);
			echo ',"records":[';
			$first_row = true;
			while ($row = $this->fetchRow($result,$fields[$table])) {
				if ($first_row) $first_row = false;
				else echo ',';
				if (isset($collect[$table])) {
					foreach (array_keys($collect[$table]) as $field) {
						$collect[$table][$field][] = $row[$keys[$field]];
					}
				}
				echo json_encode($row);
			}
			$this->db->close($result);
			echo ']';
			if ($count) echo ',';
		}
		if ($count) echo '"results":'.$count;
		echo '}';*/

  header('Content-Type: application/json');
  echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE);
  die();
?>
