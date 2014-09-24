<?php
require_once("config.php");
$NAME=$_REQUEST['name'];
$sql="select clientid from users where userid=$userid";
$res=pg_query($sql);
$resf=pg_fetch_array($res);
extract($resf,EXTR_OVERWRITE);

$sql="insert into departments (departmentname,clientid) values ('$NAME',$clientid) returning *";
$res=pg_query($sql);
if (pg_num_rows($res)==1)
	$return=array("type"=>"success");
else
	$return=Array("type"=>"error","error"=>pg_last_error($res));
echo json_encode ($return);
?>
