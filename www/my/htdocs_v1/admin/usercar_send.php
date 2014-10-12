<?php
require_once "config.php";
$uid=$_POST['uid'];
$cars=$_POST['userscars'];
if (is_numeric($uid)){
$sql="delete from users_cars where userid=$uid;";
if (count($cars)>0){
 while (list($k,$d)=each($cars)){
  $sql=$sql."insert into users_cars (carid,userid) values ($k,$uid);";
 }
}
pg_query($sql);
}

//весь список пользователей текущего предприятия к которому принадлежит эта машина, и указание пользователей которые уже назначенны данной машине.
// $sql="select userid as uid,login,fam,name,otch,(select 1 from users_cars as uc where uc.userid=u1.userid and carid=$car)::integer as inst from users as u1 where clientid in (select distinct clientid from cars where carid=$car);";
// $res=pg_query ($sql);
//  while ($resf=pg_fetch_array($res)){
//   extract ($resf,EXTR_OVERWRITE);
//    if ($inst==1)$sel="checked";else $sel="";
//    echo "<tr><td><input id=carsusers[$uid] name=carsusers[$uid] type=checkbox $sel></td><td>$login</td><td>$fam $name $otch</td></tr>";
//  }
//echo"</table></div>";
//echo "<button id=newcar onclick=\"caruser_send($car);\">Сохранить</button>";
header("Location: https://my.gpsmon.org/admin/admin.php");

?>
