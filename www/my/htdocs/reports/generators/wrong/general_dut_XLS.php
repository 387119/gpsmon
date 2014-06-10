<?
    include("../../config.php");
    
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

    $excel->getActiveSheet()->setCellValue('A1', "Отчет в стадии разработки");
/*
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
        // ->setCellValue('J2', 'Место заправки')
        // ->setCellValue('K2', 'Заправка, м3');

    foreach ($_REQUEST['cars'] as $car => $value) {
        $sql = "select gosnum, name, dutmin[1] dut1_min, dutmax[1] dut1_max, dutlitr[1] dut1_litr, dutmin[2] dut2_min, dutmax[2] dut2_max,dutlitr[2] dut2_litr from cars where carid=$car;";
        $res = pg_query($sql);
        extract (pg_fetch_array($res), EXTR_OVERWRITE);
        
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

        $sql = "select date_part('epoch',tstamp) epoch, dut[1] dut1, dut[2] dut2, dest, lat/600000::real as lat, lon/600000::real as lon, speed, dest, tstamp::date as tstamp, tstamp::timestamp without time zone as tstamp_all from data.gps where carid=$car and tstamp >= '$datefrom $timefrom' and tstamp <= '$dateto $timeto' and lat>0 and lon>0 order by epoch";
        $res = pg_query($sql);

        $speed_average = 0; // средняя скорость
        $speed_max = 0; // максимальная скорость
        $speed_count = 0; // количество треков скорости для определения средней скорости

        $move_time = 0; // время движения
        $stop_time = 0; // время стоянки

        $distance = 0; // пройденное расстояние
        $epoch_prev = false; // время предыдущего трека

        $dut1_fills = array();
        $dut1_fills_count = 0; // количество заправок
        // $dut2_arr = array();
        $dut1_liter_prev = false;
        // $dut2_liter_prev = false;
        $filling_start = false; // флаг начала заправки
        $dut1_liter_filling_start = 0; // объем бака на момент начала заправки

        while ($track = pg_fetch_array($res)) {
            extract($track, EXTR_OVERWRITE);

            if ($speed > 0) {
                if ($speed > $speed_max) {
                    $speed_max = $speed; // определение максимальной скорости
                }
                $speed_count++;
                $speed_average += $speed; // определение средней скорости
                if ($epoch_prev) {
                    $move_time += $epoch - $epoch_prev; // определение времени движения
                } 
            } else {
                if ($epoch_prev) {
                    $stop_time += $epoch - $epoch_prev; // определение времени стоянки
                } 
            }
            if ($speed>5)$distance += $dest; // определение пройденного расстояния
            $epoch_prev = $epoch;

            // определение количества и объема заправок
            if ($tank1_installed && $dut1 >= $dut1_min && $dut1 <= $dut1_max) { // если значения частоты валидны и ДУТ установлен
                $dut1_herz_percent = ($dut1 - $dut1_min) * 100 / ($dut1_max - $dut1_min); // процент от общего количества герц
                $dut1_liter = intval($dut1_herz_percent * $dut1_litr / 100); // текущий показатель расхода топлива в литрах
                if ($dut1_liter_prev && $dut1_liter > $dut1_liter_prev && $speed < 5 && $dut1_liter - $dut1_liter_prev > 5) { // если текущий показатель расхода топлива больше предыдущего
                    if (!$filling_start) {
                        $dut1_liter_filling_start = $dut1_liter_prev;
                    }
                    $filling_start = true; // начало заправки
                } else { 
                    if ($filling_start) { // если происходила заправка
                        if ($dut1_liter - $dut1_liter_filling_start > 20) { // если объем заправки превысил 20 литров
                            $dut1_fills_count++;
                            // $c = count($dut1_fills);
                            // $dut1_fills[$c]['num'] = $dut1_liter_prev - $dut1_liter_filling_start;
                            // $dut1_fills[$c]['lat'] = $lat;
                            // $dut1_fills[$c]['lon'] = $lon;
                            // $reverse_geo = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lon&sensor=false&language=ru");
                            // $reverse_geo = json_decode($reverse_geo, true);
                            // $dut1_fills[$c]['place'] = $reverse_geo[results][0][formatted_address];
                        }
                        $filling_start = false;
                    }
                }
                $dut1_liter_prev = $dut1_liter;
            }
        }
        $speed_average = round($speed_average / $speed_count, 2);

        // пересчет секунд в часы и минуты
        $h = floor($move_time / 3600);
        $m = floor(($move_time - $h * 3600) / 60);
        $move_time = ($h > 0 ? $m > 0 ? "$h ч. $m мин." : "$h ч." : "$m мин.");        

        // пересчет секунд в часы и минуты
        $h = floor($stop_time / 3600);
        $m = floor(($stop_time - $h * 3600) / 60);
        $stop_time = ($h > 0 ? $m > 0 ? "$h ч. $m мин." : "$h ч." : "$m мин.");        

        
        $excel->getActiveSheet()
            ->setCellValue("A$row", $row - 2)
            ->setCellValue("B$row", $name)
            ->setCellValue("C$row", $gosnum)
            ->setCellValue("D$row", $move_time)
            ->setCellValue("E$row", $stop_time)
            ->setCellValue("F$row", "$speed_average")
            ->setCellValue("G$row", "$speed_max")
            ->setCellValue("H$row", round($distance / 1000, 3));
        if (!$tank1_installed || !$tank2_installed) {
            $excel->getActiveSheet()->setCellValue("I$row","Нет настроек по ДУТам");
        } else {
            // $fills_places = '';
            // $fills_nums = '';
            // foreach ($dut1_fills as $i=>$fill) {
                // $fills_places = $fills_places.($i > 0 ? "\n" : '').$fill['place'];
                // $fills_nums = $fills_nums.($i > 0 ? "\n" : '').$fill['num'];
            // }
            $excel->getActiveSheet()
                ->setCellValue("I$row", $dut1_fills_count);
                // ->setCellValue("I$row", count($dut1_fills))
                // ->setCellValue("J$row", $fills_places)
                // ->setCellValue("K$row", $fills_nums);
            // $excel->getActiveSheet()->getStyle("J$row")->getAlignment()->setWrapText(true);
            // $excel->getActiveSheet()->getStyle("K$row")->getAlignment()->setWrapText(true);
        }
        // $excel->getActiveSheet()->getColumnDimension('J')->setWidth(70);
        $row++;
    }

*/
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
