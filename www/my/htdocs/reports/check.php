<?php
require_once("../config.php");

$cars=$_POST['cars'];
$typereport=$_POST['typereport'];
$formatreport=$_POST['formatreport'];
$datefrom=$_POST['datefrom'];
$dateto=$_POST['dateto'];
$timefrom=$_POST['timefrom'];
$timeto=$_POST['timeto'];
$number_of_report=$_POST['number_of_report'];

require_once ("./parcestr.php");
if (strlen($errorstr)>0)echo $errorstr;
else {
$carsstr="";
while (list($key,$val)=each($cars)){
 $carsstr.="cars%5B$val%5D=on&";
}
$chk=substr($formatreport,0,1);

if (strcmp($chk,"a")==0)
 echo "<div class=message_success>Данные успешно проверены, для загрузки отчёта №$number_of_report нажмите ссылку <a href='javascript:menu_reports_shownewreport();'>показать отчёт</a></div>";
else 
 echo "<div class=message_success>Данные успешно проверены, для загрузки отчёта №$number_of_report нажмите ссылку <a href='reports/get_blank.php?".$carsstr."typereport=$typereport&formatreport=$formatreport&datefrom=$datefrom&dateto=$dateto&timefrom=$timefrom&timeto=$timeto' target=_blank>загрузить отчёт</a></div>";
}
?>
