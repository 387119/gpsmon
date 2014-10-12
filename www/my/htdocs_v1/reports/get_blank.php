<?php
require_once("../config.php");

$cars=$_GET['cars'];
$typereport=$_GET['typereport'];
$formatreport=$_GET['formatreport'];
$datefrom=$_GET['datefrom'];
$dateto=$_GET['dateto'];
$timefrom=$_GET['timefrom'];
$timeto=$_GET['timeto'];


require_once ("./parcestr.php");
## проверки законченны подключаем обработчик или выводим ошибку
if (strlen($errorstr)>0)echo $errorstr;
else {
	if($check==0)include ($f1);
	else if($check==1){
		header("Content-type: application/vnd.ms-excel");
		header('Content-disposition: attachment; filename="report_' . date("Y-m-d") . '.xls"');
		include ($f1);
	}
	# подключаем соответсвующий файл
}
?>
