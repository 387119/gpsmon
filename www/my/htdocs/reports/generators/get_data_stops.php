function get_data_stops($carid,$tfrom,$tto){
$start=0;
$stop=0;
$i=0;
$check1=false;					//Флаг установки начала остановки
$check2=false;					//Флаг установки конца остановки
//$check3=false;					//Флаг следующей остановки
$sql="select tstamp,speed from data.gps where carid=".$carid." and tstamp>='".$tfrom."' and tstamp<='".$tto."' and lat>0 and lon>0;";
//echo $sql;
$res=pg_query($sql);
while($tmp=pg_fetch_array($res)){
	extract ($tmp,EXTR_OVERWRITE);
	if($speed<=7&&$check1==false){		//В этом блоке устанавливаем метку начала остановки
		$start=$tstamp;
		$check1=true;
		//$check3=true;
	}
	if($speed<=7&&$check1==true){		//В этом блоке устанавливаем метку конца остановки
		$stop=$tstamp;
		$check2=true;
	}
	if($speed>7&&$check1==true&&$check2==true){			//В этом блоке записываем начало и конец остановки в выходной массив
		if(($result=strtotime($stop)-strtotime($start))>240){
			$arr[$i]['tstampb']=$start;
			$arr[$i]['tstamp']=$stop;
			$check1=false;
			$check2=false;
			//$check3=true;
			$i++;
			echo $start." ".$stop."<br>";
		}
		else {
			$check1=false;
			$check2=false;
		}
	}
	else if($speed>7&&($check1==false||$check2==false)) {		//В этом Блоке если скорость >7 но метки начало или конца остановки еще нет
		$check1=false;						//то флаги установок сбросить в false
		$check2=false;
	}
}
if($check1==true&&$check2==true){
	$arr[$i]['tstampb']=$start;
	$arr[$i]['tstamp']=$stop;
	echo $start." ".$stop."<br>";
}
return $arr;
}
