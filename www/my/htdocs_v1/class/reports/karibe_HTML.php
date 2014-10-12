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
	include_once("./class.php");
	
	$datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
	$dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
	$timefrom = $_REQUEST['timefrom'];
	$timeto = $_REQUEST['timeto'];
	$tstimefrom = $datefrom ." ". $timefrom;
	$tstimeto = $dateto ." ". $timeto;
	
	$report = new Reports;
	$db_connect = $report->ConnectToPsql('db1','gps','tgps','tgpspwd');
	foreach ($_REQUEST['cars'] as $car=>$value) {
		$CarData = $report->GetCarData($car);
		echo	"<tr>";
		echo	"<td align='center'>"."</td>";
		echo	"<td align='center'>".$CarData['name']."</td>";
		if(1)echo "<td align='center'>"."</td>";
		else echo "<td>&nbsp;</td>";
		echo	"<td align='center'>"."</td>";
		echo	"<td align='center'>"."</td>";
		echo	"<td align='center'>"."</td>";
		echo	"<td align='center'>"."</td>";
		echo	"<td align='center'>"."</td>";
		if (1) {
			echo	"<td align='center'><b>Нет настроек по ДУТам</b></td>";
			echo	"<td align='center'>-</td>";
			echo	"<td align='center'>-</td>";
			echo	"<td align='center'>-</td>";
			echo	"<td align='center'>-</td>";
			echo	"<td align='center'>-</td>";
			echo	"<td align='center'>-</td>";
			echo	"<td align='center'>-</td>";
		}else {
			echo	"<td align='center'>"."</td>";
			echo	"<td align='center'></td>";
			echo	"<td align='center'>"."</td>";
			echo	"<td align='center'></td>";
			echo	"<td align='center'>"."</td>";
			echo	"<td align='center'>"."</td>";
			echo	"<td align='center'>"."</td>";
			echo	"<td align='center'>"."</td>";
		}
		echo	"</tr>";
		
	}


/*


<?php
		
		
?>

*/

?>
        </tbody>
    </table>
</body>