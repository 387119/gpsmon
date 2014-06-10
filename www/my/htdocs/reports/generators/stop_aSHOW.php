<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <script src="/jquery/js/jquery.js" type="text/javascript"></script>
</head>
<body style="font: 9pt Tahoma">
    <?
	include_once("class.php");
	$reqc = new Reports;
	$datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
	$dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
	$timefrom = $_REQUEST['timefrom'];
	$timeto = $_REQUEST['timeto'];
	$debug = $_REQUEST['debug'] == 1;
	
	$tstamp_start = $datefrom." ".$timefrom;
	$tstamp_end = $dateto." ".$timeto;
	foreach ($_REQUEST['cars'] as $car) {
		$car_info = $reqc->GetCarData($car);
		$arr_stops = $reqc->GetStops($tstamp_start,$tstamp_end,$car);
		//print_r($arr_stops);
		echo "<div style=\"margin:10px 0; font-size:12pt\"> Отчёт по объекту: ".$car_info['name']." (".$car_info['gosnum'].")</div>";
		if (count($arr_stops) > 0) {
			echo "<table border=\"1\" cellspacing='0' cellpadding='5'>";
			echo "<thead><tr><th><span>№ п/п</span></th><th><span>Начало</span></th><th><span>Конец</span></th><th><span>Время стоянки</span></th><th><span>Адрес</span></th><!--<th><span>На карте</span></th>--></tr></thead><tbody>";
			foreach ($arr_stops as $i=>$stop) {
				echo "<tr onmouseout=\"this.className='normal';\" onmouseover=\"this.className='hover';\" class='normal' id='color2'>";
				echo "<td>".($i+1)."</td>";
				echo "<td>".$stop['tstampb']."</td>";
				echo "<td>".$stop['tstamp']."</td>";
				
				$ss = strtotime($stop['tstamp']) - strtotime($stop['tstampb']); // время стоянки в секундах
				$time = $reqc->ConvertTimeToString($ss);
				echo "<td>$time</td>";
				
				//$reverse_geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng=48.33417333333333,38.40648333333333&sensor=false&language=ru');
				$lat = $stop['lat']/600000;
				$lon = $stop['lon']/600000;
				$reverse_geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.($lat).','.($lon).'&sensor=false&language=ru');
				$reverse_geo = json_decode($reverse_geo, true);
				echo "<td>{$reverse_geo[results][0][formatted_address]}</td>";
				echo "<!--<td><a style='font-size: 9pt; color: #606060; text-decoration: underline' href='javascript:setStopMarker(".$reverse_geo[results][0][geometry][location][lat].",".$reverse_geo[results][0][geometry][location][lng].")'>показать</a></td>-->";
				echo "</tr>";
			
		}
		echo "</tbody></table><br /><hr />";
            } else {
                echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет данных за выбранный период</div><hr />";
            }    

        }
    ?>
</body>


