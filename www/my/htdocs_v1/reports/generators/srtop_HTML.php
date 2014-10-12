<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <script src="/jquery/js/jquery.js" type="text/javascript"></script>
    <script type="text/javascript" src="/js/flot/jquery.flot.min.js"></script>
</head>
<body style="font: 9pt Tahoma">
    <?
        $datefrom = date('Y-m-d', strtotime($datefrom));
        $dateto = date('Y-m-d', strtotime($dateto));
        
        foreach ($cars as $car => $value) {


            $sql="select gosnum,name,dutmin[1] dut1_min,dutmax[1] dut1_max,dutlitr[1] dut1_litr,dutmin[2] dut2_min,dutmax[2] dut2_max,dutlitr[2] dut2_litr from cars where carid=$car;";
            $res=pg_query($sql);
            extract (pg_fetch_array($res),EXTR_OVERWRITE);

            echo "<div style=\"margin:10px 0; font-size:12pt\"> Отчёт по объекту: $name ($gosnum)</div>";                
            if ($dut1_min && $dut1_max && $dut1_litr) {
                $tank1_installed = true; // бак №1 установлен и его настройки заполнены
            } else {
                $tank1_installed = false;
            }
            if ($dut2_min && $dut2_max && $dut2_litr) {
                $tank2_installed = true; // бак №2 установлен и его настройки заполнены
            } else {
                $tank2_installed = false;
            }

            if ($tank1_installed || $tank2_installed) { 
                if (!$tank1_installed) echo '<div style="margin: 3px 300px; padding: 3px; text-align: center; background-color: #ffff99;">ДУТ на бак №1 не установлен</div>';
                if (!$tank2_installed) echo '<div style="margin: 3px 300px; padding: 3px; text-align: center; background-color: #ffff99;">ДУТ на бак №2 не установлен</div>';
                // $dut1_min, $dut1_max, $dut1_litr - частота при пустом баке, при полном и литраж бака - для бака №1
                // $dut2_min, $dut2_max, $dut2_litr - частота при пустом баке, при полном и литраж бака - для бака №2

                

                $query = "SELECT date_part('epoch',tstamp) epoch, dut[1] dut1, dut[2] dut2, lat/600000::real as lat, lon/600000::real as lon dest FROM data.gps WHERE carid=$car AND tstamp>='$datefrom $timefrom' AND tstamp<='$dateto $timeto' and lat>0 and lon>0 ORDER BY epoch";
                $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
                if (pg_num_rows($result) > 0) {
                    $result_array = pg_fetch_all($result);
                } else {
                    $result_array = array();
                }

                $dut1_total_fuel_consumption = 0;
                $dut2_total_fuel_consumption = 0;

                $dut1_previous_liter = false;
                $dut2_previous_liter = false;

                $dut1_arr = array();
                $dut2_arr = array();
                $dut1_liter_prev = false;
                $dut2_liter_prev = false;
                $sum_dest = 0;

            // $sum_dest2 = 0;
            // for ($i = 1; $i < count($result_array); $i++) { 
            //     $sum_dest2 += computeDistanceBetween(
            //             $result_array[$i - 1]['lat'] / 600000, 
            //             $result_array[$i - 1]['lon'] / 600000, 
            //             $result_array[$i]['lat'] / 600000, 
            //             $result_array[$i]['lon'] / 600000);
                
            // }




                foreach ($result_array as $track) { // "очистка" данных
                    $sum_dest += $track['dest'];
                    if ($track['dut1'] >= $dut1_min && $track['dut1'] <= $dut1_max) { // если значения частоты валидны
                        $dut1_herz_percent = ($track['dut1'] - $dut1_min) * 100 / ($dut1_max - $dut1_min); // процент от общего количества герц
                        $dut1_liter = intval($dut1_herz_percent * $dut1_litr / 100); // текущий показатель расхода топлива в литрах
                        // echo $dut1_liter.'<br>';
                        if ($dut1_liter_prev && $dut1_liter > $dut1_liter_prev) { // если текущий показатель расхода топлива больше предыдущего
                            if (abs($dut1_liter - $dut1_liter_prev) < 20) { // и меньше 30 литров, то значит это НЕ заправка 
                                $dut1_arr[] = $dut1_liter_prev; // и сохраняем предыдущее значение литража, приняв текущее значение ошибочным
                            } else { // иначе повышение уровня литража произошло вследствии заправки
                                $dut1_arr[] = $dut1_liter; // сохраняем текущее значение литража
                                $dut1_liter_prev = $dut1_liter;
                            }
                        } else { // текущее значение литража меньше предыдущего - так и должно быть, сохраняем текущее значение
                            $dut1_arr[] = $dut1_liter;
                            $dut1_liter_prev = $dut1_liter;
                        }
                    }
                    if ($track['dut2'] >= $dut2_min && $track['dut2'] <= $dut2_max) { // если значения частоты валидны
                        $dut2_herz_percent = ($track['dut2'] - $dut2_min) * 100 / ($dut2_max - $dut2_min); // процент от общего количества герц
                        $dut2_liter = intval($dut2_herz_percent * $dut2_litr / 100); // текущий показатель расхода топлива в литрах
                        if ($dut2_liter_prev && $dut2_liter > $dut2_liter_prev) { // если текущий показатель расхода топлива больше предыдущего
                            if (abs($dut2_liter - $dut2_liter_prev) < 20) { // и меньше 30 литров, то значит это НЕ заправка 
                                $dut2_arr[] = $dut2_liter_prev; // и сохраняем предыдущее значение литража, приняв текущее значение ошибочным
                            } else { // иначе повышение уровня литража произошло вследствии заправки
                                $dut2_arr[] = $dut2_liter; // сохраняем текущее значение литража
                                $dut2_liter_prev = $dut2_liter;
                            }
                        } else { // текущее значение литража меньше предыдущего - так и должно быть, сохраняем текущее значение
                            $dut2_arr[] = $dut2_liter;
                            $dut2_liter_prev = $dut2_liter;
                        }
                    }
                }
                $sum_dest = round($sum_dest / 1000, 3);

                foreach ($dut1_arr as $value) {
                    if ($dut1_previous_liter) {
                        $dut1_liter_delta = $dut1_previous_liter - $value;
                        if ($dut1_liter_delta > 0) $dut1_total_fuel_consumption += $dut1_liter_delta;
                    } 
                    $dut1_previous_liter = $value;
                }
                foreach ($dut2_arr as $value) {
                    if ($dut2_previous_liter) {
                        $dut2_liter_delta = $dut2_previous_liter - $value;
                        if ($dut2_liter_delta > 0) $dut2_total_fuel_consumption += $dut2_liter_delta;
                    } 
                    $dut2_previous_liter = $value;
                }

                if ($dut1_total_fuel_consumption == 0/* && $dut2_total_fuel_consumption == 0*/) {
                    echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет данных за выбранный период</div><hr />";
                } else {
                    echo "<p style=\"font:12pt Tahoma\">
                        Всего за период израсходовано: $dut1_total_fuel_consumption л.<br>
                        <!--Всего за период израсходовано из бака №2: $dut2_total_fuel_consumption<br>-->
                        Всего пройдено: $sum_dest км.<br>
                        Средний расход на 100 км: ".round(($dut1_total_fuel_consumption * 100 / $sum_dest), 2)." л.<br /><hr />
                    ";
                }
            } else {
                echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет настроек по ДУТам</div><hr />";
            }            
        }
    ?>
</body>
