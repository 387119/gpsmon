        Сводный отчет за период с <?="$_REQUEST[datefrom] $_REQUEST[timefrom]"?> по <?="$_REQUEST[dateto] $_REQUEST[timeto]"?>
        <table border="1" cellpadding="5" cellspacing="0" style="font-size: 9pt">
        <thead>
            <tr>
                <th>№ п/п</th>
                <th>Тип ТС</th>
                <th>Госномер</th>
                <th>Дата</th>
                <th>Время<br />движения</th>
                <th>Время<br />стоянки</th>
                <th>Средняя<br />скорость,&nbsp;км/ч</th>
                <th>Максимальная<br />скорость,&nbsp;км/ч</th>
                <th>Пробег,&nbsp;км</th>
                <th>Количество<br />заправок</th>
            </tr>
        </thead>
        <tbody>
		<?
		include_once("../../config.php");
		include ("class.php");
		$reqc = new Reports;
		$row = 1; // текущая строка
		$datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
		$dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
		$timefrom = $_REQUEST['timefrom'];
		$timeto = $_REQUEST['timeto'];
		$time = $reqc->GetTime($datefrom,$dateto,$timefrom,$timeto);
		foreach ($_REQUEST['cars'] as $car) {
			$z=0;
			$sum_move = 0;
			$sum_stop = 0;
			$sum_dest = 0;
			$speed_avg = 0;
			$speed_max = 0;
			if ($row > 1) echo "<tr><td colspan='10'>&nbsp;</td><tr>";
			for($i=0;$i<count($time);$i++){
				$car_data = $reqc->GetCarData($car);
				$times = $reqc->GetTimeMoveStop($time[$i]['tstamp_start'],$time[$i]['tstamp_end'],$car);
				$req = $reqc->GetSpeedDest($time[$i]['tstamp_start'],$time[$i]['tstamp_end'],$car,1,1,1);
				$sum_move += $times['move'];
				$sum_stop += $times['stop'];
				$sum_dest += $req['dest'];
				if($speed_max<$req['speed_max'])$speed_max = $req['speed_max'];
				if($req['speed_avg']>0){
					$speed_avg += $req['speed_avg'];
					$z++;
				}
				echo "<tr>";
				echo     "<td>".($i==0 ? $row : '&nbsp;')."</td>";
				echo     "<td>".($i==0 ? $car_data['name'] : '&nbsp;')."</td>";
				echo     "<td>".($i==0 ? $car_data['gosnum'] : '&nbsp;')."</td>";
				echo     "<td>".date('d.m.y', strtotime($time[$i]['tstamp_start']))."</td>";
				echo     "<td>".$reqc->ConvertTimeToString($times['move'])."</td>";
				echo     "<td>".$reqc->ConvertTimeToString($times['stop'])."</td>";
				echo     "<td>".($req['speed_avg'] ? round($req['speed_avg'],2) : '0') ."</td>";
				echo     "<td>".($req['speed_max'] ? $req['speed_max'] : '0')."</td>";
				echo     "<td>".($req['dest'] ? $req['dest']/1000 : '0')."</td>";
				if (!$car_data['tank1']||!$car_data['tank2']) {
					echo "<td>Нет настроек по ДУТам</td>";
				} else { // не было ни одной заправки
					echo "<td>0</td>";
				}
				echo "</tr>";
			
			}
			echo "<tr>";
			echo     "<td>&nbsp;</td>";
			echo     "<td>&nbsp</td>";
			echo     "<td>&nbsp</td>";
			echo     "<td><b>Итого</b></td>";
			echo     "<td><b>".$reqc->ConvertTimeToString($sum_move)."</b></td>";
			echo     "<td><b>".$reqc->ConvertTimeToString($sum_stop)."</b></td>";
			echo     "<td><b>".round(($speed_avg/$z),2)."</b></td>";
			echo     "<td><b>".$speed_max."</b></td>";
			echo     "<td><b>".($sum_dest!=0 ? $sum_dest/1000 : '0')."</b></td>";
			if (!$car_data['tank1']||!$car_data['tank2']) {
				echo "<td><b>Нет настроек по ДУТам</b></td>";
			}
			else { // не было ни одной заправки
				echo "<td><b>0</b></td>";
			}
			echo "</tr>";
			$row++;
		}

		?>
        </tbody>
    </table>



