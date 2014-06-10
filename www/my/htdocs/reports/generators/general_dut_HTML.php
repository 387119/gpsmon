<head>
    <style type=text/css>
	body{	
		margin:0;
		padding:0;
		}
	#general{
		margin:0;
		padding:0;
		width:100%;
		height:400px;
		background-color:ffffff;
		}
	#title{
		padding:10px 0 10px 0;
		margin: 0 auto;
		width:22em;
		text-align:center;
		font-size:2.4em;
		font-weight:bold;
		
		}
	#box1{
		padding:0 0 0 20px;
		margin:0 auto;
		width:560px;
		}
	#box2{
		margin:0 auto;
		width:1300px;
		}
	#box3{
		margin:0 auto;
		width:1300px;
		}
	#box4{
		margin:0 auto;
		width:1300px;
		background-color:aaaaaa;
		border:1px solid;
		}
	#box1 span{
		margin:0;
		font-size:14px;
		font-weight:bold;
		}
	#box2 span{
		margin:0 auto;
		font-size:14px;
		font-weight:bold;
		}
	#box3 span{
		margin:0 auto;
		font-size:14px;
		//float:left;
		font-weight:bold;
		}
	#box4 th{
		//height:350px;
		//border:1px solid;
		background-color:aaaaaa;
		}
	.data{
		margin: 0 5px 0 5px;
		border-bottom:1px solid;
		font-size:18;
		text-align:center;
		width:200px;
		}
	.data1{
		text-align:center;
		font-size:18;
		border-bottom:1px solid;
		width:100px;
		}
	.data2{
		text-align:center;
		font-size:18;
		border-bottom:1px solid;
		width:100px;
		}
	.data3{
		height:200px;
		border:1px solid;
		background-color:cccccc;
		vertical-align:bottom;
		}
	.data4{
		border:1px solid;
		background-color:ffffff;
		text-align:center;
		height:1.3em;
		}
	.rotate{
		-ms-transform:rotate(270deg); /* IE 9 */
		-moz-transform:rotate(270deg); /* Firefox */
		-webkit-transform:rotate(270deg); /* Safari and Chrome */
		-o-transform:rotate(270deg); /* Opera */
		}
	
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="/jquery/js/jquery.js" type="text/javascript"></script>
    <script type="text/javascript" src="/js/flot/jquery.flot.min.js"></script>
</head>
<body style="font: 9pt Tahoma">
    <?
	include_once("class.php");
	$datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
	$dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
	$timefrom = $_REQUEST['timefrom'];
	$timeto = $_REQUEST['timeto'];
	$tstimefrom = $datefrom ." ". $timefrom;
	$tstimeto = $dateto ." ". $timeto;
	$reqc=new Reports;
	
	echo "<div id=\"general\">
				<div id=\"title\">Общий отчет по расходу топлива(ДУТ)</div>
				<div id=\"box1\">
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td><span >Период с</span></td>
							<td><div  class=\"data\">$datefrom $timefrom</div></td>
							<td><span >по</span></td>
							<td><div  class=\"data\">$dateto $timeto</div></td>
						</tr>
					</table>
				
				</div>
				<div id=\"box2\">
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td width=200 align=\"right\"><span>Общий пробег</span></td>
							<td><div class=\"data1\" >-</div></td>
							<td width=80><span>км</span></td>
							<td width=150 align=\"right\"><span>Всего заправлено</span></td>
							<td><div class=\"data1\" \">-</div></td>
							<td width=80><span>л</span></td>
							
						</tr>
					</table>";
			
				echo "</div>
				<div id=\"box3\">
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td width=200 align=\"right\"><span>Общий расход топлива</span></td>
							<td><div class=\"data2\">-</div></td>
							<td width=80><span>л</span></td>
							<td width=150 align=\"right\"><span>Всего слито</span></td>
							<td><div class=\"data2\">-</div></td>
							<td width=80><span>л</span></td>
							<td width=350 align=\"right\"><span></span></td>
							<td><div></div></td>
							<td><span></span></td>
						</tr>
					</table>
					<table style=\"margin:10px 0 10px 0;\">
						<tr>
							<td width=200 align=\"right\"></td>
							<td width=100></td>
							<td width=80></td>
							<td width=150 align=\"right\"></td>
							<td width=100></td>
							<td width=80></td>
							<td width=350 align=\"right\"><span></span></td>
							<td><div></div></td>
							<td><span></span></td>
						</tr>
					</table>
				</div>
				<div id=\"box4\">
					<table>
						<tr>
							<th width=250 align=\"center\"><div class=\"data3\"><p style=\"margin:80px 0 0 0;\">Название транспортного средства</p></div></th>
							<th width=200><div class=\"data3\"><p style=\"margin:80px 0 0 0;\">ФИО водителя</div></p></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Пробег, км</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:70px 0 0 0;\">Топлива в начале периода, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:70px 0 0 0;\">Топлива в конце периода, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Кол-во заправок</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Кол-во сливов</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Всего заправлено, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Всего слито, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Общий расход, л</p></div></th>
							<th width=100><div class=\"data3\"><p class=\"rotate\" style=\"margin:80px 0 0 0;\">Расход топлива л/100км</p></div></th>
						</tr>";
	
	
	
	foreach ($_REQUEST['cars'] as $car => $value) {
		$car_info = $reqc->GetCarData($car);
		if($car_info['tank1']==true||$car_info['tank2']==true){
			$stops = $reqc->GetStops($tstimefrom,$tstimeto,$car);
			$dest = $reqc->GetSpeedDest($tstimefrom,$tstimeto,$car,0,0,1);
			$fuel = $reqc->GetFuelSE($tstimefrom,$tstimeto,$car,$car_info);
			$fuel2= $reqc->GetFuelUpDown($tstimefrom,$tstimeto,$car,$car_info);
			$fuel3= $reqc->GetAllUpDown($fuel2);
			if($dest['dest']<400){
				$fact = 0;
			}else $fact = $fuel['dut_start']+$fuel3['up']+$fuel3['down']-$fuel['dut_end'];
			if($fact<0)$fact=0;
			echo "<tr>
				<td width=250><div class=\"data4\">".$car_info['name']."</div></td>
				<td width=200><div class=\"data4\">".$car_info['fio']."</div></td>
				<td width=100><div class=\"data4\">".($dest['dest']/1000)."</div></td>
				<td width=100><div class=\"data4\">".round($fuel['dut_start'],2)."</div></td>
				<td width=100><div class=\"data4\">".round($fuel['dut_end'],2)."</div></td>
				<td width=100><div class=\"data4\">".$fuel2[count($fuel2)-1]['count_up']."</div></td>
				<td width=100><div class=\"data4\">".$fuel2[count($fuel2)-1]['count_down']."</div></td>
				<td width=100><div class=\"data4\">".round($fuel3['up'],2)."</div></td>
				<td width=100><div class=\"data4\">".round(($fuel3['down'])*-1,2)."</div></td>
				<td width=100><div class=\"data4\">".round($fact,2)."</div></td>
				<td width=100><div class=\"data4\">".round($fact*100000/$dest['dest'],2)."</div></td>
				</tr>";
		}
	}
	echo "
			</table>
		</div>
	</div>";
			
    ?>
</body>






