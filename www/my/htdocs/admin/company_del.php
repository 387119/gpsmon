<?php
require_once "config.php";
if ($secure>=90){
$c=$_GET['c'];
 $sql="delete from clients where clientid=$c returning *;";
 $res=pg_query ($sql);
 echo pg_num_rows($res);
}
?>
