<?php
// Copyright © 2023 Yuichiro Nakada

//--- whether to report errors
ini_set("display_errors", 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

//--- session
//session_start();

//--- defines
$conf = include(__DIR__.'/config.php');

//--- Authentication
class PHP_API_AUTH
{
  public function __construct($config)
  {
    extract($config);

    $verb = isset($verb)?$verb:null;
    $path = isset($path)?$path:null;
    $user = isset($user)?$user:null;
    $password = isset($password)?$password:null;
    $token = isset($token)?$token:null;
    $authenticator = isset($authenticator)?$authenticator:null;

    $method = isset($method)?$method:null;
    $request = isset($request)?$request:null;
    $post = isset($post)?$post:null;
    $origin = isset($origin)?$origin:null;

    $time = isset($time)?$time:null;
    $leeway = isset($leeway)?$leeway:null;
    $ttl = isset($ttl)?$ttl:null;
    $algorithm = isset($algorithm)?$algorithm:null;
    $secret = isset($secret)?$secret:null;

    $allow_origin = isset($allow_origin)?$allow_origin:null;

    // defaults
    if (!$verb) {
      $verb = 'POST';
    }
    if (!$path) {
      $path = '';
    }
    if (!$user) {
      $user = 'user';
    }
    if (!$password) {
      $password = 'password';
    }
    if (!$token) {
      $token = 'token';
    }

    if (!$method) {
      $method = $_SERVER['REQUEST_METHOD'];
    }
    if (!$request) {
      $request = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
      if (!$request) {
        $request = isset($_SERVER['ORIG_PATH_INFO'])?$_SERVER['ORIG_PATH_INFO']:'';
      }
    }
    if (!$post) {
      $post = 'php://input';
    }
    if (!$origin) {
      $origin = isset($_SERVER['HTTP_ORIGIN'])?$_SERVER['HTTP_ORIGIN']:'';
    }

    if (!$time) {
      $time = time();
    }
    if (!$leeway) {
      $leeway = 5;
    }
    if (!$ttl) {
      $ttl = 30;
    }
    if (!$algorithm) {
      $algorithm = 'HS256';
    }

    if ($allow_origin===null) {
      $allow_origin = '*';
    }

    $request = trim($request,'/');

    $this->settings = compact('verb', 'path', 'user', 'password', 'token', 'authenticator', 'method', 'request', 'post', 'origin', 'time', 'leeway', 'ttl', 'algorithm', 'secret', 'allow_origin');
  }

  protected function retrieveInput($post)
  {
    $input = (object)array();
    $data = trim(file_get_contents($post));
    if (strlen($data)>0) {
      if ($data[0]=='{') {
        $input = json_decode($data);
      } else {
        parse_str($data, $input);
        $input = (object)$input;
      }
    }
    return $input;
  }

  protected function generateToken($claims,$time,$ttl,$algorithm,$secret)
  {
    $algorithms = array('HS256'=>'sha256','HS384'=>'sha384','HS512'=>'sha512');
    $header = array();
    $header['typ'] = 'JWT';
    $header['alg'] = $algorithm;
    $token = array();
    $token[0] = rtrim(strtr(base64_encode(json_encode((object)$header)),'+/','-_'),'=');
    $claims['iat'] = $time;
    $claims['exp'] = $time + $ttl;
    $token[1] = rtrim(strtr(base64_encode(json_encode((object)$claims)),'+/','-_'),'=');
    if (!isset($algorithms[$algorithm])) {
      return false;
    }
    $hmac = $algorithms[$algorithm];
    $signature = hash_hmac($hmac,"$token[0].$token[1]",$secret,true);
    $token[2] = rtrim(strtr(base64_encode($signature),'+/','-_'),'=');
    return implode('.',$token);
  }

  protected function getVerifiedClaims($token,$time,$leeway,$ttl,$algorithm,$secret)
  {
    //echo $token,$time,$leeway,$ttl,$algorithm,$secret;
    $algorithms = array('HS256'=>'sha256','HS384'=>'sha384','HS512'=>'sha512');
    if (!isset($algorithms[$algorithm])) {
      return false;
    }
    $hmac = $algorithms[$algorithm];
    $token = explode('.',$token);
    if (count($token)<3) {
      return false;
    }
    $header = json_decode(base64_decode(strtr($token[0],'-_','+/')),true);
    if (!$secret) {
      return false;
    }
    if ($header['typ']!='JWT') {
      return false;
    }
    if ($header['alg']!=$algorithm) {
      return false;
    }
    $signature = bin2hex(base64_decode(strtr($token[2],'-_','+/')));
    if ($signature!=hash_hmac($hmac,"$token[0].$token[1]",$secret)) {
      return false;
    }
    $claims = json_decode(base64_decode(strtr($token[1],'-_','+/')),true);
    if (!$claims) {
      return false;
    }
    if (isset($claims['nbf']) && $time+$leeway<$claims['nbf']) {
      return false;
    }
    if (isset($claims['iat']) && $time+$leeway<$claims['iat']) {
      return false;
    }
    if (isset($claims['exp']) && $time-$leeway>$claims['exp']) {
      return false;
    }
    if (isset($claims['iat']) && !isset($claims['exp'])) {
      if ($time-$leeway>$claims['iat']+$ttl) {
        return false;
      }
    }
    return $claims;
  }

  protected function allowOrigin($origin,$allowOrigins)
  {
    if (isset($_SERVER['REQUEST_METHOD'])) {
      header('Access-Control-Allow-Credentials: true');
      header('Access-Control-Expose-Headers: X-XSRF-TOKEN');
      foreach (explode(',',$allowOrigins) as $o) {
        if (preg_match('/^'.str_replace('\*','.*',preg_quote(strtolower(trim($o)))).'$/',$origin)) {
          header('Access-Control-Allow-Origin: '.$origin);
          break;
        }
      }
    }
  }

  protected function headersCommand()
  {
    $headers = array();
    $headers[]='Access-Control-Allow-Headers: Content-Type, X-XSRF-TOKEN';
    $headers[]='Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, DELETE, PATCH';
    $headers[]='Access-Control-Allow-Credentials: true';
    $headers[]='Access-Control-Max-Age: 1728000';
    if (isset($_SERVER['REQUEST_METHOD'])) {
      foreach ($headers as $header) header($header);
    } else {
      echo json_encode($headers);
    }
  }

  public function hasValidCsrfToken()
  {
    $csrf = isset($_SESSION['csrf'])?$_SESSION['csrf']:false;
    if (!$csrf) {
      return false;
    }
    $get = isset($_GET['csrf'])?$_GET['csrf']:false;
    $header = isset($_SERVER['HTTP_X_XSRF_TOKEN'])?$_SERVER['HTTP_X_XSRF_TOKEN']:false;
    //echo "g:$get c:$csrf h:$header\n";
    return ($get == $csrf) || ($header == $csrf);
  }

  public function executeCommand()
  {
    extract($this->settings);
    if ($origin) {
      $this->allowOrigin($origin,$allow_origin);
    }
    if ($method=='OPTIONS') {
      $this->headersCommand();
      return true;
    }
    $no_session = $authenticator && $secret;
    if (!$no_session) {
      ini_set('session.cookie_httponly', 1);
      session_start();
      if (!isset($_SESSION['csrf'])) {
        if (function_exists('random_int')) {
          $_SESSION['csrf'] = 'N'.random_int(0,PHP_INT_MAX);
        } else {
          $_SESSION['csrf'] = 'N'.rand(0,PHP_INT_MAX);
        }
      }
    }
    if ($method==$verb && trim($path,'/')==$request) {
      $input = $this->retrieveInput($post);
      if ($authenticator && isset($input->$user) && isset($input->$password)) {
        // authenticator
        $authenticator($input->$user,$input->$password);
        if ($no_session) {
          //echo json_encode($this->generateToken($_SESSION,$time,$ttl,$algorithm,$secret));
          header('Content-Type: application/json');
          echo '{"token":"'.$this->generateToken($_SESSION,$time,$ttl,$algorithm,$secret).'"}';
        } else {
          session_regenerate_id();
          setcookie('XSRF-TOKEN',$_SESSION['csrf'],0,'/');
          header('X-XSRF-TOKEN: '.$_SESSION['csrf']);
          echo json_encode($_SESSION['csrf']);
        }
      } else if ($secret && isset($input->$token/*$_COOKIE['AUTH-TOKEN']*/)) {
        // get CSRF
        $claims = $this->getVerifiedClaims($input->$token/*$_COOKIE['AUTH-TOKEN']*/,$time,$leeway,$ttl,$algorithm,$secret);
        //var_dump($claims);
        if ($claims) {
          foreach ($claims as $key=>$value) {
            $_SESSION[$key] = $value;
          }
          session_regenerate_id();
          setcookie('XSRF-TOKEN',$_SESSION['csrf'],0,'/');
          header('X-XSRF-TOKEN: '.$_SESSION['csrf']);
          echo json_encode($_SESSION['csrf']);
        }
      } else {
        if (!$no_session) {
          session_destroy();
        }
      }
      return true;
    }
    return false;
  }
}
$auth = new PHP_API_AUTH(array(
  'algorithm'=>$conf['algorithm'],
  'secret'=>$conf['secret'],
));


//--- API
// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));

// Decode raw body typ aplication/json
$body = json_decode(file_get_contents('php://input'), true);
//var_dump($request);
//var_dump($body);

// connect to the sqlite database
try {
  $pdo = new PDO('sqlite:'.$conf['database']);
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

// get table name
$table = preg_replace('/[^a-z0-9_]+/i', '', array_shift($request));
if ($table===""/*POST on "/" gets hijacked*/ || in_array($table, $conf['auth_table'], true)) {
  if ($auth->executeCommand()) exit(0);
  if (empty($_SESSION['user']) || !$auth->hasValidCsrfToken()) {
  	header('HTTP/1.0 401 Unauthorized');
  	//echo "USER:".$_SESSION['user']."\n";
  	//echo "hasValidCsrfToken:".$auth->hasValidCsrfToken()."\n";
  	exit(0);
  }
}

// get id or command
$cmd = preg_replace('/[^a-z0-9_]+/i', '', array_shift($request));
switch ($cmd) {
case 'create':
  $id = 0;
  break;
default:
  $id = (int)$cmd+0;
  $cmd = NULL;
}
$where = ($id?'id='.$id:'');

// get filter
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $query);
function makeFilter($l, $c, $r)
{
  $v = $r;
  if (!is_numeric($r)) $v = "'".$r."'";

  switch ($c) {
  case 'cs': return $l.' LIKE %'.$r.'%';//FIXME:security
  case 'sw': return $l.' LIKE '.$r.'%';
  case 'ew': return $l.' LIKE %'.$r;
  case 'eq': return $l."=".$v;
  case 'lt': return $l."<".$r;
  case 'le': return $l."<=".$r;
  case 'ge': return $l.">=".$r;
  case 'gt': return $l.">".$r;
  case 'bt':
    $v = explode(',', $r);
    if (count($v)<2) return ''; // err!
    return $l.' BETWEEN '.$v[0].' AND '.$v[1];
  case 'in': return $l.' IN '.explode(',', $r);
  //case 'in': return $l.' IN '.$r;
  case 'is': return $l.' IS NULL';
  }
}
if (isset($query['filter'])) {
  //var_dump($query);
  $split = explode(",", $query['filter']);
  $where .= makeFilter($split[0], $split[1], $split[2]);
  //echo $where;
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
  //$sql = 'SELECT * from `'.$table.'`'.($id?' WHERE id='.$id:'');
  $sql = 'SELECT * from `'.$table.'`'.($where?' WHERE '.$where:'');
  break;
case 'PUT':
  //$sql = "UPDATE `$table` SET ".$set." WHERE id=$id";
  $sql = 'UPDATE `'.$table.'` SET '.$set.' WHERE id='.$id;
  break;
case 'POST':
  switch ($table) {
  case 'login':
    // authentication
    $auth = NULL;
    $auth = new PHP_API_AUTH(array(
      'path'=>'login', // URL/login
      'algorithm'=>$conf['algorithm'],
      'secret'=>$conf['secret'],
      'authenticator'=>function($user, $pass) use ($pdo) {
        session_start();
        $_SESSION = [];
        //session_destroy();

        //echo "select * from users where email='".$user."' and password='".$pass."'";
        $stmt = $pdo->prepare("select * from users where email='".$user."' and password='".$pass."'");
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          // success
          session_regenerate_id();
          $_SESSION['user'] = $user; // JWT token's user
        } else {
          header('HTTP/1.0 401 Unauthorized');
          exit(0);
        }
      }
    ));
    if ($auth->executeCommand()) exit(0);
    /*if (empty($_SESSION['user']) || !$auth->hasValidCsrfToken()) {
    	header('HTTP/1.0 401 Unauthorized');
    	exit(0);
    }*/
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
  $sql = 'DELETE FROM `'.$table.'` WHERE id='.$id;
  break;
}
//echo $method."\n";
//echo $sql."\n";
 
// excecute SQL statement
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $stmt->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
  //$result = $stmt->fetchAll();

  header('Content-Type: application/json');
  header('X-Frame-Options: deny');
  header('X-Content-Type-Options: nosniff');
  $assoc = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($assoc) {
    echo '{"columns":[';
    $s = "";
    $comma = "";
    foreach ($assoc as $key => $val) {
      echo $comma.'"'.$key.'"';
      $s .= $comma.'"'.$val.'"';
      $comma = ",";
    }
    echo ']';

    echo ',"records":[';
    echo "[".$s."]";
    while ($assoc = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo ',[';
      $comma = "";
      foreach ($assoc as $key => $val) {
        echo $comma.'"'.$val.'"';
        $comma = ",";
      }
      echo ']';
    }
    echo ']}';
  } else { // nothing to return
    http_response_code(200);
  }
} catch (Exception $e) {
  //echo "SQL:".$sql."\n";
  //echo $e->getMessage()."\n";
  http_response_code(404);
  die();
}
?>
