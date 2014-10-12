<?php
require_once ("../config.php");
$carid=$_POST['carid'];
$name=$_POST['name'];
$fiodriver1=$_POST['fiodriver1'];
$teldriver1=$_POST['teldriver1'];
$nlocation=$_POST['nlocation'];
list($nlat,$nlon)=split(', ',$nlocation);
if ((is_numeric($nlat))and(is_numeric($nlon))){
    $sql="update data.online set location='POINT($nlon $nlat)',tstamp=CURRENT_TIMESTAMP,tstamp_lastupdate=CURRENT_TIMESTAMP,who_lastupdate='manual' where carid=$carid and carid in (select carid from cars where clientid in (select clientid from users where userid=$userid)) returning *";
    file_put_contents("/tmp/qwerqwer","$sql");
    $res=pg_query ($sql);
    if (pg_num_rows ($res)==1)
     echo "<div class=message_success>Координаты изменены</div>";
    else
     echo "<div class=message_error>Ошибка изменения координат</div>";
}
$sql="update cars set name='$name',fiodriver1='$fiodriver1',teldriver1='$teldriver1' where carid=$carid and clientid in (select clientid from users where userid=$userid) returning *;";
$res=pg_query ($sql);

if (pg_num_rows ($res)==1)
 echo "<div class=message_success>данные машины успешно изменены</div>";
else "<div class=message_error>ошибка изменения данных машины</div>";
?>
