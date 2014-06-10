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
            $salt = rand(10000, 99999);
            $sql = "select gosnum,name from cars where carid=$car;";
            $res = pg_query($sql);
            extract (pg_fetch_array($res),EXTR_OVERWRITE);

            echo "<div style=\"margin:10px 0; font-size:12pt\"> Отчёт по объекту: $name ($gosnum)</div>";
                
            $query = "SELECT date_part('epoch', tstamp) epoch, gpsdop, lat/600000::real as lat, lon/600000::real as lon FROM data.gps WHERE carid=$car AND tstamp>='$datefrom $timefrom' AND tstamp<='$dateto $timeto' and lat>0 and lon>0 ORDER BY epoch";
            $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
            if (pg_num_rows($result) > 0) {
                $result_array = pg_fetch_all($result);
            } else {
                $result_array = array();
            }

            $gps_data = ''; // массив для скорости
            foreach ($result_array as $track) {
                $epoch = ($track['epoch'] + 2*60*60) * 1000; // компенсация временной зоны и преобразование в микросекунды
                $gps_data .= '['.$epoch.','.($track[gpsdop] > 0 ? round($track[gpsdop] * 6 / 10) : 0).'],';
            }

            if (empty($gps_data)) {
                echo "<div style=\"background-color: #FFBCBC; font: 12pt Tahoma; margin: 50px 300px; padding: 10px; text-align: center;\">Нет данных за выбранный период</div><hr />";
            } else {
                echo "
                    <center>
                    <div id='placeholder$salt' style='height:300px; width:910px'></div>
                    </center>
                    <hr />
                    <script type='text/javascript'>
                        $(function () {
                            var gsm_data = [$gsm_data]; // массив показателей скорости
                            var gps_data = [$gps_data]; // массив показателей скорости

                            var placeholder$salt = $(\"#placeholder$salt\"); // контейнер для графика
                            var data = [
                                { data: gps_data, label: \"Точность сигнала GPS, м\", color: \"#660099\"}
                            ]; 
                            var options = { // параметры графика
                                series: {
                                    bars: { 
                                        show: true,
                                        
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
                                    // minTickSize: [10, \"second\"]
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

                            placeholder$salt.bind(\"plothover\", function (event, pos, item) { // всплывающее окно с данными о точке
                                if (item) {
                                    if (previousPoint != item.dataIndex) {
                                        previousPoint = item.dataIndex;
                                        $(\"#tooltip\").remove();
                                        var x = item.datapoint[0], // значение по оси X
                                            y = item.datapoint[1]; // значение по оси Y
                                        var d = new Date(x - 3*60*60*1000);
                                        switch (item.seriesIndex) {
                                            case 1: // gps
                                                showTooltip(item.pageX, item.pageY, d.getHours() + \":\" + d.getMinutes() + \" - GPS: \" + y + \"м\");
                                                break;
                                        }
                                    }
                                }
                                else {
                                    $(\"#tooltip\").remove()
                                    previousPoint = null;            
                                }
                            });

                            // placeholder$salt.bind(\"plotselected\", function (event, ranges) { // перестроение графика при выделении
                            //     plot = $.plot(placeholder$salt, data,
                            //         $.extend(true, {}, options, {
                            //         xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
                            //     }));
                            // });

                            $.plot(placeholder$salt, data, options); // построение графика


                        });
                    </script>
                    <script>
                        function showTooltip(x, y, contents) { // всплывающая подсказка
                            $('<div id=\"tooltip\">' + contents + '</div>').css( {
                                position: 'absolute',
                                display: 'none',
                                top: y + 5,
                                left: x + 15,
                                padding: '2px',
                                'background-color': '#fee',
                                opacity: 0.80,
                                'z-index': 9999
                            }).appendTo(\"body\").fadeIn(200);
                        }
                    </script>
                ";
            }
        }
    ?>

</body>
