<?
	include("../../config.php");
	include("class.php");
	$datefrom = $_GET['datefrom'];
	$dateto = $_GET['dateto'];
	$timefrom = $_GET['timefrom'];
	$timeto = $_GET['timeto'];
	include('/var/www/winet/gpsmod/htdocs/PHPExcel/PHPExcel.php');
	include('/var/www/winet/gpsmod/htdocs/PHPExcel/PHPExcel/Writer/Excel2007.php');
	include('/var/www/winet/gpsmod/htdocs/PHPExcel/PHPExcel/IOFactory.php');
	$row = 3; // текущая строка
	$excel = new PHPExcel;
	$excel->getProperties()->setKeywords("office 2007 openxml php");
	
	$datefrom = date('Y-m-d', strtotime($datefrom));
	$dateto = date('Y-m-d', strtotime($dateto));
	
	$excel->setActiveSheetIndex(0);
	$excel->getDefaultStyle()->getFont()->setSize(12);
	$excel->getActiveSheet()->getStyle('A1:L2')->getFont()->setBold(true);
	$excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
	
	$excel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);
	
	$excel->getActiveSheet()->setCellValue('A1', "Сводный отчет за период с $datefrom $timefrom по $dateto $timeto");
	
	$excel->getActiveSheet()
		->setCellValue('A2', '№ п/п')
		->setCellValue('B2', 'Тип ТС')
		->setCellValue('C2', 'Госномер')
		->setCellValue('D2', 'Время движения')
		->setCellValue('E2', 'Время стоянки')
		->setCellValue('F2', 'Средняя скорость, км/ч')
		->setCellValue('G2', 'Максимальная скорость, км/ч')
		->setCellValue('H2', 'Пробег, км')
		->setCellValue('I2', 'Количество заправок');

	//----------------------------
	$datefrom = date('Y-m-d', strtotime($_REQUEST['datefrom']));
	$dateto = date('Y-m-d', strtotime($_REQUEST['dateto']));
	$timefrom = $_REQUEST['timefrom'];
	$timeto = $_REQUEST['timeto'];
	$tstampfrom=$datefrom."T".$timefrom;
	$tstampto=$dateto."T".$timeto;
	$reqc=new Reports;
	
	foreach ($_REQUEST['cars'] as $car => $value) {
		$resc1=$reqc->GetCarData($car);
		print_r($resc1);
		$resc2=$reqc->GetTimeMoveStop($tstampfrom,$tstampto,$car);
		$resc3=$reqc->GetSpeedDest($tstampfrom,$tstampto,$car,1,1,1);
		//$resc4=$reqc->GetFuelUpDown($tstampfrom,$tstampto,$car,$resc1);
		$excel->getActiveSheet()
			->setCellValue("A$row", $row - 2 )
			->setCellValue("B$row", $resc1['name'])
			->setCellValue("C$row", $resc1['gosnum'])
			->setCellValue("D$row", $reqc->ConvertTimeToString($resc2['move']))
			->setCellValue("E$row", $reqc->ConvertTimeToString($resc2['stop']))
			->setCellValue("F$row", round($resc3['speed_avg'],1))
			->setCellValue("G$row", $resc3['speed_max'])
			->setCellValue("H$row", ($resc3['dest']/1000));
		if(!$tank1_installed || !$tank2_installed) {
			$excel->getActiveSheet()->setCellValue("I$row","Нет настроек по ДУТам");
		} 
		else{
			$excel->getActiveSheet()->setCellValue("I$row", "Нет настроек по ДУТам");
		}
		$row++;
	}
	
	//--------------------------------------        
	
	ob_end_clean();
	header('Content-Type:application/ms-excel');
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate"); 
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="Report.xlsx"');
	
	$excel_2007 = new PHPExcel_Writer_Excel2007($excel);
	ob_end_clean();
	$excel_2007->save('php://output');
?>
