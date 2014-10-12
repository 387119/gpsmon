<?php
include("./config/php");
$arrr=report_general_dut_get_stops (165,"2013-09-18 00:00:00","2013-09-20 23:59:59");
//echo "1111111<br>";
//print_r($arrr);

function report_general_dut_get_stops ($carid,$tfrom,$tto){
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
	from data.gps where carid =".$carid." and tstamp >='".$tfrom."' and tstamp <='".$tto."' and lat>0 and lon>0
	order by tstamp;
update ttt1 as tmp1 set tstampb=(select tstamp from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp < tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),latb=(select lat from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1), lonb=(select lon from data.gps as dg where dg.carid=tmp1.carid and dg.tstamp <= tmp1.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1) where tstampb is null;
update ttt1 set signal_restart=(select count(*) from data.gps as dg where dg.tstamp<ttt1.tstamp and dg.tstamp>ttt1.tstampb and signal_restart=1);
select * from ttt1 order by tstamp;";

echo $sql;
  $arr=array();
  $res=pg_query($sql);
echo "psql error:".pg_last_error($res)."<br>";
  if (pg_num_rows($res)<2){
   $result = array('type'=>'error', 'regions'=> array('text'=>'нет данных за указанный период'));
    echo "0000000000000000000000";
  }
  else{
  while ($data_new=pg_fetch_array($res)){
    echo "11111111111111111";
    //старые данные уже есть, теперь функция принимания решения заносить ли новые данные в старые или из старых данных создать отрезок а новые начать обрабатывать
    //if func_create_new_point()==true {выводим старый отрезок в массив, создаём новый отрезок}
    // else {передаём новые и старые данные в функцию для слепливания и обновляем этим старые данные}
    if (!is_array($data)){$data=$data_new; echo "2222222222222222222<br>";}
    else{
	echo "333333333333333333333333<br>";
    if (report_general_dut_compare_for_split_data ($data,$data_new)){$data=report_general_dut_split_data($data,$data_new);}
    else {
        $tstamp_r=strtotime($data['tstamp'])-strtotime($data['tstampb']);
	if (($data['speed']<=5)and ($tstamp_r>=189)){
	/// это стоянка
	$arr[]= array(
	    'tstamp'=>$data['tstamp'],
	    'tstampb'=>$data['tstampb'],
	    'dest'=>$data['dest'],
	    'speed'=>$data['speed']
	);
	$data=$data_new;
	}//if stop
	}
    }
   }
  }
return $arr;
}

function report_general_dut_compare_for_split_data($data,$data_new){
 $ret=true;
 // определяем движение машины
 if (($data['speed']<=5)&&($data_new['speed']>5))$ret=false;//считаем что машина поехала
 if (($data['speed']>5)&&($data_new['speed']<=5))$ret=false;//считаем что машина остановилась

 return $ret;
}//function compare_for_split_data

function report_general_dut_split_data ($data,$data_new){
 $res=$data;
 $res['tstamp']=$data_new['tstamp'];
 $res['dest']=$data['dest']+$data_new['dest'];
 $res['speed']=round(($data['speed']+$data_new['speed'])/2);
 return $res;
}//function split_data

?>