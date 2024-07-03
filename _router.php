<?php
/*if (file_exists($_SERVER["REQUEST_FILENAME"])) {
  return false;
} else {
  require "api.php";
}*/
#echo "URI:".$_SERVER['REQUEST_URI'];
if (strpos($_SERVER['REQUEST_URI'], '/api.php') !== false) {
  $_SERVER['PATH_INFO'] = substr($_SERVER['REQUEST_URI'], 9);
  #echo "PATH:".$_SERVER['PATH_INFO'];
  require "api.php";
}
else if (strpos($_SERVER['REQUEST_URI'], '/api') !== false) {
  $_SERVER['PATH_INFO'] = substr($_SERVER['REQUEST_URI'], 5);
  #echo "PATH:".$_SERVER['PATH_INFO'];
  require "api.php";
}
else return false;
