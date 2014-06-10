<?php
# Варианты отображения уведомлений
#<div class="message_info"><img src=/images/messages_info.png>Инфо</div>
#<div class="message_success"><img src=/images/messages_success.png>Успех</div>
#<div class="message_warning"><img src=/images/messages_warning.png>Внимание</div>
#<div class="message_error"><img src=/images/messages_error.png>Ошибка</div>
#<div class="message_validation"><img src=/images/messages_validation.png>Проверка</div>


require_once "config.php";
require_once "tmp.path.php";

echo "<html>
<head>
<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=no'  /> <meta http-equiv='content-type' content='text/html; charset=UTF-8' />
<title>GPSmon Отчёты</title>
<link rel='stylesheet' href='".$config['webroot']."/css/jquery-ui-1.8.21.custom.css' type='text/css'>
<link rel='stylesheet' href='../../themes/redmond/jquery.ui.all.css' type='text/css'>
<link rel='stylesheet' href='".$config['webroot']."/css/my.buttons.css' type='text/css'>
<link rel='stylesheet' href='".$config['webroot']."/css/my.tables.css' type='text/css'>
<link rel='stylesheet' href='".$config['webroot']."/css/my.messages.css' type='text/css'>

<script src='".$config['webroot']."/js/jquery-1.7.2.min.js' type='text/javascript'></script>
<script src='".$config['webroot']."/js/jquery.ui.core.js' type='text/javascript'></script>
<script src='".$config['webroot']."/js/jquery.ui.widget.js' type='text/javascript'></script>
<script src='".$config['webroot']."/js/jquery.ui.datepicker.js' type='text/javascript'></script>
    
<script>
    $(function() {
	    $('#datefrom,#dateto' ).datepicker();
});
    </script>";

#if ($secure>=0){ }
#else include_once "login/login_head.php";
?>
</head>
<?php
if ($secure>=0){
echo "<div><table class=table1>
<tr><td>Машины</td><td><button class=button1>Выбрать</button></td></tr>
<tr class=odd><td>Тип отчёта</td><td><select name=typereport><option value=1>Сводный отчёт</option><option value=2>Отчёт по ДУТ</option><option value=3>Отчёт по стоянкам</option><option value=4>Средний расход топлива</option></select></td></tr>
<tr><td>Формат отчёта</td><td><select name=formatreport><option value='html'>в html</option><option value='xls'>в xls</option></select></td></tr>
<tr class=odd><td>Дата</td><td>с <input id=datefrom> по <input id=dateto></td></tr>
</table></div>
<div>
<button class=button4>Загрузить отчёт</button>
</div>
";


}//body if secure>=0
?>

</body>
</html>
