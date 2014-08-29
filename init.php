<?php
set_time_limit(120);

require_once 'MDB2.php';

$username=$_SERVER['PHP_AUTH_USER'];
$DBHOST="localhost";
$WEBHOST="guam.csis.u-tokyo.ac.jp";

$db = MDB2::connect('pgsql://'.$username.'@'.$DBHOST.'/crowdsourcing?charset=utf8');

if(PEAR::isError($db)) {
  print('There is an error with connection to the database. Please contact with administrator.');
}

//if (!($cn = pg_connect("host=".$DBHOST." port=5432 dbname=crowdsourcing user=". $username))) {
//    $DBHOST="10.8.0.1";
//    $cn = pg_connect("host=".$DBHOST." port=5432 dbname=crowdsourcing user=". $username);
//}
?>
