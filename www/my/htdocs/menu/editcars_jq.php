<?php
require_once ("../config.php");
$carid=$_POST['carid'];
$name=$_POST['name'];
$fiodriver1=$_POST['fiodriver1'];
$teldriver1=$_POST['teldriver1'];
$sql="update cars set name='$name',fiodriver1='$fiodriver1',teldriver1='$teldriver1' where carid=$carid and clientid in (select clientid from users where userid=$userid) returning *;";
$res=pg_query ($sql);

if (pg_num_rows ($res)==1)
 echo "<div class=message_success>данные машины успешно изменены</div>";
else "<div class=message_error>ошибка изменения данных машины</div>";
?>
