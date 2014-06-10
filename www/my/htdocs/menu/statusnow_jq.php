<?php
require_once ("../config.php");
#<img src='images/icon_history.png' border=0 width=30 height=30 alt='Построить трек' title='Построить трек'></a><a href='#'><img src='images/icon_report.png' border=0 width=30 height=30 alt='Создать отчёт' title='Создать отчёт'></a><a href='#'><img src='images/icon_alarm.png' border=0 width=30 height=30 alt='Геозоны' title='Геозоны'></a>
 
$sql="select carid,icon,name,gosnum,(select tstamp from data.online as don where don.carid=cars.carid)::timestamp without time zone as lasttstamp,fiodriver1,teldriver1,
		 (select speed from data.online as don where don.carid=cars.carid) as speed,
		 (select dest_day from data.online as don where don.carid=cars.carid)/1000::real as dest_day,
		 (select lat from data.online as don where don.carid=cars.carid)/600000::real as lat,
		 (select lon from data.online as don where don.carid=cars.carid)/600000::real as lon,
		 (select ceil(gsmsignal*5/32) from data.online as don where don.carid=cars.carid)::integer as gsmsig,
		 (select case  when (gpsdop/10<=8) then 'on' when (gpsdop/10<=20) then 'bad' else 'off' end from data.online as don  where don.carid=cars.carid) as gpsicon,
		 (select case  when (now()-tstamp<interval '20 minutes') then 'black' when (now()-tstamp<interval '3 hour') then 'c88e25' else 'red' end from data.online as don  where don.carid=cars.carid) as tstamp_color
		from cars where clientid in (select clientid from users where userid=$userid) and carid in (select carid from users_cars where userid=$userid) order by name;";
$res=pg_query($sql);
$odd="";
while ($resf=pg_fetch_array($res)){
 extract($resf,EXTR_OVERWRITE);
 if ((strcmp($gsmsig,"")==0)or ($gsmsig<0))$gsmsig=0;
 if ($gsmsig>5)$gsmsig=5;
 if (strcmp($odd,"")==0)$odd="class=odd";else $odd="";
 echo "<tr $odd><td><img src='images/cars/$icon'></td><td>$name</td><td>$gosnum</td><td>$speed</td><td>$dest_day</td><td><img src=images/gps_s_$gpsicon.png></td>
 <td><img src=images/gsmsig$gsmsig.png></td><td style='color:$tstamp_color;'>$lasttstamp</td>
 <td><a href='javascript:center_car($carid);'><img src='images/icon_now.png' border=0 width=30 height=30 alt='Показать' title='Показать на карте'></a><a href='#'><a href='javascript:menu_editcars_show($carid);'><img src='images/icon_settings.png' border=0 width=30 height=30 alt='Изменить' title='Изменить информацию о машине'></a></td><td>$fiodriver1</td><td>$teldriver1</td></tr>";
}
?>
