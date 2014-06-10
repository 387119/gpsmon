<?php
	$db_conn=ConnectToPsql('db','gpsmon','gpsmon','wwUxphhPMEXHXg1H');
# default period for 1 day
	$tstamp_end="date_trunc('day',CURRENT_TIMESTAMP)";
#	$tstamp_begin="date_trunc('day',CURRENT_TIMESTAMP) - interval '1 day'"; # начинаем с предыдущего дня
	$tstamp_begin="(select max(day) from olap.gps_average_day) + interval '1 day'"; # начинаем с первого дня за который нет данных
#	$tstamp_begin="'1970-01-01 0:0:0'"; # если база чистая надо начать с самого начала, указываем явную дату
	$sql = "insert into olap.gps_average_day (carid,day,speed_max,dest,speed_sum,counts) (select carid,date_trunc('day',tstamp)::date as day,max(speed),sum(dest),sum(speed),count(*) from gpsdatafull where speed>5 and lat>0 and lon>0 and tstamp < $tstamp_end and tstamp >= $tstamp_begin group by day,carid) returning carid,day;";
	$res = pg_query($sql);
	if($res==false)die();
	$tmp = pg_fetch_all($res);
	$table1		="olap.gps_average_day";
	$group1		=1;
	//Параметр $tmp во всех 3 функциях это результат запроса $sql(массив из carid и day),$table1 таблица в базе данных, Параметр $group1 пока работает только с значением равным 1
	GetTimeMoveStop($db_conn,$table1,$tmp);							//Функция записывает  время движения и простоя
	GetTimeOfMoto($db_conn,$table1,$tmp);							//Функция записывает  Моточасы
	GetFuelUpDown($db_conn,$table1,$tmp,$group1);						//Функция записывает  заправки и сливы, их колличество,
	
?>

<?php
	function ConnectToPsql($host,$db,$user,$pass){
		$conn = pg_connect("host=$host dbname=$db user=$user password=$pass");
		return $conn;
	}
	
	//Функция определения стоянок
	function GetStops2($day,$carid){
		$tstamp_start = $day." 00:00:00";
		$tstamp_end = $day." 23:59:59";
		
		$out = array();$out1 = array();
		$begin=false;					//Флаг установки начала остановки
		$end=false;					//Флаг установки конца остановки
		$x = 0;
		$start = 0;$stop = 0;
		$sql="select tstamp,speed, lat, lon from gpsdatafull where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 order by tstamp;";
		$tmp = pg_fetch_all(pg_query($sql));
		//Получаем массив из стоянок
		$count = count($tmp);
		for($i=0;$i<$count;$i++){
			if($tmp[$i]['speed']<=5&&$begin==true){
				$stop = $tmp[$i]['tstamp'];
			}
			else if($tmp[$i]['speed']<=5&&$begin==false){ //Началась остановка
				$start = $tmp[$i]['tstamp'];
				$stop = $tmp[$i]['tstamp'];
				$begin = true;
			}
			else if($tmp[$i]['speed']>5&&$begin==true){
				if(strtotime($stop)-strtotime($start)>60){
					$out1[$x]['tstampb'] = $start;
					$out1[$x]['tstamp'] = $stop;
					$x++;
					$begin = false;
				}
				else{
					$begin = false;
				}
			}
		}
		if($begin==true){
			$out1[$x]['tstampb']=$start;
			$out1[$x]['tstamp']=$stop;
			//$out1[$x]['lat']=$latitude;
			//$out1[$x]['lon']=$longtitude;
		}
		//print_r($out1);
		//Обьеденяем ложные стоянки и стоянки где разница времени между ними меньше 120 секунд
		$count1 = count($out);
		$y = 0;
		$out[$y]['tstampb'] = $out1[0]['tstampb'];
		$out[$y]['tstamp'] = $out1[0]['tstamp'];
		//$out[$y]['lat'] = $out1[0]['lat'];
		//$out[$y]['lon'] = $out1[0]['lon'];
		for($i=1;$i<count($out1);$i++){
			if(strtotime($out1[$i]['tstampb'])-strtotime($out[$y]['tstamp'])<=120){
				$out[$y]['tstamp'] = $out1[$i]['tstamp'];
			}
			else{
				if(strtotime($out[$y]['tstamp'])-strtotime($out[$y]['tstampb'])>300)$y++;
				$out[$y]['tstampb'] = $out1[$i]['tstampb'];
				$out[$y]['tstamp'] = $out1[$i]['tstamp'];
				//$out[$y]['lat'] = $arr[$x]['lat'];
				//$out[$y]['lon'] = $arr[$x]['lon'];
			}
		}
	return $out;
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
			$out['dut1_min']=false;
			$out['dead_zone1']=0;
		}
		if($dut2_min && $dut2_max && $dut2_litr){
			$out['tank2']=true;
			$out['em2']=($dut2_litr-$dead_zone2)/($dut2_max-$dut2_min);					//Высчитываем единицу/литр
			$out['dut2_min']=$dut2_min;
			$out['dead_zone2']=$dead_zone2;
		}else {
		    $out['tank2']=false;
		    $out['em2']=0;
		    $out['dut2_min']=false;
		    $out['dead_zone2']=0;
		    }
		$out['gosnum']=$gosnum;
		$out['name']=$name;
		$out['fio']=$fiodriver1;
	return $out;
	}
	
	function GetFuelUpDown($conn,$table,$cars,$group){
		if($group!=1){
			echo "Пятый параметр не может принемать значение не равным 1";
			die();
		}
		$sql_data = "";
		$sql_cars	="select carid from cars order by carid;";				
		//$data_cars	=pg_fetch_all(pg_query($sql_cars));					//Выбираем с базы id всех машин
		//$days		=GetTime2($start,$stop);						//Разбиваем период по дням
		for($i1=0;$i1<sizeof($cars);$i1++){
			$day = 1;
			$up=0;
			$down=0;
			$fuel_up = 0;
			$fuel_down = 0;
			$ch1=false;
			$tank = GetCarData($cars[$i1]['carid']);					//Выбираем данные по баку по каждой машине
			if($tank['tank1']==false&&$tank['tank2']==false)continue;				//Если банки не установлены, переходим к следующей машине
			//echo "Обрабатываем ".$cars[$i1]['carid']."\n";
			
			//for($y1=0;$y1<sizeof($days);$y1++){
				$stops		=GetStops2($cars[$i1]['day'],$cars[$i1]['carid']);	//Расчитываем стоянки
				for($i=0;$i<count($stops);$i++){
					if($stops[$i]['tstampb']==false||$stops[$i]['tstamp']==false)continue;
					$ch2=false;
					if($tank['tank1']==true){$sql1="select tstamp, dut[1]  as dut1
						from gpsdatafull where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$cars[$i1]['carid']." and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." and speed<5 order by tstamp;";
					}
					if($tank['tank2']==true){$sql2="select tstamp,  dut[2] as dut2
						from gpsdatafull where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$cars[$i1]['carid']." and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." and speed<5 order by tstamp;";
					}
					if($tank['tank1']==true){
						$tmp1 = pg_fetch_all(pg_query($sql1));
						$count1=count($tmp1);
					}
					if($tank['tank2']==true){
						$tmp2 = pg_fetch_all(pg_query($sql2));
						$count2=count($tmp2);
					}
					
					$num=0;
					if($tank['tank1']==true){
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
										$fuel_up +=($tmp1[$y+$t]['dut1']-$tmp1[$y]['dut1'])*$tank['em1'];
										$up++;
										$y+=$t;
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
										$fuel_down +=($tmp1[$y+$t]['dut1']-$tmp1[$y]['dut1'])*$tank['em1'];
										$down++;
										$y+=$t;
										$ch2=true;
									}
									else $y+=$t;
								}
								else {
									$y++;
								}
						}
					}
					if($tank['tank2']==true){
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
										$fuel_up +=($tmp2[$y+$t]['dut2']-$tmp2[$y]['dut2'])*$tank['em2'];
										$up++;
										$y+=$t;
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
										$fuel_down +=($tmp2[$y+$t]['dut2']-$tmp2[$y]['dut2'])*$tank['em2'];
										$down++;
										$y+=$t;
										$ch2=true;
									}
									else $y+=$t;
								}
								
								else {
									$y++;
								}
						}
					}
				}
				if($day==$group){
					$sql_data="UPDATE ".$table." set fill_up_count=".$up.",fill_down_count=".$down.", fill_up_litr=".$fuel_up.", fill_down_litr=".$fuel_down." where carid=".$cars[$i1]['carid']." and day='".$cars[$i1]['day']."';";
					pg_query($sql_data);
					$day=1;
					$fuel_up=0;
					$fuel_down=0;
					$up=0;
					$down=0;
					$sql_data="";
				}
				else $day++;
			//}
		}
	
	}
	
	function GetTime2($date1,$date2){
		$out = array();
		$i=0;
		if($date1==$date2){
			$out[$i]['day'] = $date1;
		}
		else {
			while($date1!=$date2){	
				$out[$i]['day'] = $date1;
				$date1 = date("Y-m-d",(strtotime($date1)+90000));
				$i++;
			}
		$out[$i]['day'] = $date1;
		}
	return $out;
	}
	
	function GetTimeMoveStop($conn,$table,$cars){
		for($i=0;$i<sizeof($cars);$i++){
			$tstamp_start	=$cars[$i]['day']." 00:00:00";
			$tstamp_end	=$cars[$i]['day']." 23:59:59";
			$sql = "select tstamp,speed from gpsdatafull where carid=".$cars[$i]['carid']." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 order by tstamp;";
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
			$sql = "UPDATE ".$table." set time_move_seconds=".$out['move'].", time_stop_seconds=".$out['stop']." where day='".$cars[$i]['day']."' and carid=".$cars[$i]['carid'].";";
			pg_query($sql);
		}
	}
	
	
	function GetTimeOfMoto($conn,$table,$cars){
		for($i=0;$i<sizeof($cars);$i++){
			$tstamp_start	=$cars[$i]['day']." 00:00:00";
			$tstamp_end	=$cars[$i]['day']." 23:59:59";
			$sql = "select tstamp, volt[1] as volt1 from data.gps where carid=".$cars[$i]['carid']." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 and volt[1]>0 order by tstamp;";
			$sql1 = "select MIN(volt[1]) as volt1_min, MAX(volt[1]) as volt1_max from data.gps where carid=".$cars[$i]['carid']." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 and volt[1]>0 and speed>10;";
			$res = pg_query($sql);
			$tmp1 = pg_fetch_array( pg_query($sql1));
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
			$sql = "UPDATE ".$table." set time_moto_seconds=".$out." where day='".$cars[$i]['day']."' and carid=".$cars[$i]['carid'].";";
			pg_query($sql);
		}
	}
	
?>
