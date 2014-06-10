<?php
require_once "config.php";
echo "
<div id='cartar'></div>
<div id='atab'>
 <table>
    <thead>
      <tr><th></th><th>№ машины</th><th>№ трекера</th><th>Название</th><th>Гос. номер</th><th>ФИО водителя</th><th>тел. водителя</th><th>макс. скорость</th><th>литраж баков</th><th>Управление</th></tr>
    </thead>";

 $sql="
select carid,fiodriver1,teldriver1,name,gosnum,icon,maxspeed,dutlitr[1] as dutlitr1,dutlitr[2] as dutlitr2,(select trackerid from trackers where cars.carid=trackers.carid limit 1) as trackerid 
   from cars 
   where clientid in (select clientid from users where userid=$userid) order by carid;";

 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    if (strcmp($dutlitr1,"")==0)$dutlitr1="нет";
    if (strcmp($dutlitr2,"")==0)$dutlitr2="нет";
    echo "<tr><td><img src='/images/cars/$icon'></td><td>$carid</td><td>$trackerid</td><td>$name</td><td>$gosnum</td><td>$fiodriver1</td><td>$teldriver1</td><td>$maxspeed</td><td>$dutlitr1 / $dutlitr2</td><td><a href='javascript:cartar($carid);'>тарировка</a>&nbsp;<a href='javascript:carformedit($carid);'>изменить</a>&nbsp;<a href=\"javascript:caruser($carid);\">доступ</a>&nbsp;<a href=\"javascript:delcar('car','машину',$carid);\">удалить</a></td></tr>";
  }
echo"</table></div>";
echo "<button id=newcar onclick=\"carformnew();\">Новая машина</button>";
?>
