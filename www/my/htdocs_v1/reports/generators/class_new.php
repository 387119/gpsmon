<?php
class Reports{
	//Функция открытия подключения с БД
	function ConnectToPsql($db_host,$db,$db_user,$db_pass){
		$db_connection=pg_connect ("host=$db_host dbname=$db user=$db_user password=$db_pass");
		return $db_connection;
	}

//------------------------------------------------------------------------------------------------------------------------
	//Функция выборки данных из таблицы gps_avg_test
	
	function GetAvgData($cars,$time){//Done
		$ch = false;
		$out = array();
		if($time['pavg']['tstamp_s']==false)return false;
		if($cars==false) return false;
		$sql = "select * from olap.gps_average_day where day>='".$time['pavg']['tstamp_s']."' and day<='".$time['pavg']['tstamp_e']."' and carid=".$cars." order by day;";
		//$sql = "select * gps_avg_test where day>='".$time['pavg']['tstamp_s']."' and day<='".$time['pavg']['tstamp_e']."' and carid=".$cars." order by day;";
			$out['speed_max'] = 0;
			$out['speed_sum']= 0;
			$out['dest']= 0;
			$out['fill_up_count']= 0;
			$out['fill_down_count']= 0;
			$out['fill_up_litr']= 0;
			$out['fill_down_litr']= 0;
			$out['time_move_seconds']= 0;
			$out['time_stop_seconds']= 0;
			$out['time_moto_seconds']= 0;
			$out['counts'] = 0;
			
		//echo $sql;
		$res = pg_query($sql);
		while($tmp = pg_fetch_array($res)){
				if($out['speed_max']<$tmp['speed_max'])$out['speed_max']=$tmp['speed_max'];
				$out['speed_sum']+=$tmp['speed_sum'];
				$out['dest']+=$tmp['dest'];
				$out['fill_up_count']+=$tmp['fill_up_count'];
				$out['fill_down_count']+=$tmp['fill_down_count'];
				$out['fill_up_litr']+=$tmp['fill_up_litr'];
				$out['fill_down_litr']+=$tmp['fill_down_litr'];
				$out['time_move_seconds']+=$tmp['time_move_seconds'];
				$out['time_stop_seconds']+=$tmp['time_stop_seconds'];
				$out['time_moto_seconds']+=$tmp['time_moto_seconds'];
				$out['counts']+=$tmp['counts'];
		}
	return $out;
	}
	
	
	//Функция определения максимальной скорости, входные данные ассоциативный массив выборки из таблицы data.gps
	
	function GetSpeedDest($tstamp_start,$tstamp_end,$carid,$max,$avg,$dest){//DONE
		$tmp=false;
		$avg=false;
		
		$date1 = date("Y-m-d",strtotime($tstamp_start));
		$date2 = date("Y-m-d",strtotime($tstamp_end));
		$time1 = date("H:i:s",strtotime($tstamp_start));
		$time2 = date("H:i:s",strtotime($tstamp_end));
		
		$time = $this->GetTime2($date1,$date2,$time1,$time2);
		$avg = $this->GetAvgData($carid,$time);
		$out = array();
		$sql="select MAX(speed) as speed_max, SUM(speed) as speed_sum, SUM(dest) as dest, count(*) as counts";
		if($time['pstart']['tstamp_s']&&$time['pend']['tstamp_s']==false){
			$sql = $sql." from gpsdatafull where tstamp>='".$time['pstart']['tstamp_s']."' and tstamp<='".$time['pstart']['tstamp_e']."' and carid=".$carid." and lat>0 and lon>0 and speed>5;";
		}
		else if($time['pstart']['tstamp_s']==false&&$time['pend']['tstamp_s']){
			$sql = $sql." from gpsdatafull where tstamp>='".$time['pend']['tstamp_s']."' and tstamp<='".$time['pend']['tstamp_e']."' and carid=".$carid." and lat>0 and lon>0 and speed>5;";
		}
		else if($time['pstart']['tstamp_s']&&$time['pend']['tstamp_s']){
			$sql = $sql." from gpsdatafull where tstamp>='".$time['pstart']['tstamp_s']."' and tstamp<='".$time['pstart']['tstamp_e']."' and carid=".$carid." and lat>0 and lon>0 and speed>5 or";
			$sql = $sql." tstamp>='".$time['pend']['tstamp_s']."' and tstamp<='".$time['pend']['tstamp_e']."' and carid=".$carid." and lat>0 and lon>0 and speed>5;";
		}
		else if ($time['pstart']['tstamp_s']==false&&$time['pend']['tstamp_s']==false)$sql=false;
		if($sql)$tmp = pg_fetch_array(pg_query($sql));
		
		if($tmp&&$avg){
			if($avg['speed_max']>$tmp['speed_max'])$out['speed_max'] = $avg['speed_max'];
			else $out['speed_max'] = $tmp['speed_max'];
			$out['speed_avg'] = ($avg['speed_sum']+$tmp['speed_sum'])/($avg['counts']+$tmp['counts']);
			$out['dest'] = $avg['dest']+$tmp['dest'];
		}
		else if(!$tmp&&$avg){
			$out['speed_max'] = $avg['speed_max'];
			$out['speed_avg'] = $avg['speed_sum']/$avg['counts'];
			$out['dest'] = $avg['dest'];
		}
		else if($tmp&&!$avg){
			$out['speed_max'] = $tmp['speed_max'];
			$out['speed_avg'] = $tmp['speed_sum']/$tmp['counts'];
			$out['dest'] = $tmp['dest'];
		}
		return $out;
	}
	
	
	//Функция определения стоянок более 4 минут
	function GetStops($tstamp_start,$tstamp_end,$carid){
		$start=0;
		$stop=0;
		$i=0;
		$check1=false;					//Флаг установки начала остановки
		$check2=false;					//Флаг установки конца остановки
		$sql="select tstamp,speed, lat, lon from gpsdatafull where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 order by tstamp;";
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
		$y=0;
		$out[$y]['tstampb'] = $arr[0]['tstampb'];
		$out[$y]['tstamp'] = $arr[0]['tstamp'];
		$out[$y]['lat'] = $arr[0]['lat'];
		$out[$y]['lon'] = $arr[0]['lon'];
		for($x=1;$x<count($arr);$x++){
			if(strtotime($arr[$x]['tstampb'])-strtotime($out[$y]['tstamp'])<180){
				$out[$y]['tstamp'] = $arr[$x]['tstamp'];
			}
			else{
				$y++;
				$out[$y]['tstampb'] = $arr[$x]['tstampb'];
				$out[$y]['tstamp'] = $arr[$x]['tstamp'];
				$out[$y]['lat'] = $arr[$x]['lat'];
				$out[$y]['lon'] = $arr[$x]['lon'];
			}
	    	}
	return $out;
	}

	function GetStops2($tstamp_start,$tstamp_end,$carid){//DONE
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
	

	function GetTimeMoveStop($tstamp_start,$tstamp_end,$carid){//DONE
		$avg=false;
		$out['move']=0;
		$out['stop']=0;
		$date1 = date("Y-m-d",strtotime($tstamp_start));
		$date2 = date("Y-m-d",strtotime($tstamp_end));
		$time1 = date("H:i:s",strtotime($tstamp_start));
		$time2 = date("H:i:s",strtotime($tstamp_end));
		$time = $this->GetTime2($date1,$date2,$time1,$time2);
		$avg = $this->GetAvgData($carid,$time);
		if($avg){
			$out['move'] += $avg['time_move_seconds'];
			$out['stop'] += $avg['time_stop_seconds'];
		}
		
		if($time['pstart']['tstamp_s']){
			$sql = "select tstamp,speed from gpsdatafull where carid=".$carid." and tstamp>='".$time['pstart']['tstamp_s']."' and tstamp<='".$time['pstart']['tstamp_e']."' and lat>0 and lon>0 order by tstamp;";
			$res = pg_query($sql);
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
			
		}
		if($time['pend']['tstamp_s']){
			$sql = "select tstamp,speed from data.gps where carid=".$carid." and tstamp>='".$time['pend']['tstamp_s']."' and tstamp<='".$time['pend']['tstamp_e']."' and lat>0 and lon>0 order by tstamp;";
			$res = pg_query($sql);
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
		}
	return $out;
	}
	
	//Функция определения мото часов
	function GetTimeOfMoto($tstamp_start,$tstamp_end,$carid){//DONE
		$date1 = date("Y-m-d",strtotime($tstamp_start));
		$date2 = date("Y-m-d",strtotime($tstamp_end));
		$time1 = date("H:i:s",strtotime($tstamp_start));
		$time2 = date("H:i:s",strtotime($tstamp_end));
		$time = $this->GetTime2($date1,$date2,$time1,$time2);
		$avg = $this->GetAvgData($carid,$time);
		$out=0;
		$time_prev=false;
		if($avg){
			$out += $avg['time_moto_seconds'];
		}
		if($time['pstart']['tstamp_s']){
			$sql = "select tstamp, volt[1] as volt1 from data.gps where carid=".$carid." and tstamp>='".$time['pstart']['tstamp_s']."' and tstamp<='".$time['pstart']['tstamp_e']."' and lat>0 and lon>0 and volt[1]>0 order by tstamp;";
			$sql1 = "select MIN(volt[1]) as volt1_min, MAX(volt[1]) as volt1_max from data.gps where carid=".$carid." and tstamp>='".$time['pstart']['tstamp_s']."' and tstamp<='".$time['pstart']['tstamp_e']."' and lat>0 and lon>0 and volt[1]>0 and speed>10;";
			$res = pg_query($sql);
			$tmp1 = pg_fetch_array( pg_query($sql1));
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
		}
		
		if($time['pend']['tstamp_s']){
			$sql = "select tstamp, volt[1] as volt1 from data.gps where carid=".$carid." and tstamp>='".$time['pend']['tstamp_s']."' and tstamp<='".$time['pend']['tstamp_e']."' and lat>0 and lon>0 and volt[1]>0 order by tstamp;";
			$sql1 = "select MIN(volt[1]) as volt1_min, MAX(volt[1]) as volt1_max from data.gps where carid=".$carid." and tstamp>='".$time['pend']['tstamp_s']."' and tstamp<='".$time['pend']['tstamp_e']."' and lat>0 and lon>0 and volt[1]>0 and speed>10;";
			$res = pg_query($sql);
			$tmp1 = pg_fetch_array( pg_query($sql1));
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
	
	function GetFuelSE($tstamp_start,$tstamp_end,$carid,$tank){//DONE
		if($tank['tank1']&&$tank['tank2']){
			$sql = "select first(d1) as dut1_start,first(d2) as dut2_start,last(d1) as dut1_end,last(d2)as dut2_end from (select dut[1] as d1,dut[2] as d2 from gpsdatafull
				where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and (dut[1]>".$tank['dut1_min']." or dut[2]>".$tank['dut2_min'].") order by tstamp) as x;";
			/*$sql = "select
				(select dut[1] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp limit 1) as dut1_start,
				(select dut[1] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp desc limit 1) as dut1_end,
				(select dut[2] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." order by tstamp limit 1) as dut2_start,
				(select dut[2] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." order by tstamp desc limit 1) as dut2_end
				from cars where carid=$carid;";
			*/
		}
		else if($tank['tank1']&&$tank['tank2']==false){
			$sql = "select first(d1) as dut1_start,last(d1) as dut1_end from (select dut[1] as d1,dut[2] as d2 from gpsdatafull
				where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp) as x;";
			/*$sql = "select
				(select dut[1] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp limit 1) as dut1_start,
				(select dut[1] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." order by tstamp desc limit 1) as dut1_end
				from cars where carid=$carid;";
			*/
		}
		else if($tank['tank1']==false&&$tank['tank2']){
			$sql = "select first(d2) as dut2_start,last(d2)as dut2_end from (select dut[1] as d1,dut[2] as d2 from gpsdatafull
				where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and  dut[2]>".$tank['dut2_min']." order by tstamp) as x;";
			/*$sql = "select
				(select dut[2] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." order by tstamp limit 1) as dut2_start,
				(select dut[2] from gpsdatafull where carid=$carid and tstamp >= '$tstamp_start' and tstamp <= '$tstamp_end' and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." order by tstamp desc limit 1) as dut2_end
				from cars where carid=$carid;";
			*/
		}
		$tmp = pg_fetch_array(pg_query($sql));
		if($tank['tank1']){
			if($tmp['dut1_start']<$tank['dut1_min'])$tmp['dut1_start']=$tank['dut1_min'];
			if($tmp['dut1_end']<$tank['dut1_min'])$tmp['dut1_end']=$tank['dut1_min'];
			$start1 =$tank['dead_zone1']+($tmp['dut1_start']-$tank['dut1_min'])*$tank['em1'];
			$end1 =  $tank['dead_zone1']+($tmp['dut1_end']-$tank['dut1_min'])*$tank['em1'];
		}
		else {
			$start1 = 0;
			$end1   = 0;
		}
		if($tank['tank2']){
			if($tmp['dut2_start']<$tank['dut2_min'])$tmp['dut2_start']=$tank['dut2_min'];
			if($tmp['dut2_end']<$tank['dut2_min'])$tmp['dut2_end']=$tank['dut2_min'];
			$start2 = $tank['dead_zone2']+($tmp['dut2_start']-$tank['dut2_min'])*$tank['em2'];
			$end2 =   $tank['dead_zone2']+($tmp['dut2_end']-$tank['dut2_min'])*$tank['em2'];
		}
		else {
			$start2 = 0;
			$end2   = 0;
		}
		$out['dut_start'] = $start1+$start2;
		$out['dut_end'] =   $end1 + $end2;
	return $out;
	}
	
	function GetFuelUpDown($tstamp_start,$tstamp_end,$carid,$tank){
		$date1 = date("Y-m-d",strtotime($tstamp_start));
		$date2 = date("Y-m-d",strtotime($tstamp_end));
		$time1 = date("H:i:s",strtotime($tstamp_start));
		$time2 = date("H:i:s",strtotime($tstamp_end));
		$time = $this->GetTime2($date1,$date2,$time1,$time2);
		$avg = $this->GetAvgData($carid,$time);
		//print_r($avg);
		$out = array();
		$x=0;
		$up=0;
		$down=0;
		if($avg){
			$num = 0;
			if($avg['fill_up_litr']){
				$out[$x][$num]['dut1_res'] = $avg['fill_up_litr'];
				$num++;
			}
			if($avg['fill_down_litr'])$out[$x][$num]['dut1_res'] = $avg['fill_down_litr'];
			if($avg['fill_up_litr']||$avg['fill_down_litr'])$x++;
		}
		
		if($time['pstart']['tstamp_s']){

			$ch1=false;
			$stops = $this->GetStops2($time['pstart']['tstamp_s'],$time['pstart']['tstamp_e'],$carid);
			for($i=0;$i<count($stops);$i++){

				$ch2=false;
				if($tank['tank1']==true){$sql1="select tstamp, dut[1]  as dut1
					from gpsdatafull where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." and speed<5 order by tstamp;";
					$tmp1 = pg_fetch_all(pg_query($sql1));
					$count1=count($tmp1);
				}
				if($tank['tank2']==true){$sql2="select tstamp,  dut[2] as dut2
					from gpsdatafull where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." and speed<5 order by tstamp;";
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
				}
			if($ch2)$x++;
			}
		}
		if($time['pend']['tstamp_s']){
			$ch1=false;
			$stops = $this->GetStops2($time['pend']['tstamp_s'],$time['pend']['tstamp_e'],$carid);
			for($i=0;$i<count($stops);$i++){
				$ch2=false;
				if($tank['tank1']==true){$sql1="select tstamp, dut[1]  as dut1
					from gpsdatafull where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." and speed<5 order by tstamp;";
					$tmp1 = pg_fetch_all(pg_query($sql1));
					$count1=count($tmp1);
				}
				if($tank['tank2']==true){$sql2="select tstamp,  dut[2] as dut2
					from gpsdatafull where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." and speed<5 order by tstamp;";
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
				}
			if($ch2)$x++;
			}
		}
		
		$out[$x]['count_up']=$up;
		$out[$x]['count_down']=$down;
		
		if($avg){
			if($avg['fill_up_count'])$out[$x]['count_up']+=$avg['fill_up_count'];
			if($avg['fill_down_count'])$out[$x]['count_down']+=$avg['fill_down_count'];
		}

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
	
	function GetTime($date1,$date2,$time1,$time2){
		$out = array();
		$i=0;
		if($date1==$date2){
			$out[$i]['tstamp_start'] = $date1." ".$time1;
			$out[$i]['tstamp_end']   = $date2." ".$time2;
		}
		else {
			while($date1!=$date2){	
				if($i==0){
					$out[$i]['tstamp_start'] = $date1." ".$time1;
					$out[$i]['tstamp_end'] = $date1." 23:59:59";
				}
				else {
					$out[$i]['tstamp_start'] = $date1." 00:00:00";
					$out[$i]['tstamp_end'] = $date1." 23:59:59";
				}
				$date1 = date("Y-m-d",(strtotime($date1)+90000));
				$i++;
			}
		$out[$i]['tstamp_start'] = $date1." 00:00:00";
		$out[$i]['tstamp_end'] = $date1." ".$time2;
		}
	return $out;
	}
	
	function GetTime2($date1,$date2,$time1,$time2){
		$out = array();
		$i=0;
		$avg_s = false;
		$avg_e = false;
		$start_s = false;
		$start_e = false;
		$end_s = false;
		$end_e = false;
		
		$ch = false;
		$today = date("Y-m-d");
		if(strtotime($date2.$time2)-strtotime($date1.$time1)<0){
			$out['pavg']['tstamp_s'] = false;
			$out['pavg']['tstamp_e'] = false;
			$out['pstart']['tstamp_s'] = false;
			$out['pstart']['tstamp_e'] = false;
			$out['pend']['tstamp_s'] = false;
			$out['pend']['tstamp_e'] = false;
			return $out;
		}
		if($date1==$date2){
			if($today==$date1){
				$out['pavg']['tstamp_s'] = false;
				$out['pavg']['tstamp_e'] = false;
				$out['pstart']['tstamp_s'] = false;
				$out['pstart']['tstamp_e'] = false;
				$out['pend']['tstamp_s'] = $date1." ".$time1;
				$out['pend']['tstamp_e'] = $date1." ".$time2;
			}
			else{
				if(strtotime($date1." ".$time2)-strtotime($date1." ".$time1)<86340){
					$out['pavg']['tstamp_s'] = false;
					$out['pavg']['tstamp_e'] = false;
					$out['pstart']['tstamp_s'] = false;
					$out['pstart']['tstamp_e'] = false;
					$out['pend']['tstamp_s'] = $date1." ".$time1;
					$out['pend']['tstamp_e'] = $date1." ".$time2;
				}
				else{
					$out['pavg']['tstamp_s'] = $date1;
					$out['pavg']['tstamp_e'] = $date1;
					$out['pstart']['tstamp_s'] = false;
					$out['pstart']['tstamp_e'] = false;
					$out['pend']['tstamp_s'] = false;
					$out['pend']['tstamp_e'] = false;
				}
			}
		}
		else{
			while($date1!=$date2){
				if($i==0){
					$start_s = $date1;
					$start_e = $date1;
					$i = 1;
				}
				else {
					if($ch==false){
						$avg_s = $date1;
						$avg_e = $date1;
						$ch=true;
					}
					else $avg_e = $date1;
				}
				$date1 = date("Y-m-d",(strtotime($date1)+90000));
			}
			$out['pavg']['tstamp_s'] = $avg_s;
			$out['pavg']['tstamp_e'] = $avg_e;
			if($start_s){
				if(strtotime($start_e." 23:59:59")-strtotime($start_s." ".$time1)<86340){
					$out['pstart']['tstamp_s'] = $start_s." ".$time1;
					$out['pstart']['tstamp_e'] = $start_e." 23:59:59";
				}
				else {
					if($out['pavg']['tstamp_s']&&$out['pavg']['tstamp_e']){
						$out['pavg']['tstamp_s'] = $start_s;
					}
					else{
						$out['pavg']['tstamp_s'] = $start_s;
						$out['pavg']['tstamp_e'] = $start_e;
					}
					$out['pstart']['tstamp_s'] = false;
					$out['pstart']['tstamp_e'] = false;
				}
			}
			else{
				$out['pstart']['tstamp_s'] = $start_s;
				$out['pstart']['tstamp_e'] = $start_e;
			}
			if($today==$date1){
				$out['pend']['tstamp_s'] = $date1." 00:00:00";
				$out['pend']['tstamp_e'] = $date1." ".$time2;
			}
			else{
				if(strtotime($date1." ".$time2)-strtotime($date1." 00:00:00")<86340){
					$out['pend']['tstamp_s'] = $date1." 00:00:00";
					$out['pend']['tstamp_e'] = $date1." ".$time2;
				}
				else{
					if($out['pavg']['tstamp_e']&&$out['pavg']['tstamp_e']){
						$out['pavg']['tstamp_e'] = $date1;
					}
					else{
						$out['pavg']['tstamp_s'] = $date1;
						$out['pavg']['tstamp_e'] = $date1;
					}
					$out['pend']['tstamp_s'] = false;
					$out['pend']['tstamp_e'] = false;
				}
			}
		}
	return $out;
	}
	
	
}
?>
