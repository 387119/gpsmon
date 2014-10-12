<?php
require_once "config.php";
echo "
<style>
#atab {
 font-family:Arial;
 font-size:10pt;
}
#atab table tr:nth-child(odd) {background: #ffffff;}
#atab table tr:nth-child(even) {background: #f6f6f6;}
</style>
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
#        <a href='javascript:cartar($carid);' style='width:30px;height:25px; background-image: url('/images/icons.png')
#;background-position: -650px -290px;'> </a>
#    &nbsp;<a href='javascript:carformedit($carid);'>изменить</a>&nbsp;
#    <a href=\"javascript:caruser($carid);\">доступ</a>&nbsp;
#<a href=\"javascript:delcar('car','машину',$carid);\">удалить</a>
    echo "<tr><td><img src='/images/cars/$icon'></td><td>$carid</td><td>$trackerid</td><td>$name</td><td>$gosnum</td><td>$fiodriver1</td><td>$teldriver1</td><td>$maxspeed</td><td>$dutlitr1 / $dutlitr2</td>
	<td style='min-width:130px;'>
    	    <a title='Тарировка баков' href='javascript:cartar($carid)' style=\"float:left; width:30px; height:25px; background-image: url('/images/icons.png'); background-position: -650px -290px;\"> </a>
    	    <a title='Параметры машины' href='javascript:carformedit($carid)' style=\"float:left; width:30px; height:25px; background-image: url('/images/icons.png'); background-position: -324px -803px;\"> </a>
    	    <a title='Доступ работников к машине' href='javascript:caruser($carid)' style=\"float:left; width:30px; height:25px; background-image: url('/images/icons.png'); background-position: -90px -663px;\"> </a>
    	    <a title='Удалить машину' href=\"javascript:delcar('car','машину',$carid)\" style=\"float:left; width:30px; height:25px; background-image: url('/images/icons.png'); background-position: -790px -56px;\"> </a>
	</td></tr>";
  }
echo"</table></div>";
echo "<button id=newcar onclick=\"carformnew();\">Новая машина</button>";
?>
