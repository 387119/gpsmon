<head>
    <style type=text/css>
	body{	
		margin:0;
		padding:0;
		}
	#general{
		margin:0;
		padding:0;
		width:100%;
		height:400px;
		background-color:ffffff;
		}
	#title{
		padding:10px 0 10px 0;
		margin: 0 auto;
		width:22em;
		text-align:center;
		font-size:2.4em;
		font-weight:bold;
		
		}
	#box1{
		padding:0 0 0 20px;
		margin:0 auto;
		width:560px;
		}
	#box2{
		margin:0 auto;
		width:1300px;
		}
	#box3{
		margin:0 auto;
		width:1300px;
		}
	#box4{
		margin:0 auto;
		width:1300px;
		background-color:aaaaaa;
		border:1px solid;
		}
	#box1 span{
		margin:0;
		font-size:14px;
		font-weight:bold;
		}
	#box2 span{
		margin:0 auto;
		font-size:14px;
		font-weight:bold;
		}
	#box3 span{
		margin:0 auto;
		font-size:14px;
		//float:left;
		font-weight:bold;
		}
	#box4 th{
		//height:350px;
		//border:1px solid;
		background-color:aaaaaa;
		}
	.data{
		margin: 0 5px 0 5px;
		border-bottom:1px solid;
		font-size:18;
		text-align:center;
		width:200px;
		}
	.data1{
		text-align:center;
		font-size:18;
		border-bottom:1px solid;
		width:100px;
		}
	.data2{
		text-align:center;
		font-size:18;
		border-bottom:1px solid;
		width:100px;
		}
	.data3{
		height:200px;
		border:1px solid;
		background-color:cccccc;
		vertical-align:bottom;
		}
	.data4{
		border:1px solid;
		background-color:ffffff;
		text-align:center;
		height:1.3em;
		}
	.rotate{
		-ms-transform:rotate(270deg); /* IE 9 */
		-moz-transform:rotate(270deg); /* Firefox */
		-webkit-transform:rotate(270deg); /* Safari and Chrome */
		-o-transform:rotate(270deg); /* Opera */
		}
	
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <script src="/jquery/js/jquery.js" type="text/javascript"></script>
    <script type="text/javascript" src="/js/flot/jquery.flot.min.js"></script>
</head>
<body style="font: 9pt Tahoma">
    <?

        $datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
        $dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
        $timefrom = $_REQUEST['timefrom'];
        $timeto = $_REQUEST['timeto'];
	$tstimefrom = $datefrom ." ". $timefrom;
	$tstimeto = $dateto ." ". $timeto;
	
	$count=0;
	$all_probeg=0;
	$all_sliv=0;
	$all_zapravka=0;
        $all_rasxod=0;
        foreach ($_REQUEST['cars'] as $car => $value) {
        	//echo "--------------start-----------------<br>";
        	//echo "$car<br>";
        	$sql = "select gosnum,fiodriver1,name,dutmin[1] dut1_min,dutmax[1] dut1_max,dutlitr[1] dut1_litr,dutmin[2] dut2_min,dutmax[2] dut2_max,dutlitr[2] dut2_litr,
        		(select dut[1] from data.gps where carid=$car and tstamp >= '$tstimefrom' order by tstamp limit 1) as dut1_start,
        		(select dut[1] from data.gps where carid=$car and tstamp <= '$tstimeto' order by tstamp desc limit 1) as dut1_end,
        		(select dut[2] from data.gps where carid=$car and tstamp >= '$tstimefrom' order by tstamp limit 1) as dut2_start,
        		(select dut[2] from data.gps where carid=$car and tstamp <= '$tstimeto' order by tstamp desc limit 1) as dut2_end
        		from cars where carid=$car;";
        	$res = pg_query($sql);
        	extract (pg_fetch_array($res),EXTR_OVERWRITE);
		if($dut1_min && $dut1_max && $dut1_litr){
			$tank1_installed=true;
			$em=$dut1_litr/($dut1_max-$dut1_min);
		}else $tank1_installed=false;
		
		if($dut2_min && $dut2_max && $dut2_litr){
			$tank2_installed=true;
			$em=$dut2_litr/($dut2_max-$dut2_min);					//Высчитываем единицу/литр
		}else $tank2_installed=false;
		if(!$tank1_installed && !$tank2_installed)continue;				//если оба бака не установлены то начинаем заного
		$count_z[$count]=0;
        	$count_s[$count]=0;
        	$sum_rasxod[$count]=0;
		$sum_probeg = get_general_probeg($car,$tstimefrom,$tstimeto);
		$all_probeg+=$sum_probeg;
		
		$x_down=round(1/$em);								//Значение частоты при сливе
		$x_up=round(5/$em);								//Значение частоты при заправке
		$fio[$count]=$fiodriver1;
		$goss[$count]=$name;
		
		//echo "$x_up $x_down <br>";
		
		$dut_start_all[$count]=0;
		if ($dut1_start>0)$dut_start_all[$count]+=($dut1_start-$dut1_min)*$em;
		if ($dut2_start>0)$dut_start_all[$count]+=($dut2_start-$dut2_min)*$em;
		$dut_end_all[$count]=0;
		if ($dut1_end>0)$dut_end_all[$count]+=($dut1_end-$dut1_min)*$em;
		if ($dut2_end>0)$dut_end_all[$count]+=($dut2_end-$dut2_min)*$em;

		
		$start_stop = report_general_dut_get_stops ($car,$tstimefrom,$tstimeto);
//print_r($start_stop);

			$sum_litr_zapravka[$count]=0;
			$sum_litr_sliv[$count]=0;
		
		while (list($k,$mm)=each($start_stop)){
		unset($res1);
		$sql="select tstamp as tstamp_min,dut[1] as dut1_min,dut[2] as dut2_min into temporary dut_min from data.gps where carid=$car and lat>0 and lon>0 and tstamp >= '".$mm['tstampb']."'::timestamp with time zone + interval '1 minute' order by tstamp limit 1;
		       select tstamp as tstamp_max,dut[1] as dut1_max,dut[2] as dut2_max into temporary dut_max from data.gps where carid=$car and lat>0 and lon>0 and tstamp <= '".$mm['tstamp']."'::timestamp with time zone - interval '1 minute' order by tstamp desc limit 1;
			select tstamp_min,tstamp_max,case when tstamp_max>=tstamp_min then dut1_max-dut1_min else dut1_min-dut1_max end as dut1_res,case when tstamp_max>=tstamp_min then dut2_max-dut2_min else dut2_min-dut2_max end as dut2_res
			   from dut_min,dut_max;";
//echo $sql;
		$res1=pg_query($sql);
//echo "psql error:".pg_last_error($res1)."<br>";
		if (pg_num_rows($res1)==1){$dut1_res=pg_result($res1,0,"dut1_res");$dut2_res=pg_result($res1,0,"dut2_res");}
		else {$dut1_res=0;$dut2_res=0;}
//echo pg_result($res1,0,"tstamp_min")." ".pg_result($res1,0,"tstamp_max")." $dut1_res $dut2_res ".pg_last_error($res1)."<br>";
		pg_query ("drop table dut_min;drop table dut_max;");
		
		if($dut1_res>$x_up||$dut2_res>$x_up){
			//Заправка
			if ($dut1_res>=$x_up)$sum_lirt_zapravka[$count]+=$dut1_res*$em;
			if ($dut2_res>=$x_up)$sum_lirt_zapravka[$count]+=$dut2_res*$em;
			$count_z[$count]++;
			}
		if($dut1_res*-1>$x_down||$dut2_res*-1>$x_down){
			//Слив
			if ($dut1_res*-1>$x_down)$sum_lirt_sliv[$count]+=abs($dut1_res*$em);
			if ($dut2_res*-1>$x_down)$sum_lirt_sliv[$count]+=abs($dut2_res*$em);
			$count_s[$count]++;
			}
		
//		 $mm['tstamp']
//		 $mm['tstampb']
		//echo $count;
		}
$sum_rasxod[$count] = ($dut_start_all[$count] + $sum_lirt_zapravka[$count]) - ($dut_end_all[$count] + $sum_lirt_sliv[$count]);
$all_rasxod += $sum_rasxod[$count];
$all_zapravka += $sum_lirt_zapravka[$count] ;
$all_sliv += $sum_lirt_sliv[$count];
$count++;

//echo "<br>------------end---------------<br>";
}		
			
			echo "<div id=\"general\">
				<div id=\"title\">Общий отчет по расходу топлива(ДУТ)</div>
				<div id=\"box1\">
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td><span >Период с</span></td>
							<td><div  class=\"data\">$datefrom $timefrom</div></td>
							<td><span >по</span></td>
							<td><div  class=\"data\">$dateto $timeto</div></td>
						</tr>
					</table>
				</div>
				<div id=\"box2\">
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td width=200 align=\"right\"><span>Общий пробег</span></td>
							<td><div class=\"data1\" >".$all_probeg."</div></td>
							<td width=80><span>км</span></td>
							<td width=150 align=\"right\"><span>Всего заправлено</span></td>
							<td><div class=\"data1\" \">".round($all_zapravka,3)."</div></td>
							<td width=80><span>л</span></td>
							<td width=350 align=\"right\"><span>Средний расход топлива с учетом стоянок</span></td>
							<td><div class=\"data1\" \">111</div></td>
							<td><span>л/100км</span></td>
						</tr>
					</table>
				</div>
				<div id=\"box3\">
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td width=200 align=\"right\"><span>Общий расход топлива</span></td>
							<td><div class=\"data2\">".round($all_rasxod,3)."</div></td>
							<td width=80><span>л</span></td>
							<td width=150 align=\"right\"><span>Всего слито</span></td>
							<td><div class=\"data2\">".round($all_sliv,3)."</div></td>
							<td width=80><span>л</span></td>
							<td width=350 align=\"right\"><span>Средний расход топлива на моточас</span></td>
							<td><div class=\"data2\">111</div></td>
							<td><span>л/100км</span></td>
						</tr>
					</table>
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td width=200 align=\"right\"></td>
							<td width=100></td>
							<td width=80></td>
							<td width=150 align=\"right\"></td>
							<td width=100></td>
							<td width=80></td>
							<td width=350 align=\"right\"><span></span></td>
							<td><div></div></td>
							<td><span></span></td>
						</tr>
					</table>
				</div>
				<div id=\"box4\">
					<table>
						<tr>
							<th width=250 align=\"center\"><div class=\"data3\"><p style=\"margin:80px 0 0 0;\">Название транспортного средства</p></div></th>
							<th width=200><div class=\"data3\"><p style=\"margin:80px 0 0 0;\">ФИО водителя</div></p></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Пробег, км</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:70px 0 0 0;\">Топлива в начале периода, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:70px 0 0 0;\">Топлива в конце периода, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Кол-во заправок</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Кол-во сливов</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Всего заправлено, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Всего слито, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Общий расход, л</p></div></th>
						</tr>";
			for($i=0;$i<$count;$i++){
				echo "<tr>
					<td width=250><div class=\"data4\">".$goss[$i]."</div></td>
					<td width=200><div class=\"data4\">".$fio[$i]."</div></td>
					<td width=100><div class=\"data4\">".$sum_probeg."</div></td>
					<td width=100><div class=\"data4\">".round($dut_start_all[$i],3)."</div></td>
					<td width=100><div class=\"data4\">".round($dut_end_all[$i],3)."</div></td>
					<td width=100><div class=\"data4\">$count_z[$i]</div></td>
					<td width=100><div class=\"data4\">$count_s[$i]</div></td>
					<td width=100><div class=\"data4\">".round($sum_lirt_zapravka[$i],3)."</div></td>
					<td width=100><div class=\"data4\">".round($sum_lirt_sliv[$i],3)."</div></td>
					<td width=100><div class=\"data4\">".round($sum_rasxod[$i],3)."</div></td>
					</tr>";
				}
			echo "
					</table>
				</div>
			</div>";
		
                
    ?>
</body>

<?
function get_general_probeg ($carid,$tfrom,$tto){
$sql="select dest from data.gps where carid =".$carid." and tstamp >='".$tfrom."' and tstamp <='".$tto."' and lat>0 and lon>0
	order by tstamp;";
$res=pg_query($sql);
$dist=0;
while($track = pg_fetch_array($res)){
	extract($track, EXTR_OVERWRITE);
	$dist+=$dest;
	}
return $dist/1000;
}

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

//echo $sql;
  $arr[]=array();
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
	}//if stop
	$data=$data_new;
	}
    }
   }
  }
pg_query ("drop table ttt1");
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

