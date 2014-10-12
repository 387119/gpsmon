<?php
require_once "config.php";
echo "<div id='atab'>
 <table>
    <thead>
      <tr><th>№ трекера</th><th>№ машины</th><th>серийный №</th><th>imei</th><th>телефон</th><th>пароль</th><th>Управление</th></tr>
    </thead>";

 $sql="
select serialnum,trackerid,imei,phone,passwd,carid 
    from trackers 
    where clientid in (select clientid from users where userid=$userid) order by trackerid;
";

 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    echo "<tr><td>$trackerid</td><td>$carid</td><td>$serialnum</td><td>$imei</td><td>$phone</td><td>$passwd</td><td><a href='javascript:trackerformedit($trackerid);'>изменить</a>&nbsp;
<a href=\"javascript:delcar('tracker','трекер',$trackerid);\">удалить</a></td></tr>";
  }

echo"</table></div>";
echo "<button id=newtracker  onclick=\"trackerformnew();\">Новый трекер</button>";
?>

