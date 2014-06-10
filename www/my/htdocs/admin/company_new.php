<?php
require_once "config.php";
if ($secure>=90){
$name=$_GET['n'];
$ret="";
 $sql="select count(*) from clients where company_name='$name';";
 $res=pg_query ($sql);
 $x=pg_result($res,0,"count");
 if ($x!=0)$ret="Такое предприятие уже существует";
 else {
  $sql="insert into clients (company_name,pays_client) values ('$name',0);";
  $res=pg_query ($sql);
 }
 echo $ret;
}
?>
