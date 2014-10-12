<?php
require_once("../config.php");

$cars=$_POST['cars'];
$typereport=$_POST['typereport'];
$formatreport=$_POST['formatreport'];
$datefrom=$_POST['datefrom'];
$dateto=$_POST['dateto'];
$timefrom=$_POST['timefrom'];
$timeto=$_POST['timeto'];


require_once ("./parcestr.php");
## проверки законченны подключаем обработчик или выводим ошибку
if (strlen($errorstr)>0)echo $errorstr;
else {
include ($f1);
# подключаем соответсвующий файл
}
?>
