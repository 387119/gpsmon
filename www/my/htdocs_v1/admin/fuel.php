<?
    require_once ("./config.php");
    $car=$_GET["car"];
    $dstart=$_GET["start"];
    $dend=$_GET["end"];
    $tstart=$_GET["starttime"];
    $tend=$_GET["endtime"];
    $mode=$_GET["mode"];
    $toexel=$_GET["toexel"];
    $a=$_GET["a"];
    $el=$_GET["el"];


    $dsf=substr($dstart,6,4)."-".substr($dstart,3,2)."-".substr($dstart,0,2);
    $dst=substr($dend,6,4)."-".substr($dend,3,2)."-".substr($dend,0,2);


    $query = "SELECT tstamp, dut[1] dut1, dut[2] dut2, date_part('epoch', tstamp) epoch, speed, lat/600000::real as lat,lon/600000::real as lon FROM data.gps WHERE carid=$car AND tstamp>'$dsf $tstart' AND tstamp<'$dst $tend' ORDER BY tstamp ASC";
    $result = pg_query($connection, $query);// or die("Error in query: $query. " .pg_last_error($connection));
    if (pg_num_rows($result) > 0) {
        $result_array = pg_fetch_all($result);
    }

    $dut1_arr = array();
    $dut2_arr = array();
    $dut1_liter_prev = false;
    $dut2_liter_prev = false;
    $dut1_min = 500;
    $dut1_max = 1500;
    $dut1_litr = 300;

    $filling_start = false;
    $num = 0; // общий литраж заправки
    foreach ($result_array as $i=>$track) {
        if ($track['dut1'] >= $dut1_min && $track['dut1'] <= $dut1_max) { // если значения частоты валидны
            $dut1_herz_percent = ($track['dut1'] - $dut1_min) * 100 / ($dut1_max - $dut1_min); // процент от общего количества герц
            $dut1_liter = intval($dut1_herz_percent * $dut1_litr / 100); // текущий показатель расхода топлива в литрах

            // if (strtotime(date('H:i:s', $track['epoch'])) > strtotime('23:59:30')) {
            //     echo date('H:i:s', $track['epoch']).' - '.$dut1_liter.'<br>';
            // }

            if ($dut1_liter_prev && $dut1_liter > $dut1_liter_prev && $track['speed'] < 5 && $dut1_liter - $dut1_liter_prev > 10) { // если текущий показатель расхода топлива больше предыдущего
                if (!$filling_start) {
                    $num = $dut1_liter_prev;
                }
                $filling_start = true; // начало заправки
                $dut1_liter_prev = $dut1_liter;
            } else { 
                if ($filling_start && $dut1_liter - $num > 10) { // если происходила заправка
                    $c = count($dut1_arr);
                    $dut1_arr[$c]['tstamp'] = $track['tstamp'];
                    $dut1_arr[$c]['lat'] = $track['lat'];
                    $dut1_arr[$c]['lon'] = $track['lon'];
                    $dut1_arr[$c]['num'] = $dut1_liter - $num;
// echo $num . ' - ' . $dut1_liter;
                    $filling_start = false;
                }
                $dut1_liter_prev = $dut1_liter;
            }
        }    
    }

    echo 'Количество заправок: '.count($dut1_arr).'<br /><br />';
    foreach ($dut1_arr as $fill) {
        echo 'Заправка в '.$fill['tstamp'].'<br />';
        $reverse_geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='.($fill['lat']).','.($fill['lon']).'&sensor=false&language=ru');
        $reverse_geo = json_decode($reverse_geo, true);
        echo 'Место заправки: '.$reverse_geo[results][0][formatted_address].'<br />';
        echo 'Заправка, л: '.$fill['num'].'<br /><br />';
            
    }

?>