<?php
require_once("../config.php");
$res=pg_query("select show_direction from users where userid=$userid");
if (pg_num_rows($res)==1)$direction=pg_result($res,0,"show_direction");
header('Content-Type:image/png');
//header('Content-Length: ' . filesize($file));

$azimut=$_GET['az'];
$car=$_GET['carid'];
$icon=$_GET['icon'];
$speed=$_GET['spd'];

$res=pg_query ("select gsmsignal,
       case
         when (now()-tstamp>interval '3 hour') then 1
         else 0
         end as tstamp_st
                                         
 from data.online where carid=$car");
$gsmsignal=pg_result($res,0,"gsmsignal");
$tstamp_st=pg_result($res,0,"tstamp_st");
if (($gsmsignal<3)or($tstamp_st==1))$errorstr="convert -page +0+0 - -page +34+32 ./car_error_s.png -background transparent -layers merge -extent 50x50 +repage - |";
else $errorstr="";

if (!is_numeric($azimut))$azimut=0;
if (!is_file("./cars/$icon"))$icon="car1.png";
if (!is_numeric($speed))$speed=0;
//$file=system("cat ./cars/car$car.png");//50x31
//$file=system("convert -page +0+0 ./cars/car$car.png -layers merge -extent 50x45 -background red +repage -");
if ($speed>0)
 if ($direction==1)
  $file=system("cat ./azimuts.png |\
  convert -rotate $azimut -background transparent - PNG:-|\
  convert -page +0+0 ./cars/$icon  -page +16+32 - -background transparent -layers merge -extent 50x50 +repage -|$errorstr cat");
 else 
  $file=system("convert -page +0+0 ./cars/$icon -background transparent -layers merge -extent 50x50 +repage -|$errorstr cat");
else
 $file=system("convert -page +0+0 ./cars/$icon -background transparent -layers merge -extent 50x50 +repage -|$errorstr cat");


system("rm -f /tmp/gpsmod_azimut_$uniq.png");
//strlen()
echo $file;
?>