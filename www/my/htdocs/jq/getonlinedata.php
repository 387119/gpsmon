<?php
 require_once "../config.php";
 if ($secure<0)die("fuck");
 $t=$_GET["t"];
 $k=$_GET["k"];
 $arr=array();
 $result = array('type'=>'error');

 // получаем список онлайн данных по машинам и передаём его на вывод
# carid,name,gosnum,icon,tstamp,speed,lat,lon,
  $sql="
  select carid,azimut,speed,lat/600000::real as lat,lon/600000::real as lon,tstamp,dest_day/1000::real as dest_day,((gpsdop*6)/10)::integer as gpsdop,
   (select icon from cars where cars.carid=do1.carid) as icon,
   (select name from cars where cars.carid=do1.carid) as name,
   (select fiodriver1 from cars where cars.carid=do1.carid) as fiodriver1,
   (select teldriver1 from cars where cars.carid=do1.carid) as teldriver1,
   (select gosnum from cars where cars.carid=do1.carid) as gosnum,
   (select maxspeed from cars where cars.carid=do1.carid) as maxspeed,
       case 
         when (gpsdop/10<=8) then 'on'
         when (gpsdop/10<=20) then 'bad'
         else 'off'
       end as gpsicon,
    ceil(gsmsignal*5/32)::integer as gsmsignal,
    case  when (now()-tstamp<interval '20 minutes') then 'black' when (now()-tstamp<interval '3 hour') then 'c88e25' else 'red' end  as colortstamp
    from data.online as do1 where carid in (select carid from users_cars where userid=$userid) and lat !=0 and lon !=0;
                                                                                                                                                                                                                                 
";
  $res=pg_query($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
   $arr[]= array('carid'=>$carid,
		 'name'=>"$name",
		 'gosnum'=>"$gosnum",
		 'lat'=>"$lat",
		 'lon'=>"$lon",
		 'icon'=>"$icon",
		 'name'=>"$name",
		 'maxtstamp'=>"$tstamp",
		 'gpsicon'=>"$gpsicon",
		 'speed'=>"$speed",
		 'dsts'=>"$dest_day",
		 'azimut'=>"$azimut",
		 'fiodriver1'=>"$fiodriver1",
		 'teldriver1'=>"$teldriver1",
		 'gosnum'=>"$gosnum",
		 'gsmsignal'=>"$gsmsignal",
		 'colortstamp'=>"$colortstamp",
		 'gpsdop'=>"$gpsdop",
		 'maxspeed'=>"$maxspeed"
		  );

  }
 $result = array('type'=>'success', 'regions'=>$arr);
print json_encode($result);

?>

