<?php
require_once "./config.php";
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"  />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<script src="/jquery/js/jquery-1.7.2.min.js" type="text/javascript"></script>
<link type="text/css" href="/css/my.messages.css" rel="stylesheet" />
<?php
if ($secure>=50){

echo "
<script src=\"/jquery/js/jquery-ui-1.8.21.custom.min.js\"></script>
<script src=\"/js/jquery.cookie.js\"></script>
<link type=\"text/css\" href=\"/jquery/css/redmond/jquery-ui-1.8.21.custom.css\" rel=\"stylesheet\" />
<link type=\"text/css\" href=\"/css/my.tables.css\" rel=\"stylesheet\" />
<style>
body {
 background:url('/images/bottom_texture.jpg');
 padding:0;
 margin:0;
/* overflow: hidden;*/
}
.divtopgl{position:relative;left:0;top:0;bottom:0;right:0;background-attachment:fixed;background-image:url(/images/page_gl.png);background-repeat:no-repeat;background-position:top left;}
</style>
  <script>";
include_once("func.js");
echo "</script>
<body cellpadding=0 cellspacing=0><div class='divtopgl'>";
if ($secure>=90){
 if (is_numeric($_REQUEST['companyset'])){
  pg_query ("update users set clientid=".$_REQUEST['companyset']." where userid=$userid;");
 }
 echo "<form method=post action=/admin/admin.php><select id=companyset name=companyset>";
 $res=pg_query ("select company_name,clientid from clients order by company_name;");
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    echo "<option value='$clientid'><span style='font-size:40px;font-weight:bold;'>$company_name</span></option>";
  }
 echo "</select><input type=submit value='Сменить компанию'></form>";
}
$res=pg_query ("select company_name from clients where clientid in (select clientid from users where userid=$userid) limit 1;");
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    echo "<span style='font-size:40px;font-weight:bold;'>$company_name</span>";
  }

$res=pg_query ("select fam || ' '|| name || ' ' || otch as fio from users where userid=$userid");
$fio=pg_result($res,0,"fio");
echo "<div style='padding:0px 50px 0px 50px;height:100%;'>";
echo "<div align=right style='font-family:arial;font-size:11px;'> <b>$fio</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; административная консоль управления (<a href='/admin/admin.php?typelogin=exitlogin'>выход</a>)</div>";
echo "<div id='dialog' title='false'></div>";
echo "<div id='dialog_icon' title='false'></div>";
echo "<div class='tabs' id='tabs'>
<!-- Это сами вкладки -->
    <ul class='tabNavigation'>
     <li><a class='' href='#tabs-1'>Машины</a></li>
     <li><a class='' href='#tabs-2'>Трекеры</a></li>
     <li><a class='' href='#tabs-3'>Пользователи</a></li>
     <li><a class='' href='#tabs-4'>Настройки</a></li>
     <li><a class='' href='#tabs-5'>Логи</a></li>";
if ($secure>=90){echo "\n<li><a class='' href='#tabs-6'>Компании</a></li>";}
echo "</ul>
 <div id=\"tabs-1\">";
 include_once "cars.php";
echo " </div>
 <div id=\"tabs-2\">";
 include_once "trackers.php";
echo " </div>
 <div id=\"tabs-3\">";
 include_once "users.php";
echo "</div>
 <div id=\"tabs-4\">";
 include_once "settings.php";
echo "
 </div>
 <div id=\"tabs-5\">";
 include_once "logs.php";
echo "
 </div>";
if ($secure>=90){
 echo"<div id=\"tabs-6\">";
  include_once "company.php";
 echo "
 </div>";
}
echo"</div>
</div>
</body>
";

}
else {
include_once "./login.php";
}

?>



