<?php
class Reports{
	//Функция открытия подключения с БД
	function ConnectToPsql($db_host,$db,$db_user,$db_pass){
		$db_connection=pg_connect ("host=$db_host dbname=$db user=$db_user password=$db_pass");
		return $db_connection;
	}
	//Функция выборки данных из таблицы data.gps
	function SelectDataGps($tstamp_start,$tstamp_end,$carid){
		$sql="select tstamp, lat, lon, speed, dest, dut[1] as dut1, dut[2] as dut2, volt[1] as volt1, volt[2] as volt2 from data.gps where tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and carid=".$carid." and lat>0 and lon>0 order by tstamp;";
		return pg_fetch_all(pg_query($sql));
	}
	

//------------------------------------------------------------------------------------------------------------------------
	//Функция определения максимальной скорости, входные данные ассоциативный массив выборки из таблицы data.gps
	
	function GetSpeedDest($tstamp_start,$tstamp_end,$carid,$max,$avg,$dest){
		$sql="select ";
		if($max){
			if($avg){
				if($dest)$sql = $sql."MAX(speed) as speed_max, AVG(speed) as speed_avg, SUM(dest) as dest";
				else $sql = $sql."MAX(speed) as speed_max, AVG(speed) as speed_avg";
			}
			else if($dest)$sql = $sql."MAX(speed) as speed_max, SUM(dest) as dest";
			else $sql = $sql."MAX(speed) as speed_max";
			
		}
		else if($avg){
			if($dest)$sql = $sql."AVG(speed) as speed_avg, SUM(dest) as dest";
			else $sql = $sql."AVG(speed) as speed_AVG";
		}
		else if($dest)$sql = $sql."SUM(dest) as dest";
		
		$sql = $sql." from data.gps where tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and carid=".$carid." and lat>0 and lon>0 and speed>5;";
		$tmp = pg_fetch_array(pg_query($sql));
		//if(count($tmp)==1)
		return $tmp;
		//else return false;
	}
	
	//Функция определения стоянок более 4 минут
	function GetStops($tstamp_start,$tstamp_end,$carid){
		$start=0;
		$stop=0;
		$i=0;
		$check1=false;					//Флаг установки начала остановки
		$check2=false;					//Флаг установки конца остановки
		$sql="select tstamp,speed, lat, lon from data.gps where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 order by tstamp;";
		$res=pg_query($sql);
		while($tmp=pg_fetch_array($res)){
			extract ($tmp,EXTR_OVERWRITE);
			if($speed<=7&&$check1==false){		//В этом блоке устанавливаем метку начала остановки
				$start=$tstamp;
				$check1=true;
				$latitude = $lat;
				$longtitude = $lon;
			}
			if($speed<=7&&$check1==true){		//В этом блоке устанавливаем метку конца остановки
				$stop=$tstamp;
				$check2=true;
			}
			if($speed>7&&$check1==true&&$check2==true){			//В этом блоке записываем начало и конец остановки в выходной массив
				if(($result=strtotime($stop)-strtotime($start))>240){
					$arr[$i]['tstampb']=$start;
					$arr[$i]['tstamp']=$stop;
					$arr[$i]['lat']=$latitude;
					$arr[$i]['lon']=$longtitude;
					//$arr[$i]['t'] = ($result=strtotime($stop)-strtotime($start));
					$check1=false;
					$check2=false;
					//$check3=true;
					$i++;
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
			$arr[$i]['lat']=$latitude;
			$arr[$i]['lon']=$longtitude;
			//$arr[$i]['t'] = ($result=strtotime($stop)-strtotime($start));
		}
	return $arr;
	}

	function GetTimeMoveStop($tstamp_start,$tstamp_end,$carid){
		$sql = "select tstamp,speed from data.gps where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 order by tstamp;";
		$res = pg_query($sql);
		$out['move']=0;
		$out['stop']=0;
		$time_prev=false;
		while($tmp = pg_fetch_array($res)){
			if($time_prev){
				if($tmp['speed']<=5){
					$out['stop']+=strtotime($tmp['tstamp'])-$time_prev;
					$time_prev=strtotime($tmp['tstamp']);
				}
				else {
					$out['move']+=strtotime($tmp['tstamp'])-$time_prev;
					$time_prev=strtotime($tmp['tstamp']);
				}
			}
			else $time_prev=strtotime($tmp['tstamp']);
		}
	return $out;
	}
	
	//Функция определения мото часов
	function GetTimeOfMoto($tstamp_start,$tstamp_end,$carid){
		$sql = "select tstamp, volt[1] as volt1 from data.gps where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 and volt[1]>0 order by tstamp;";
		$sql1 = "select MIN(volt[1]) as volt1_min, MAX(volt[1]) as volt1_max from data.gps where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 and volt[1]>0 and speed>10;";
		//$sql2 = "select AVG(volt[1]) as volt1_mid from data.gps where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 and volt[1]>0 and speed=0;";
		$res = pg_query($sql);
		$tmp1 = pg_fetch_array( pg_query($sql1));
		//$tmp2 = pg_fetch_array( pg_query($sql2));
		$out=0;
		$time_prev=false;
		if($tmp1['volt1_min']&&$tmp1['volt1_max']){
			if($tmp1['volt1_max']-$tmp1['volt1_min']>50){
				while($tmp = pg_fetch_array($res)){
					if($time_prev){
						if($tmp['volt1']>($tmp1['volt1_min']-50)&&$tmp['volt1']<($tmp1['volt1_max']+100)){
							$out+=strtotime($tmp['tstamp'])-$time_prev;
							$time_prev=strtotime($tmp['tstamp']);
						}else $time_prev=strtotime($tmp['tstamp']);
					}else $time_prev=strtotime($tmp['tstamp']);
				}
			}
		}
	return $out;
	}
	
	//Функция конвертирования времени в строку
	function ConvertTimeToString(&$time){
		$h = round(floor($time/3600));
		$m = round(floor($time-$h*3600)/60);
		return ($h > 0 ? $m > 0 ? "$h&nbsp;ч.&nbsp;$m&nbsp;мин." : "$h&nbsp;ч." : "$m&nbsp;мин.");
	}
	
	function GetCarData($carid){
			$sql = "select gosnum,fiodriver1,name,dutmin[1] dut1_min,dutmax[1] dut1_max,dutlitr[1] dut1_litr,dutmin[2] dut2_min,dutmax[2] dut2_max,dutlitr[2] dut2_litr,deadzone[1] dead_zone1,deadzone[2] dead_zone2
        		from cars where carid=$carid;";
        	$res = pg_query($sql);
        	extract (pg_fetch_array($res),EXTR_OVERWRITE);
		if($dut1_min && $dut1_max && $dut1_litr){
			$out['tank1']=true;
			$out['em1']=($dut1_litr-$dead_zone1)/($dut1_max-$dut1_min);
			$out['dut1_min']=$dut1_min;
			$out['dead_zone1']=$dead_zone1;
		}else{
		     $out['tank1']=false;
		    $out['em1']=0;
		    }
		if($dut2_min && $dut2_max && $dut2_litr){
			$out['tank2']=true;
			$out['em2']=($dut2_litr-$dead_zone2)/($dut2_max-$dut2_min);					//Высчитываем единицу/литр
			$out['dut2_min']=$dut2_min;
			$out['dead_zone2']=$dead_zone2;
		}else {
		    $out['tank2']=false;
		    $out['em2']=0;
		    }
		$out['gosnum']=$gosnum;
		$out['name']=$name;
		$out['fio']=$fiodriver1;
	return $out;
	}
	
	function GetFuelSE($tstamp_start,$tstamp_end,$carid,$tank){
		$sql = "select
        		(select dut[1] from data.gps where carid=$carid and tstamp >= '$tstamp_start' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp limit 1) as dut1_start,
        		(select dut[1] from data.gps where carid=$carid and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp desc limit 1) as dut1_end,
        		(select dut[2] from data.gps where carid=$carid and tstamp >= '$tstamp_start' and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." order by tstamp limit 1) as dut2_start,
        		(select dut[2] from data.gps where carid=$carid and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." order by tstamp desc limit 1) as dut2_end
        		from cars where carid=$carid;";
	$tmp = pg_fetch_array(pg_query($sql));
	if($tmp['dut1_start']<$tank['dut1_min'])$tmp['dut1_start']=$tank['dut1_min'];
	if($tmp['dut1_end']<$tank['dut1_min'])$tmp['dut1_end']=$tank['dut1_min'];
	if($tmp['dut2_start']<$tank['dut2_min'])$tmp['dut2_start']=$tank['dut2_min'];
	if($tmp['dut2_end']<$tank['dut2_min'])$tmp['dut2_end']=$tank['dut2_min'];
	
	$out['dut_start'] = $tank['dead_zone1']+$tank['dead_zone2']+($tmp['dut1_start']-$tank['dut1_min'])*$tank['em1']+($tmp['dut2_start']-$tank['dut2_min'])*$tank['em2'];
	$out['dut_end'] = $tank['dead_zone1']+$tank['dead_zone2']+($tmp['dut1_end']-$tank['dut1_min'])*$tank['em1']+($tmp['dut2_end']-$tank['dut2_min'])*$tank['em2'];
	return $out;
	}
	
	function GetFuelUpDown($carid,$tank,$stops){
		$out = array();
		$x=0;
		$up=0;
		$down=0;
		$ch1=false;
		for($i=0;$i<count($stops);$i++){
				$ch2=false;
				//echo "Начали обработку = ".$i."\n";
				$sql1="select tstamp, dut[1]  as dut1
					from data.gps where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[1]>0 order by tstamp;";
				$sql2="select tstamp,  dut[2] as dut2
					from data.gps where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[2]>0 order by tstamp;";
				$tmp1 = pg_fetch_all( pg_query($sql1));
				$tmp2 = pg_fetch_all( pg_query($sql2));
				//print_r($tmp);
				//echo "Записали данные в массив\n";
				$count1=count($tmp1);
				$count2=count($tmp2);
				$num=0;
				if($tank['tank1']==true){
					//echo "начали Обработку ДУТ1\n";
					$y=0;
					while($y+1<$count1){
							if($tmp1[$y+1]['dut1']-$tmp1[$y]['dut1']>4){
								$t=0;
								while($tmp1[$y+1+$t]['dut1']-$tmp1[$y+$t]['dut1']>4){
									if(($y+1+$t+1)<$count1)$t++;
									else {
										$t++;
										break;
										
									}
								}
								if($tmp1[$y+$t]['dut1']-$tmp1[$y]['dut1']>5/$tank['em1']){
									$out[$x][$num]['dut1_res']=($tmp1[$y+$t]['dut1']-$tmp1[$y]['dut1'])*$tank['em1'];
									$out[$x][$num]['tstamp'] = $tmp1[$y]['tstamp'];
									$up++;
									$y+=$t;
									$num++;
									$ch2=true;
								}
								else $y+=$t;
							}
							else if(($tmp1[$y+1]['dut1']-$tmp1[$y]['dut1'])*-1>4&&$y+1<$count1){
								$t=0;
								while(($tmp1[$y+1+$t]['dut1']-$tmp1[$y+$t]['dut1'])*-1>4){
									if(($y+1+$t+1)<$count1)$t++;
									else{ 
										$t++;
										break;
									}
								}
								if(($tmp1[$y+$t]['dut1']-$tmp1[$y]['dut1'])*-1>5/$tank['em1']){
									$out[$x][$num]['dut1_res']=($tmp1[$y+$t]['dut1']-$tmp1[$y]['dut1'])*$tank['em1'];
									$out[$x][$num]['tstamp'] = $tmp1[$y]['tstamp'];
									$down++;
									$y+=$t;
									$num++;
									$ch2=true;
								}
								else $y+=$t;
							}
							else {
								$y++;
							}
					}
				//echo "Закончили Обработку ДУТ1\n";
				}
				if($tank['tank2']==true){
				//echo "начали Обработку ДУТ2\n";
					$y=0;
					while($y+1<$count2){
							if($tmp2[$y+1]['dut2']-$tmp2[$y]['dut2']>4){
								$t=0;
								while($tmp2[$y+1+$t]['dut2']-$tmp2[$y+$t]['dut2']>4){
									if(($y+1+$t+1)<$count2)$t++;
									else{
										$t++;
										break;
									}
								
								}
								if($tmp2[$y+$t]['dut2']-$tmp2[$y]['dut2']>5/$tank['em2']){
									$out[$x][$num]['dut2_res']=($tmp2[$y+$t]['dut2']-$tmp2[$y]['dut2'])*$tank['em2'];
									$out[$x][$num]['tstamp'] = $tmp2[$y]['tstamp'];
									$up++;
									$y+=$t;
									$num++;
									$ch2=true;
								}
								else $y+=$t;
							}
							else if(($tmp2[$y+1]['dut2']-$tmp2[$y]['dut2'])*-1>4){
								$t=0;
								while(($tmp2[$y+1+$t]['dut2']-$tmp2[$y+$t]['dut2'])*-1>4){
									if(($y+1+$t+1)<$count2)$t++;
									else{
										$t++;
										break;
									}
								}
								if(($tmp2[$y+$t]['dut2']-$tmp2[$y]['dut2'])*-1>5/$tank['em2']){
									$out[$x][$num]['dut2_res']=($tmp2[$y+$t]['dut2']-$tmp2[$y]['dut2'])*$tank['em2'];
									$out[$x][$num]['tstamp'] = $tmp2[$y]['tstamp'];
									$down++;
									$y+=$t;
									$num++;
									$ch2=true;
								}
								else $y+=$t;
							}
							
							else {
								$y++;
							}
					}
				//echo "Закончили Обработку ДУТ2\n";
				}
			//echo "END\n";
			if($ch2)$x++;
			
		}
		$out[$x]['count_up']=$up;
		$out[$x]['count_down']=$down;
	return $out;
	}
	
	function GetAllUpDown($fuel){
		$out['up']=0;
		$out['down']=0;
		$count = count($fuel);
		for($i=0;$i<$count-1;$i++){
			for($x=0;$x<count($fuel[$i]);$x++){
				$tmp = key($fuel[$i][$x]);
				if($fuel[$i][$x][$tmp]>0)$out['up']+=$fuel[$i][$x][$tmp];
				else $out['down']+=$fuel[$i][$x][$tmp];
			}
		}
	return $out;	
	}
	
}


?>
