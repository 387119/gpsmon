<?php
require_once "config.php";
# car tracker user
$u=$_GET['u'];
$p=$_GET['p'];
$res="не найден пользователь";
if (is_numeric($u)){
$sql="
insert into logs (userid,text)values ($userid,'изменён пароль пользователю '|| (select login from users where userid=$u)|| ', id пользователя $u');
update users set password='$p' where userid=$u returning *;";
$res=pg_query($sql);
if (pg_num_rows($res)==1)$res=1;
 else $res="ошибка изменения пароля";
}
echo $res;
?>
