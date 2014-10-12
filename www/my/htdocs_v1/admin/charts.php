<? require_once "config.php" ?>
<?
    // function BCoord($x0, $x1, $x2, $x3, $t)
    // {
    //    return (1-$t)*(1-$t)*(1-$t)*$x0+
    //              3*$t*(1-$t)*(1-$t)*$x1+
    //              3*$t*$t*(1-$t)*$x2+
    //              $t*$t*$t*$x3;
    // }


    // function quadBezier($x1, $x2, $x3/*, $y2, $x3, $y3*/) {
    //     $points = array();
    //     echo "$x1-$x2-$x3<br>";
        
    //     $b = $pre1 = $pre2 = $pre3 = 0;
    //     $prevx = 0;
    //     // $prevy = 0;
    //     $d = sqrt(($x1 - $x2) * ($x1 - $x2)/* + ($y1 - $y2) * ($y1 - $y2)*/) +
    //         sqrt(($x2 - $x3) * ($x2 - $x3)/* + ($y2 - $y3) * ($y2 - $y3)*/);
    //     $resolution = (1/$d) * 10;
    //     echo $resolution;
    //     for ($a = 1; $a >0; $a-=$resolution) {
    //         $b=1-$a;
    //         $pre1=($a*$a);
    //         $pre2=2*$a*$b;
    //         $pre3=($b*$b);
    //         $x = $pre1*$x1 + $pre2*$x2  + $pre3*$x3;
    //         // $y = $pre1*$y1 + $pre2*$y2 + $pre3*$y3;
    //         // if ($prevx != 0 && $prevy != 0)
    //         if ($prevx != 0)
    //             $points[] = $x;
    //             // $points[] = array($x, $y);
    //         $prevx = $x;
    //         // $prevy = $y;
    //     }
    //     // $points[] = array($prevx, $prevy);
    //     $points[] = $x;
    //     return $points;
    // }

    // function curveLine($data)
    // {
    //     echo '<pre>';
    //     $bezier_line = array();
    //     foreach ($data as $i=>$point) {
    //         // $curved_line = quadBezier($point[1], $data[$i+1][1], $data[$i+2][1]);
    //         $curved_line = BCoord($data[$i][1], $data[$i+1][1], $data[$i+2][1], $data[$i+3][1], 1);
    //         print_r($curved_line);
    //         break;
    //     }
    // }

    $car = $_GET["car"];
    $dstart = $_GET["start"];
    $dend = $_GET["end"];
    $tstart = $_GET["starttime"];
    $tend = $_GET["endtime"];
    // $mode = $_GET["mode"];
    // $toexel = $_GET["toexel"];
    // $a = $_GET["a"];
    // $el = $_GET["el"];
    $dutmin = 500;
    $dutmax = 1500;
    $dutlitr = 500;

    $query = "SELECT date_part('epoch', tstamp) epoch, speed, dut[2] FROM data.gps WHERE carid=$car AND tstamp>'$dstart $tstart' AND tstamp<'$dend $tend' ORDER BY tstamp ASC";
    $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
    if (pg_num_rows($result) > 0) {
        $result_array = pg_fetch_all($result);
    }

    $speed_data = '['; // массив для скорости
    $dut_data = '['; // массив для ДУТа
    $dut_data2 = '['; // массив для ДУТа
    $dut_data_points = array();
    $prev_dut_liter = false;
    foreach ($result_array as $track) {
        
        $epoch = ($track['epoch'] + 3*60*60) * 1000; // компенсация временной зоны и преобразование в микросекунды
        $speed_data .= "[$epoch,$track[speed]],";
        if ($track['dut'] >= $dutmin && $track['dut'] <= $dutmax) { // если валидные значения частоты
            $dut_herz_percent = ($track['dut'] - $dutmin) * 100 / ($dutmax - $dutmin); // процент от общего количества герц
            $dut_liter = intval($dut_herz_percent * $dutlitr / 100);
            $dut_data_points[] = array($epoch,$dut_liter);
            $dut_data2 .= "[$epoch,$dut_liter],";
            if ($prev_dut_liter && $dut_liter > $prev_dut_liter) {
                if (abs($dut_liter - $prev_dut_liter) < 30) {
                    $dut_data .= "[$epoch,$prev_dut_liter],";
                    // echo "prev=$prev_dut_liter cur=$dut_liter used=$prev_dut_liter<br>";
                } else {
                    $dut_data .= "[$epoch,$dut_liter],";
                    // echo "prev=$prev_dut_liter cur=$dut_liter used=$dut_liter<br>";
                    $prev_dut_liter = $dut_liter;
                }
            } else {
                $dut_data .= "[$epoch,$dut_liter],";
                // echo "prev=$prev_dut_liter cur=$dut_liter used=$dut_liter<br>";
                $prev_dut_liter = $dut_liter;
            }
        }

    }

    // curveLine($dut_data_points);

    $speed_data .= ']';
    $dut_data .= ']';
    $dut_data2 .= ']';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>GPS Charts</title>
        <!--[if lte IE 8]><script type="text/javascript" src="/js/excanvas.min.js"></script><![endif]-->
        <script type="text/javascript" src="/js/jquery-1.7.1.min.js"></script>
        <script type="text/javascript" src="/js/flot/jquery.flot.min.js"></script>
        <script type="text/javascript" src="/js/flot/jquery.flot.selection.min.js"></script>
    </head>
    <body>
        <div id="placeholder" style="height:500px; width:1000px"></div>

        <script type="text/javascript">
            $(function () {
                var speed_data = <?=$speed_data?>; // массив показателей скорости
                var dut_data = <?=$dut_data?>; // массив показателей ДУТа
                var dut_data2 = <?=$dut_data2?>; // массив показателей ДУТа

                var placeholder = $("#placeholder"); // контейнер для графика
                var data = [
                    { data: speed_data, label: "Скорость, км/ч", color: "#FF7400"}, 
                    { data: dut_data, label: "Топливо (ДУТ), л", yaxis: 2, color: "#099999"},
                    { data: dut_data2, label: "Топливо (ДУТ)2, л", yaxis: 2, color: "#999999"}
                ];
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
                    selection: { 
                        mode: "x"
                    },
                    xaxes: [{
                        mode: "time"
                    }],
                    yaxes: [{
                    },{
                        position: "right",
                        alignTicksWithAxis: 1
                    }],
                    shadowSize: 0
                };

                placeholder.bind("plothover", function (event, pos, item) { // всплывающее окно с данными о точке
                    if (item) {
                        if (previousPoint != item.dataIndex) {
                            previousPoint = item.dataIndex;
                            
                            $("#tooltip").remove();
                            var x = item.datapoint[0], // значение по оси X
                                y = item.datapoint[1]; // значение по оси Y
                            var d = new Date(x - 3*60*60*1000);
                            switch (item.seriesIndex) {
                                case 0: // скорость
                                    showTooltip(item.pageX, item.pageY, d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds() + " - " + y + " км/ч");
                                    break;
                                case 1: // топливо
                                    showTooltip(item.pageX, item.pageY, d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds() + " - " + y + " л");
                                    break;
                                case 2: // топливо
                                    showTooltip(item.pageX, item.pageY, d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds() + " - " + y + " л");
                                    break;
                            }
                            
                        }
                    }
                    else {
                        $("#tooltip").remove()
                        previousPoint = null;            
                    }
                });

                placeholder.bind("plotselected", function (event, ranges) { // перестроение графика при выделении
                    plot = $.plot(placeholder, data,
                        $.extend(true, {}, options, {
                        xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to }
                    }));
                });

                $.plot(placeholder, data, options); // построение графика
            });
        </script>

        <script type="text/javascript">
            function showTooltip(x, y, contents) { // всплывающая подсказка
                $('<div id="tooltip">' + contents + '</div>').css( {
                    position: 'absolute',
                    display: 'none',
                    top: y + 5,
                    left: x + 5,
                    padding: '2px',
                    'background-color': '#fee',
                    opacity: 0.80
                }).appendTo("body").fadeIn(200);
            }
        </script>
    </body>
