<?php
require_once("../config.php");
# проверяем входящие данные 
#<div class="message_info"><img src=/images/messages_info.png>Инфо</div>
#<div class="message_success"><img src=/images/messages_success.png>Успех</div>
#<div class="message_warning"><img src=/images/messages_warning.png>Внимание</div>
#<div class="message_error"><img src=/images/messages_error.png>Ошибка</div>
#<div class="message_validation"><img src=/images/messages_validation.png>Проверка</div>

$errorstr="";
#проверка списка машин для отчёта (дополнить проверкой из базы)
if ((!is_array($cars))or(count($cars)==0))$errorstr.="<div class=message_error>Требуется выбрать хотябы одну машину</div>";
#проверка типа отчёта
$sql="select * from settings where name='typereport' and value='$typereport';";
$res=pg_query ($sql);
if (pg_num_rows($res)!=1)$errorstr.="<div class=message_error>Неверный тип отчёта</div>";
#проверка формата отчёта
$sql="select * from settings where name='formatreport' and value='$formatreport';";
$res=pg_query ($sql);
if (pg_num_rows($res)!=1)$errorstr.="<div class=message_error>Неверный формат отчёта</div>";
#проверка диапазона дат
$datefrom_y=substr($datefrom,6,4);
$datefrom_m=substr($datefrom,3,2);
$datefrom_d=substr($datefrom,0,2);
$dateto_y=substr($dateto,6,4);
$dateto_m=substr($dateto,3,2);
$dateto_d=substr($dateto,0,2);
$sqldatefrom=$datefrom_y."-".$datefrom_m."-".$datefrom_d;
$sqldateto=$dateto_y."-".$dateto_m."-".$dateto_d;

if (!checkdate($datefrom_m,$datefrom_d,$datefrom_y) or ! checkdate($dateto_m,$dateto_d,$dateto_y))
  $errorstr.="<div class=message_error>Неверный формат дат</div>";
else{
 $datefrom_int=mktime (0,0,0,$datefrom_m,$datefrom_d,$datefrom_y);
 $dateto_int=mktime(23,59,59,$dateto_m,$dateto_d,$dateto_y);
 $date_raz=$dateto_int-$datefrom_int;
 if ($date_raz<0)$errorstr.="<div class=message_error>Дата начала формирования отчёта не может быть больше даты конца</div>";
}
# проверяем наличие файла который формирует отчёт
if($formatreport=="XLS"){
	$f1="./generators/".$typereport."_HTML.php";
	$check=1;
}

else {
	$f1="./generators/".$typereport."_".$formatreport.".php";
	$check=0;
}

if (!file_exists($f1))$errorstr.="<div class=message_validation>Не наден конструктор по выбранному типу и формату отчёта</div>";

?>
