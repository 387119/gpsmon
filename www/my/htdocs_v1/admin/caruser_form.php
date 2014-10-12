<?php
require_once "config.php";
$car=$_GET['c'];
echo "
<div id='atab' style='overflow:auto'>
 <form action='/admin/caruser_send.php' method=post id='caruserform'>
 <input type=hidden name=carid value='$car'>
 <table>
    <thead>
      <tr><th></th><th>login</th><th>ФИО</th></tr>
    </thead>";
//весь список пользователей текущего предприятия к которому принадлежит эта машина, и указание пользователей которые уже назначенны данной машине.
 $sql="select userid as uid,login,fam,name,otch,(select 1 from users_cars as uc where uc.userid=u1.userid and carid=$car)::integer as inst from users as u1 where clientid in (select distinct clientid from cars where carid=$car);";
 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    if ($inst==1)$sel="checked";else $sel="";
    echo "<tr><td><input id=carsusers[$uid] name=carsusers[$uid] type=checkbox $sel></td><td>$login</td><td>$fam $name $otch</td></tr>";
  }
echo"</table></form></div>";
echo "<button id=newcar onclick=\"caruser_send($car);\">Сохранить</button>";
?>
