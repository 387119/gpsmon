<?php
require_once ("../config.php");

$res=pg_query ("select icon,name,gosnum,carid,(select tstamp from data.online as don where don.carid=cars.carid)::timestamp without time zone as lasttstamp,
         (select speed from data.online as don where don.carid=cars.carid) as speed,
         (select dest_day from data.online as don where don.carid=cars.carid)/1000::real as dest_day,
         (select lat from data.online as don where don.carid=cars.carid)/600000::real as lat,
         (select lon from data.online as don where don.carid=cars.carid)/600000::real as lon,
         (select case  when (now()-tstamp<interval '20 minutes') then 'on' when (now()-tstamp<interval '3 hour') then 'bad' else 'off' end from data.online as don  where don.carid=cars.carid) as gpsicon
        from cars where clientid in (select clientid from users where userid=$userid) and carid in (select carid from users_cars where userid=$userid) order by name;");
$odd="";
while ($resf=pg_fetch_array($res)){
 extract($resf,EXTR_OVERWRITE);
 if (strcmp($odd,"")==0)$odd="class=odd";else $odd="";
 echo 
    "<tr $odd>
        <td><img src='images/cars/$icon'></td>
        <td>$name</td>
        <td>$gosnum</td>
        <td><a href='javascript:center_car($lat,$lon);'>показать</a></td>
        <td><a href='javascript:getGeozones($carid);'>показать</a></td>
    </tr>";
}
?>