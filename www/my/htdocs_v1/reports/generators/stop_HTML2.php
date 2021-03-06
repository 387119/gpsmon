<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <script src="/jquery/js/jquery.js" type="text/javascript"></script>
</head>
<body style="font: 9pt Tahoma">
    <?
        $datefrom = $_REQUEST['datefrom'];
        $dateto = $_REQUEST['dateto'];
        $timefrom = $_REQUEST['timefrom'];
        $timeto = $_REQUEST['timeto'];
        
        function computeDistanceBetween($lat1, $lon1, $lat2, $lon2) { // расчет расстояния между двумя координатами
            $R = 6371; // earth's mean radius in km
            $dLat  = deg2rad($lat2 - $lat1);
            $dLong = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLong/2) * sin($dLong/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $d = $R * $c;
            return round($d*1000, 3);
        }

        foreach ($_REQUEST['cars'] as $car => $value) {

            $sql="select gosnum,name,dutmin[1] dut1_min,dutmax[1] dut1_max,dutlitr[1] dut1_litr,dutmin[2] dut2_min,dutmax[2] dut2_max,dutlitr[2] dut2_litr from cars where carid=$car;";

            echo $sql;
            $res=pg_query($sql);
            extract (pg_fetch_array($res),EXTR_OVERWRITE);

            echo "<div style=\"margin:10px 0; font-size:12pt\"> Отчёт по объекту: $name ($gosnum)</div>";                

            $query = "SELECT date_part('epoch', tstamp) epoch, speed, lat, lon FROM data.gps WHERE carid=$car AND tstamp>='$datefrom $timefrom' AND tstamp<='$dateto $timeto' and lat>0 and lon>0 ORDER BY epoch";
            $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
            if (pg_num_rows($result) > 0) {
                $result_array = pg_fetch_all($result);
            } else {
                $result_array = array();
            }

            $arr_stops = array();

            $from_time = false;

            // $sum_time = 0;



            define('MIN_STOP_TIME', 300); // время простоя машины в секундах меньше этого считать стоянкой (отсеиваем простои в пробках и кратковременные остановки) (5 минут)
            define('MIN_TIME_BETWEEN_STOPS', 600); // время между остановками, если меньше то считать одной стоянкой
            define('MIN_SPEED', 2); // минимальная скорость, отсеиваем биения датчика скорости
            define('MIN_DISTANCE', 100); // минимальная расстояние между стоянками, которое считать одной стоянкой, в метрах
            
            // echo '<br>';
            
            foreach ($result_array as $track) {
                if ($track['speed'] < MIN_SPEED) { // если текущая скорость меньше минимальной - машина остановилась
                    echo '<span style="color:red">'.date('H:i:s', $track[epoch])." | $track[lat] $track[lon] | $track[speed]</span>";
                    if (!$from_time) $from_time = $track['epoch']; // начинаем отсчет времени стоянки
                } else { // текущая скорость превысила минимальную - машина двинулась
                    if ($from_time && ($track['epoch'] - $from_time) > MIN_STOP_TIME) { // если время стоянки превысило минимальное
                        echo '<span style="color:green">'.date('H:i:s', $track[epoch])." | $track[lat] $track[lon] | $track[speed]</span><br />";
                        $c = count($arr_stops);
                        $arr_stops[$c]['from_time'] = $from_time;
                        $arr_stops[$c]['to_time'] = $track['epoch'];
                        $arr_stops[$c]['latitude'] = $track['lat'] / 600000;
                        $arr_stops[$c]['longitude'] = $track['lon'] / 600000;
                        $from_time = false;
                    } else {
                        echo '<span style="color:black">'.date('H:i:s', $track[epoch])." | $track[lat] $track[lon] | $track[speed]</span><br />";
                        $from_time = false;
                    }
                }
            }
            
            if ($from_time && ($track['epoch'] - $from_time) > MIN_STOP_TIME) {
                $c = count($arr_stops);
                $arr_stops[$c]['from_time'] = $from_time;
                $arr_stops[$c]['to_time'] = $track['epoch'];
                $arr_stops[$c]['latitude'] = $track['lat'] / 600000;
                $arr_stops[$c]['longitude'] = $track['lon'] / 600000;
                echo '<span style="color:black">'.date('H:i:s', $track[epoch])." | $track[lat] $track[lon] | $track[speed]</span><br />";
            } 

            if (count($arr_stops) > 0) {
                // пересчет стоянок в соответствии с расстоянием между стоянками, если расстояние меньше минимального то считать стоянки - одной стоянкой
                for ($i = 1; $i < count($arr_stops); $i++) { 
                    if (computeDistanceBetween($arr_stops[$i - 1]['latitude'], $arr_stops[$i - 1]['longitude'], $arr_stops[$i]['latitude'], $arr_stops[$i - 1]['longitude']) < MIN_DISTANCE && $arr_stops[$i]['from_time'] - $arr_stops[$i - 1]['to_time'] < MIN_TIME_BETWEEN_STOPS) {
                        $arr_stops[$i - 1]['to_time'] = $arr_stops[$i]['to_time']; // объединить стоянки
                        array_splice($arr_stops, $i, 1); // удалить элемент массива
                        $i = 0; // начать заново
                    }
                }

                // echo "<script>function setStopMarker(lat, lng) { new google.maps.Marker({position: new google.maps.LatLng(lat,lng), map: map});  map.setCenter(new google.maps.LatLng(lat,lng)); }</script>";
                echo "<table border=\"1\" cellspacing='0' cellpadding='5'>";
                echo "<thead><tr><th><span>№ п/п</span></th><th><span>Начало</span></th><th><span>Конец</span></th><th><span>Время стоянки</span></th><th><span>Адрес</span></th><!--<th><span>На карте</span></th>--></tr></thead><tbody>";
                foreach ($arr_stops as $i=>$stop) {
                    $from_time = date('H:i:s d.m.Y', $stop['from_time']);
                    $to_time = date('H:i:s d.m.Y', $stop['to_time']);
                    echo "<tr onmouseout=\"this.className='normal';\" onmouseover=\"this.className='hover';\" class='normal' id='color2'>";
                    echo "<td>".($i+1)."</td>";
                    echo "<td>$from_time</td>";
                    echo "<td>$to_time</td>";
                    // расчет времени стоянки
                    $ss = $stop['to_time'] - $stop['from_time']; // время стоянки в секундах
                    // $sum_time+= $ss; // общее время всех стоянок
                    $h = floor($ss / 3600);
                    $m = floor(($ss - $h * 3600) / 60);
                    $time = ($h > 0 ? $m > 0 ? "$h ч. $m мин." : "$h ч." : "$m мин.");
                    echo "<td>$time</td>";
                    // http://maps.googleapis.com/maps/api/geocode/json?latlng=48.33417333333333,38.40648333333333&sensor=false&language=ru
                    $reverse_geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.($stop['latitude']).','.($stop['longitude']).'&sensor=false&language=ru');
                    $reverse_geo = json_decode($reverse_geo, true);
                    echo "<td>{$reverse_geo[results][0][formatted_address]}</td>";
                    echo "<!--<td><a style='font-size: 9pt; color: #606060; text-decoration: underline' href='javascript:setStopMarker(".$reverse_geo[results][0][geometry][location][lat].",".$reverse_geo[results][0][geometry][location][lng].")'>показать</a></td>-->";
                    echo "</tr>";
                }
                // $h = floor($sum_time / 3600);
                // $m = floor(($sum_time - $h * 3600) / 60);
                // $sum_time = ($h > 0 ? $m > 0 ? "$h ч. $m мин." : "$h ч." : "$m мин.");
                // echo "<tr><th>Итого:</th><th>-</th><th>-</th><th>$sum_time</th><th>-</th></tr>";
                echo "</tbody></table><br /><hr />";      
            } else {
                echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет данных за выбранный период</div><hr />";
            }    
        }
    ?>
</body>
