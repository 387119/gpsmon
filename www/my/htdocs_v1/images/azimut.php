<?php
header('Content-Type:image/png');
//header('Content-Length: ' . filesize($file));

$azimut=$_GET['az'];
$red=$_GET['r'];if ($red==1)$png="./azimut_r.png";else $png="./azimut_l.png";
if (!is_numeric($azimut))$azimut=0;
$file=system("convert -rotate $azimut -background transparent $png -");
//strlen()
echo $file;
?>