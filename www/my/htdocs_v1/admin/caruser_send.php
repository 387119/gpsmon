<?php
require_once "config.php";
$car=$_POST['carid'];
$users=$_POST['carsusers'];
if (is_numeric($car)){
$sql="delete from users_cars where carid=$car;";
if (count($users)>0){
 while (list($k,$d)=each($users)){
  $sql=$sql."insert into users_cars (carid,userid) values ($car,$k);";
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
