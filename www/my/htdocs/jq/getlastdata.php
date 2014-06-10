<?php
 require_once "../config.php";
 if ($secure<0)die("fuck");
 $t=$_GET["t"];
 $k=$_GET["k"];
 $lambda=$_GET["lambda"];if (!is_numeric($lambda)||($lambda<0)||($lambda>180))$lambda=0;
 $arr=array();
 $result = array('type'=>'error');
 $dsf=substr($_GET["dsf"],6,4)."-".substr($_GET["dsf"],3,2)."-".substr($_GET["dsf"],0,2);
 $dst=substr($_GET["dst"],6,4)."-".substr($_GET["dst"],3,2)."-".substr($_GET["dst"],0,2);
# дополнительные поля если требуется выводить всё
#	lag(tstamp) over (partition by carid order by tstamp) as tstampb,
#	lag(lat) over (partition by carid order by tstamp) as latb,
#	lag(lon) over (partition by carid order by tstamp) as lonb,
//update ttt1 as tmp1 set tstampb=(select tstamp from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),latb=(select lat from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1), lonb=(select lon from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1) where tstampb is null;

 $sql="
select
	carid,
	tstamp,
	lag(tstamp) over (partition by carid order by tstamp) as tstampb,
	lat,
	lon,
	lag(lat) over (partition by carid order by tstamp) as latb,
	lag(lon) over (partition by carid order by tstamp) as lonb,
	dest,
	speed,
	azimut,
	dut[0]as dut1,
	dut[1] as dut2,
	gsmsignal,
	gpsdop,
	signal_restart
	into temporary ttt1
	from data.gps where carid =".$_GET["carid"]." and tstamp >='$dsf ".$_GET["tsf"]."' and tstamp <='$dst ".$_GET["tst"]."' and lat>0 and lon>0
	order by tstamp;
update ttt1 as tmp1 set tstampb=(select tstamp from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp < tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),latb=(select lat from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1), lonb=(select lon from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1) where tstampb is null;
update ttt1 set signal_restart=(select count(*) from data.gps as dg where dg.tstamp<ttt1.tstamp and dg.tstamp>ttt1.tstampb and signal_restart=1);
select * from ttt1 order by tstamp;";

//echo $sql;
  $res=pg_query($sql);
  if (pg_num_rows($res)<2){
   $result = array('type'=>'error', 'regions'=> array('text'=>'нет данных за указанный период'));
  }
  else{
  while ($data_new=pg_fetch_array($res)){
    //старые данные уже есть, теперь функция принимания решения заносить ли новые данные в старые или из старых данных создать отрезок а новые начать обрабатывать
    //if func_create_new_point()==true {выводим старый отрезок в массив, создаём новый отрезок}
    // else {передаём новые и старые данные в функцию для слепливания и обновляем этим старые данные}
    if (!is_array($data))$data=$data_new;
    else{
    if (compare_for_split_data ($data,$data_new,$lambda)){$data=split_data($data,$data_new);}
    else {
	$arr[]= array(
	    'tstamp'=>$data['tstamp'],
	    'lat'=>$data['lat']/600000,
	    'lon'=>$data['lon']/600000,
	    'tstampb'=>$data['tstampb'],
	    'latb'=>$data['latb']/600000,
	    'lonb'=>$data['lonb']/600000,
	    'dest'=>$data['dest'],
	    'azimut'=>$data['azimut'],
	    'speed'=>$data['speed'],
	    'signal_restart'=>$data['signal_restart'],
	    'tstamp_r'=>strtotime($data['tstamp'])-strtotime($data['tstampb'])
	);
	$data=$data_new;
	}
    }
   }
  }
   $result = array('type'=>'success', 'regions'=>$arr); 
print json_encode($result);



function compare_for_split_data($data,$data_new,$lambda){
 $ret=true;

 //определяем наличие данных с трекера
 if (($data['speed']>5)&&($data['signal_restart']==0)&&($data_new['signal_restart']!=0))$ret=false;//считаем что перестали идти данные с трекера, в движении машины
 if (($data['speed']>5)&&($data['signal_restart']!=0)&&($data_new['signal_restart']==0))$ret=false;//считаем что данные с трекера начали поступать

 // определяем движение машины
 if (($data['speed']<=5)&&($data_new['speed']>5))$ret=false;//считаем что машина поехала
 if (($data['speed']>5)&&($data_new['speed']<=5))$ret=false;//считаем что машина остановилась

 //определяем изменение азимута (если машина движется)
 if (($data['speed']>5)&&($ret)){
  $new_azimut_min=$data_new['azimut']-$lambda/2;
   if ($new_azimut_min<0)$new_azimut_min+=360;
   if ($new_azimut_min>360)$new_azimut_min-=360;
  $new_azimut_max=$data_new['azimut']+$lambda/2;
   if ($new_azimut_max<0)$new_azimut_max+=360;
   if ($new_azimut_max>360)$new_azimut_max-=360;
  if ($data['azimut']<$new_azimut_min)$ret=false;
  if ($data['azimut']>$new_azimut_max)$ret=false;
 }
 // 
 return $ret;
}//function compare_for_split_data

function split_data ($data,$data_new){
 $res=$data;
 $res['tstamp']=$data_new['tstamp'];
 $res['lat']=$data_new['lat'];
 $res['lon']=$data_new['lon'];
 $res['dest']=$data['dest']+$data_new['dest'];
 $res['speed']=round(($data['speed']+$data_new['speed'])/2);
 $res['signal_restart']=$data['signal_restart']+$data_new['signal_restart'];
 return $res;
}//function split_data
?>
