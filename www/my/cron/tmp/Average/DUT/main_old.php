<?php
	$db_connection=ConnectToPsql('db1','gps','tgps','tgpspwd');
	$sql1 ="select carid from cars order by carid;";
	$res = pg_query($sql1);
	$days = GetTime2('2013-12-25','2014-02-04');
	
	while($cars = pg_fetch_array($res)){
		$set = GetCarData($cars['carid']);
		if($set['tank1']==false&&$set['tank2']==false){
			//for($i=0;$i<sizeof($days);$i++){
				//if($days[$i]['day']!="2013-11-27")continue;
			//	$sql = "UPDATE gps_avg_test set fill_up_count=NULL, fill_down_count=NULL, fill_up_litr=NULL, fill_down_litr=NULL where day='".$days[$i]['day']."' and carid=".$cars['carid'].";";
			//	pg_query($sql);
			//	echo $sql."\n";
			//}
		}
		
		else{
			for($i=0;$i<sizeof($days);$i++){
				//if($days[$i]['day']!="2013-11-27")continue;
				echo "Записываем машину ".$cars['carid']." ".$days[$i]['day']."\n";
				$stop = GetStops2($days[$i]['day'],$cars['carid']);
				if($stop[0]['tstampb']==true&&$stop[0]['tstamp']==true){
					$fuel = GetFuelUpDown($cars['carid'],$set,$stop);
					$fuel_all = GetAllUpDown($fuel);
					$sql = "UPDATE gps_avg_test set fill_up_count=".$fuel[sizeof($fuel)-1]['count_up']." ,fill_down_count=".$fuel[sizeof($fuel)-1]['count_down'].",fill_up_litr=".$fuel_all['up'].", fill_down_litr=".$fuel_all['down']." where day='".$days[$i]['day']."' and carid=".$cars['carid'].";";
					pg_query($sql);
					//echo $sql."\n";
				}
			}
		}
		//if($cars['carid']==165)die();
	}

?>

<?php
	function ConnectToPsql($host,$db,$user,$pass){
		$conn = pg_connect("host=$host dbname=$db user=$user password=$pass");
		return $conn;
	}
	
	//Функция определения стоянок более 4 минут
	function GetStops($day,$carid){
		$tstamp_start = $day." 00:00:00";
		$tstamp_end = $day." 23:59:59";
		
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

	function GetStops2($day,$carid){
		$tstamp_start = $day." 00:00:00";
		$tstamp_end = $day." 23:59:59";
		
		$out = array();$out1 = array();
		$begin=false;					//Флаг установки начала остановки
		$end=false;					//Флаг установки конца остановки
		$x = 0;
		$start = 0;$stop = 0;
		$sql="select tstamp,speed, lat, lon from data.gps_arh where carid=".$carid." and tstamp>='".$tstamp_start."' and tstamp<='".$tstamp_end."' and lat>0 and lon>0 order by tstamp;";
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
	
	function GetFuelUpDown($carid,$tank,$stops){
		$out = array();
		$x=0;
		$up=0;
		$down=0;
		$ch1=false;
		for($i=0;$i<count($stops);$i++){
				$ch2=false;
				//echo "Начали обработку = ".$i."\n";
				if($tank['tank1']==true){$sql1="select tstamp, dut[1]  as dut1
					from data.gps_arh where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[1]>".$tank['dut1_min']." and speed<5 order by tstamp;";
				}
				if($tank['tank2']==true){$sql2="select tstamp,  dut[2] as dut2
					from data.gps_arh where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[2]>".$tank['dut2_min']." and speed<5 order by tstamp;";
				}
				if($tank['tank1']==true){
					$tmp1 = pg_fetch_all(pg_query($sql1));
					$count1=count($tmp1);
				}
				if($tank['tank2']==true){
					$tmp2 = pg_fetch_all(pg_query($sql2));
					$count2=count($tmp2);
				}
				//print_r($tmp);
				//echo "Записали данные в массив\n";
				
				
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
		//print_r($out);
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
	
	
	function GetFuelUpDown2($carid,$tank,$stops){
		$out = array();
		$x=0;
		$up=0;
		$down=0;
		for($i=0;$i<count($stops);$i++){
			$sql1="select tstamp, dut[1]  as dut1
				from data.gps where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[1]>0 and speed<5 order by tstamp;";
			$sql2="select tstamp,  dut[2] as dut2
				from data.gps where tstamp>='".$stops[$i]['tstampb']."'::timestamp with time zone + interval 'minute 1' and tstamp<='".$stops[$i]['tstamp']."'::timestamp with time zone  and carid=".$carid." and lat>0 and lon>0 and dut[2]>0 and speed<5 order by tstamp;";
			if($tank['tank1']==true){
				$tmp1 = pg_fetch_all( pg_query($sql1));
				$count1=count($tmp1);
			}
			if($tank['tank2']==true){
				$tmp2 = pg_fetch_all( pg_query($sql2));
				$count2=count($tmp2);
			}	
			$point = 0;
			$g =0;
			for($l=0;$l<$count2-1;$l++){
				if($l==0)echo $tmp2[$l]['dut2']." ".$tmp2[$l]['tstamp']."\n";
				if($tmp2[$l+1]['dut2']-$tmp2[$l]['dut2']<=2&&$tmp2[$l+1]['dut2']-$tmp2[$l]['dut2']>=-2){
					$point = $tmp2[$l]['dut2'];
					$g++;
				}
				else {
					if($g>0){
						$point = $tmp2[$l]['dut2'];
						echo $point." ".$tmp2[$l]['tstamp']."\n";
						$g=0;
					}
				}
			}
			//if($g>0){
				echo $tmp2[$l]['dut2']." ".$tmp2[$l]['tstamp']."\n";
				$g=0;
			//}
			
			echo "-----------------------\n";
		}
	}

?>
