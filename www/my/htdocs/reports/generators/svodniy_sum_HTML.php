<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="font-family:Tahoma">
        Сводный отчет за период с <?="$_REQUEST[datefrom] $_REQUEST[timefrom]"?> по <?="$_REQUEST[dateto] $_REQUEST[timeto]"?>
        <table border="1" cellpadding="5" cellspacing="0" style="font-size: 9pt">
        <thead>
            <tr>
                <th>№ п/п</th>
                <th>Тип ТС</th>
                <th>Госномер</th>
                <th>Время<br />движения</th>
                <th>Время<br />стоянки</th>
                <th>Средняя<br />скорость,&nbsp;км/ч</th>
                <th>Максимальная<br />скорость,&nbsp;км/ч</th>
                <th>Пробег,&nbsp;км</th>
                <th>Количество<br />заправок</th>
                <!-- <th>Место<br />заправки</th> -->
                <!-- <th>Заправка,&nbsp;м3</th> -->
            </tr>
        </thead>
        <tbody>
            <?
                include("../../config.php");
                include("class.php");
                $row = 1; // текущая строка

                $datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
                $dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
                $timefrom = $_REQUEST['timefrom'];
                $timeto = $_REQUEST['timeto'];
		$tstampfrom=$datefrom."T".$timefrom;
		$tstampto=$dateto."T".$timeto;
                $reqc=new Reports;
                foreach ($_REQUEST['cars'] as $car=>$value) {
		$resc1=$reqc->GetCarData($car);
		$resc2=$reqc->GetTimeMoveStop($tstampfrom,$tstampto,$car);
		$resc3=$reqc->GetSpeedDest($tstampfrom,$tstampto,$car,1,1,1);
		//$resc4=$reqc->GetFuelUpDown($tstampfrom,$tstampto,$car,$resc1);
		//print_r($resc4);
		//print_r ($resc3);
                    echo "<tr>";
                    echo     "<td>$row</td>";
                    echo     "<td>".$resc1['name']."</td>";
                    echo     "<td>".$resc1['gosnum']."</td>";
                    echo     "<td>".$reqc->ConvertTimeToString($resc2['move'])."</td>";
                    echo     "<td>".$reqc->ConvertTimeToString($resc2['stop'])."</td>";
                    echo     "<td>".round($resc3['speed_avg'],1)."</td>";
                    echo     "<td>".$resc3['speed_max']."</td>";
                    echo     "<td>".($resc3['dest']/1000)."</td>";
                    echo "<td>Нет настроек по ДУТам</td>";
                    echo "<td>0</td>";
                    echo "</tr>";
                    $row++;
                }
            ?>
        </tbody>
    </table>
</body>
