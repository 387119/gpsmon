<?php
 require_once "../config.php";
 if ($secure<0)die("fuck");
 $arr=array();
 $stst=date("d-m-Y H:i:s");

  $sql="select case when show_webnotify then 1 else 0 end as show_webnotify from users where userid=$userid";
  $res=pg_query($sql);
  $show=pg_result ($res,0,"show_webnotify");
  if ($show==1){
// блок определения превышения скорости машинами
  $sql="select cars.name, cars.gosnum, doo.speed from data.online as doo, cars 
  		where doo.carid=cars.carid 
		      and doo.speed>cars.maxspeed 
		      and cars.clientid in (select clientid from users where userid=$userid)
		      and cars.carid in (select carid from users_cars where userid =$userid)";
//echo $sql;
  $res=pg_query($sql);

//  if (pg_num_rows($res)<2){$result = array('type'=>'empty', 'regions'=> array('text'=>'нет данных за указанный период'));}
  while ($data_new=pg_fetch_array($res)){
   extract ($data_new,EXTR_OVERWRITE);
	$arr[]= array('type'=>'warning','text'=>"$stst Машина $name госномер $gosnum превышение скорости $speed км/ч");
  }
}

$result = array('type'=>'success', 'regions'=>$arr); 
print json_encode($result);

?>
