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
            $sql = "select gosnum,name from cars where carid=$car;";
            $res = pg_query($sql);
            extract (pg_fetch_array($res),EXTR_OVERWRITE);

            echo "<div style=\"margin:10px 0; font-size:12pt\"> Отчёт по объекту: $name ($gosnum)</div>";
                
            $query = "SELECT date_part('epoch', tstamp) epoch, speed, lat/600000::real as lat, lon/600000::real as lon FROM data.gps WHERE carid=$car AND tstamp>='$datefrom $timefrom' AND tstamp<='$dateto $timeto' and lat>0 and lon>0 ORDER BY epoch";
            $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
            if (pg_num_rows($result) > 0) {
                $result_array = pg_fetch_all($result);
            } else {
                $result_array = array();
            }

            $speed_data = ''; // массив для скорости
            foreach ($result_array as $track) {
                $epoch = ($track['epoch'] + 2*60*60) * 1000; // компенсация временной зоны и преобразование в микросекунды
                $speed_data .= "[$epoch,$track[speed]],";
            }

            if (empty($speed_data)) {
                echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет данных за выбранный период</div><hr />";
            } else {
                echo "
                    <center>
                    <div id='placeholder$car' style='height:300px; width:910px'></div>
                    </center>
                    <hr />
                    <script type='text/javascript'>
                        $(function () {
                            var speed_data = [$speed_data]; // массив показателей скорости

                            var placeholder$car = $(\"#placeholder$car\"); // контейнер для графика
                            var data = [
                                { data: speed_data, label: \"Скорость, км/ч\", color: \"#FF7400\"}]; 
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
                                            case 0: // скорость
                                                showTooltip(item.pageX, item.pageY, d.getHours() + \":\" + d.getMinutes() + \" - \" + y + \" км/ч\");
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
        }
    ?>
</body>
