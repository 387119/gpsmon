<?php
require_once "config.php";
# car tracker user
$c=$_GET['c'];

if (is_numeric($c)){
// получить данныe по дутам за прошедший период
$sql="select min(dut[1]) as cmin1,max(dut[1]) as cmax1, min(dut[2]) as cmin2, max(dut[2])as cmax2 from data.gps where carid=$c and tstamp >=now()-interval '5 min' limit 1";
$req=pg_query($sql);
while ($resf=pg_fetch_array($req)){
 extract ($resf,EXTR_OVERWRITE);
 $arr=array('cmin1'=>"$cmin1",
 'cmax1'=>"$cmax1",
 'cmin2'=>"$cmin2",
 'cmax2'=>"$cmax2");
}

$res=array('type'=>'success','regions'=>$arr);
print json_encode($res);
}
?>
