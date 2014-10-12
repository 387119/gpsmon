<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
</head>
<body style="font-family:Tahoma">
        Оперативный отчет за период с <?="$_REQUEST[datefrom] $_REQUEST[timefrom]"?> по <?="$_REQUEST[dateto] $_REQUEST[timeto]"?>
        <table border="1" cellpadding="5" cellspacing="0" style="font-size: 9pt">
        <thead>
            <tr>
                <th rowspan="2">№ п/п</th>
                <th rowspan="2">Авто</th>
                <th rowspan="2">Госномер</th>
                <th rowspan="2">Дата</th>
                <th rowspan="2">Время<br />движения</th>
                <th rowspan="2">Моточас</th>
                <th rowspan="2">Время<br />стоянки</th>
                <th rowspan="2">Пробег,&nbsp;км</th>
                <th rowspan="2">Топлива в<br />начале</th>
                <th colspan="2">Заправки</th>
                <th colspan="2">Сливы</th>
        	<th rowspan="2">Остаток <br /> Топлива</th>
        	<th rowspan="2">Фактический<br /> расход</th>
        	<th rowspan="2">Средний<br /> расход</th>
            </tr>
    	    <tr>
                <th>Кол-во</th>
                <th>Обьем</th>
                <th>Кол-во</th>
                <th>Обьем</th>
    	    </tr>
        </thead>
        <tbody>
<?php
	include_once("../../config.php");
	include_once("class.php");
	$report = new Reports;
	$datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
	$dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
	$timefrom = $_REQUEST['timefrom'];
	$timeto = $_REQUEST['timeto'];
	//$tstimefrom = $datefrom ." ". $timefrom;
	//$tstimeto = $dateto ." ". $timeto;
	$req = $report->GetTime($datefrom,$dateto,$timefrom,$timeto);
	$num = 0;
	$row=1;
	foreach ($_REQUEST['cars'] as $car=>$value) {
		$itogo_dest = 0;
		$itogo_time_moto = 0;
		$itogo_fuel_start = 0;
		$itogo_fuel_end = 0;
		$itogo_time_move = 0;
		$itogo_time_stop = 0;
		$itogo_count_up = 0;
		$itogo_count_down = 0;
		$itogo_up = 0;
		$itogo_down = 0;
		$up=0;$down=0;
		$CarData = $report->GetCarData($car);
		if($CarData['tank1']||$CarData['tank2']){
			if($row>1)echo "<tr><td colspan='16'>&nbsp;</td></tr>";
			
			for($i=0;$i<count($req);$i++){
				$tstimefrom = $req[$i]['tstamp_start'];
				$tstimeto   = $req[$i]['tstamp_end'];
				$dest = $report->GetSpeedDest($tstimefrom,$tstimeto,$car,0,0,1);
				$time = $report->GetTimeMoveStop($tstimefrom,$tstimeto,$car);
				$time_moto = $report->GetTimeOfMoto($tstimefrom,$tstimeto,$car);
				$stops = $report->GetStops($tstimefrom,$tstimeto,$car);
				$SE = $report->GetFuelSE($tstimefrom,$tstimeto,$car,$CarData);
				//$fuel = $report->GetFuelUpDown($car,$CarData,$stops);
				$fuel = $report->GetFuelUpDown($tstimefrom,$tstimeto,$car,$CarData);
				$fuel1 = $report->GetAllUpDown($fuel);
				//print_r($fuel1);
				$itogo_dest += $dest['dest'];
				$itogo_time_moto += $time_moto;
				if($i==0)$itogo_fuel_start = $SE['dut_start'];
				if($i==count($req)-1)$itogo_fuel_end = $SE['dut_end'];
				$itogo_time_move += $time['move'];
				$itogo_time_stop += $time['stop'];
				$itogo_count_up += $fuel[count($fuel)-1]['count_up'];
				$itogo_count_down += $fuel[count($fuel)-1]['count_down'];
				$itogo_up += $fuel1['up'];
				$itogo_down += $fuel1['down'];
				if($dest['dest']<400)$fact=0;
				else $fact = $SE['dut_start']+$fuel1['up']+$fuel1['down']-$SE['dut_end'];
				if($fact<0)$fact=0;
				echo	"<tr>";
				echo	"<td align='center'>".($i==0 ? $row : '&nbsp;')."</td>";
				echo	"<td align='center'>".($i==0 ? $CarData['name'] : '&nbsp;')."</td>";
				echo	"<td align='center'>".($i==0 ? $CarData['gosnum'] : '&nbsp;')."</td>";
				echo	"<td align='center'>".date('d.m.Y',strtotime($tstimefrom))."</td>";
				echo	"<td align='center'>".$report->ConvertTimeToString($time['move'])."</td>";
				echo	"<td align='center'>".$report->ConvertTimeToString($time_moto)."</td>";
				echo	"<td align='center'>".$report->ConvertTimeToString($time['stop'])."</td>";
				echo	"<td align='center'>".($dest['dest']/1000)."</td>";
				echo	"<td align='center'>".round($SE['dut_start'],2)."</td>";
				echo	"<td align='center'>".$fuel[count($fuel)-1]['count_up']."</td>";
				echo	"<td align='center'>".round($fuel1['up'],2)."</td>";
				echo	"<td align='center'>".$fuel[count($fuel)-1]['count_down']."</td>";
				echo	"<td align='center'>".round($fuel1['down']*-1,2)."</td>";
				echo	"<td align='center'>".round($SE['dut_end'],2)."</td>";
				echo	"<td align='center'>".round($fact,2)."</td>";
				echo	"<td align='center'>".round(($fact*100000/$dest['dest']),2)."</td>";
				echo	"</tr>";
			}
			if($i>0){
				if($dest['dest']<400)$fact=0;
				else $fact = $itogo_fuel_start+$itogo_up+$itogo_down-$itogo_fuel_end;
				if($fact<0)$fact=0;
				echo	"<tr>";
				echo	"<td align='center'>&nbsp;</td>";
				echo	"<td align='center'>&nbsp;</td>";
				echo	"<td>&nbsp;</td>";
				echo	"<td align='center'><b>Итого</b></td>";
				echo	"<td align='center'><b>".$report->ConvertTimeToString($itogo_time_move)."</b></td>";
				echo	"<td align='center'><b>".$report->ConvertTimeToString($itogo_time_moto)."</b></td>";
				echo	"<td align='center'><b>".$report->ConvertTimeToString($itogo_time_stop)."</b></td>";
				echo	"<td align='center'><b>".($itogo_dest/1000)."</b></td>";
				echo	"<td align='center'><b>".round($itogo_fuel_start,2)."</b></td>";
				echo	"<td align='center'><b>".$itogo_count_up."</b></td>";
				echo	"<td align='center'><b>".round($itogo_up,2)."</b></td>";
				echo	"<td align='center'><b>".$itogo_count_down."</b></td>";
				echo	"<td align='center'><b>".round($itogo_down*-1,2)."</b></td>";
				echo	"<td align='center'><b>".round($itogo_fuel_end,2)."</b></td>";
				echo	"<td align='center'><b>".round($fact,2)."</b></td>";
				echo	"<td align='center'><b>".round(($fact*100000/$itogo_dest),2)."</b></td>";
			}
		}
	$row++;
	}



?>
        </tbody>
    </table>
</body>