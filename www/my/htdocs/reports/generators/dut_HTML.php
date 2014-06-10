<head>
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
        
        foreach ($_REQUEST['cars'] as $car => $value) {
            $sql = "select gosnum,name,dutmin[1] dut1_min,dutmax[1] dut1_max,dutlitr[1] dut1_litr,dutmin[2] dut2_min,dutmax[2] dut2_max,dutlitr[2] dut2_litr from cars where carid=$car;";
            $res = pg_query($sql);
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

                $query = "SELECT date_part('epoch', tstamp) epoch, dut[1] dut1, dut[2] dut2, lat/600000::real as lat, lon/600000::real as lon FROM data.gps WHERE carid=$car AND tstamp>='$datefrom $timefrom' AND tstamp<='$dateto $timeto' and lat>0 and lon>0 ORDER BY epoch";
                $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
                if (pg_num_rows($result) > 0) {
                    $result_array = pg_fetch_all($result);
                } else {
                    $result_array = array();
                }

                $dut1_data = ''; // массив для ДУТа бака №1
                $dut2_data = ''; // массив для ДУТа бака №2
                foreach ($result_array as $track) {
                    $epoch = ($track['epoch'] + 2*60*60) * 1000; // компенсация временной зоны и преобразование в микросекунды
                    if ($track['dut1'] >= $dut1_min && $track['dut1'] <= $dut1_max) { // если валидные значения частоты
                        $dut1_herz_percent = ($track['dut1'] - $dut1_min) * 100 / ($dut1_max - $dut1_min); // процент от общего количества герц
                        $dut1_liter = intval($dut1_herz_percent * $dut1_litr / 100);
                        $dut1_data .= "[$epoch,$dut1_liter],";
                    }
                    if ($track['dut2'] >= $dut2_min && $track['dut2'] <= $dut2_max) { // если валидные значения частоты
                        $dut2_herz_percent = ($track['dut2'] - $dut2_min) * 100 / ($dut2_max - $dut2_min); // процент от общего количества герц
                        $dut2_liter = intval($dut2_herz_percent * $dut2_litr / 100);
                        $dut2_data .= "[$epoch,$dut2_liter],";
                    }
                }

                if (empty($dut1_data) && empty($dut2_data)) {
                    echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет данных за выбранный период</div><hr />";
                } else {
                    echo "
                        <center>
                        <div id='placeholder$car' style='height:300px; width:910px'></div>
                        </center>
                        <hr />
                        <script type='text/javascript'>
                            $(function () {
                                var dut1_data = [$dut1_data]; // массив показателей ДУТа бака №1
                                var dut2_data = [$dut2_data]; // массив показателей ДУТа бака №2

                                var placeholder$car = $(\"#placeholder$car\"); // контейнер для графика
                                var data = [
                                    { data: dut1_data, label: \"Топливо (ДУТ) бак №1, л\", yaxis: 2, color: \"#099999\"}, 
                                    { data: dut2_data, label: \"Топливо (ДУТ) бак №2, л\", yaxis: 2, color: \"#009900\"}];
                                var options = { // параметры графика
                                    series: {
                                        lines: { 
                                            show: true, 
                                            steps: false 
                                        }
                                    },
                                    grid: { 
                                        hoverable: true 
                                    },
                                    // selection: { 
                                        // mode: \"x\"
                                    // },
                                    xaxes: [{
                                        mode: \"time\"
                                    }],
                                    yaxes: [{
                                        min: 0
                                    },{
                                        min: 0,
                                        position: \"right\",
                                        alignTicksWithAxis: 1
                                    }],
                                    shadowSize: 0
                                };

                                placeholder$car.bind(\"plothover\", function (event, pos, item) { // всплывающее окно с данными о точке
                                    if (item) {
                                        if (previousPoint != item.dataIndex) {
                                            previousPoint = item.dataIndex;
                                            $(\"#tooltip\").remove();
                                            var x = item.datapoint[0], // значение по оси X
                                                y = item.datapoint[1]; // значение по оси Y
                                            var d = new Date(x - 3*60*60*1000);
                                            switch (item.seriesIndex) {
                                                case 0: // ДУТ бак №1
                                                    showTooltip(item.pageX, item.pageY, d.getHours() + \":\" + d.getMinutes() + \" - \" + y + \" л\");
                                                    break;
                                                case 1: // ДУТ бак №2
                                                    showTooltip(item.pageX, item.pageY, d.getHours() + \":\" + d.getMinutes() + \" - \" + y + \" л\");
                                                    break;
                                            }
                                        }
                                    }
                                    else {
                                        $(\"#tooltip\").remove()
                                        previousPoint = null;            
                                    }
                                });

                                // placeholder$car.bind(\"plotselected\", function (event, ranges) { // перестроение графика при выделении
                                //     plot = $.plot(placeholder$car, data,
                                //         $.extend(true, {}, options, {
                                //         xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
                                //     }));
                                // });

                                $.plot(placeholder$car, data, options); // построение графика


                            });
                        </script>
                        <script>
                            function showTooltip(x, y, contents) { // всплывающая подсказка
                                $('<div id=\"tooltip\">' + contents + '</div>').css( {
                                    position: 'absolute',
                                    display: 'none',
                                    top: y + 5,
                                    left: x + 5,
                                    padding: '2px',
                                    'background-color': '#fee',
                                    opacity: 0.80,
                                    'z-index': 999
                                }).appendTo(\"body\").fadeIn(200);
                            }
                        </script>
                    ";
                }
            } else {
                echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет настроек по ДУТам</div><hr />";
            }
        }
    ?>
</body>
