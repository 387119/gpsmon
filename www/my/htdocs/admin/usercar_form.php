<?php
require_once "config.php";
$usr=$_GET['u'];
echo "
<div id='atab' style='overflow:auto'>
 <form action='/admin/usercar_send.php' method=post id='usercarform'>
 <input type=hidden name=uid value='$usr'>
 <table>
    <thead>
      <tr><th></th><th>№ машины</th><th>Название</th><th>Гос. номер</th></tr>
    </thead>";

 $sql="select carid,name,gosnum,(select 1 from users_cars as uc where userid=$usr and uc.carid=c1.carid)::integer as inst from cars as c1 where clientid in (select clientid from users where userid = $usr) order by carid;";
 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    if ($inst==1)$sel="checked";else $sel="";
    echo "<tr><td><input id=userscars[$carid] name=userscars[$carid] type=checkbox $sel></td><td>$carid</td><td>$name</td><td>$gosnum</td></tr>";
  }
echo"</table></form></div>";
echo "<button id=newcar onclick=\"usercar_send($usr);\">Сохранить</button>";
?>
