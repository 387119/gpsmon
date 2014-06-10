<?php
require_once "config.php";
# car tracker user
$t=$_GET['t'];
$o=$_GET['o'];
$res="не найден тип объекта";
/// дабавить везде записи в лог
if ((strcmp($t,"car")==0)&&(is_numeric($o))){
// удаляем машину
$sql="update trackers set carid=null where carid=$o;
      delete from data.online where carid=$o;
      insert into logs (userid,text) values ($userid,'удалил машину $o');
      delete from cars where carid=$o returning *";
$res=pg_query($sql);
if (pg_num_rows($res)==1)$res=1;
 else $res="удалено ".pg_num_rows($res)." машин";
}
if ((strcmp($t,"tracker")==0)&&(is_numeric($o))){
// удаляем трекер
$sql="insert into logs (userid,text) values ($userid,'удалил трекер $o');
delete from trackers where trackerid=$o returning *";
$res=pg_query($sql);
if (pg_num_rows($res)==1)$res=1;
 else $res="удалено ".pg_num_rows($res)." трекеров";
}
if ((strcmp($t,"user")==0)&&(is_numeric($o))){
// удаляем пользователя
$sql="insert into logs (userid,text) values ($userid,'удалил пользователя $o');
delete from users where userid=$o returning *;";
$res=pg_query($sql);
if (pg_num_rows($res)==1)$res=1;
 else $res="удалено ".pg_num_rows($res)." пользователей";
}
echo $res;
?>
