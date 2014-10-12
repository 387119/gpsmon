<?php
#
#writen by Jonny Gyl, email jonny@winet.in.ua
$config['webroot']="/";

require_once ("func.php");
error_reporting (E_ERROR);
$secure=-1;
$curtime=localtime(time());
$domain="my.gpsmon.org";
$dbhost="db";
$db="gpsmon";
$user="gpsmon";
$pass="wwUxphhPMEXHXg1H";
$cookie_only_ssl=1;
$errshow="";

$connection = pg_connect ("host=$dbhost dbname=$db user=$user password=$pass");
if (!$connection){die("Could not open connection to database server");}

#Обработка входящих переменных

$logintype=$_REQUEST['typelogin'];
if (strcmp($logintype,"")==0){$logintype=$_GET['typelogin'];}
$login=$_REQUEST['login'];
$passwd=$_REQUEST['passwd'];

$phpses=$_COOKIE['phpses'];
//print_r ($_COOKIE);
//echo $phpses;
#$result=pg_query("select us.nick,ws.session from web.sessions as ws, accounts.users as us where nick='jonny' and us.userid=ws.userid and datedrop >CURRENT_TIMESTAMP");

// Выход из сайта
###############################
if (strcmp($logintype,"exitlogin")==0)
{
 pg_query ("delete from sessions where session='$phpses' and datedrop >CURRENT_TIMESTAMP");
 $phpses="";
}
// Авторизация на сайте
if(strcmp($logintype,"newlogin")==0)
 {
  $result=pg_query("select secure,userid,login as nick from users where login='$login' and password='$passwd';");
  $rows=pg_num_rows($result);
  if ($rows==1)
   {
    $secure=pg_result($result,0,"secure");
    $userid=pg_result($result,0,"userid");
    $nick=pg_result($result,0,"nick");
    if ($secure>=0)
    {
     $date=date("c",time()+604800);
     do{

     $sql="select round(random()*100000000) into temporary ttt;
		      select * into temporary ttt1 from ttt where round::varchar not in (select session from sessions);
		      create temporary table ttt2(userid integer,typeses integer, session varchar, datedrop timestamp with time zone);
		      insert into ttt2 (userid,typeses,datedrop) values ($userid,1,'$date');
		      update ttt2 set session=(select round from ttt1);
		      insert into sessions (select * from ttt2 limit 1) returning *;";
     $result=pg_query($sql);
     }while (pg_num_rows($result)!=1);
     $phpses=pg_result($result,0,"session");
     setcookie("phpses","$phpses",time()+604800,"/",$domain,$cookie_only_ssl);
    }
  }
  else {
   $errshow="<div class='message_error'>Логин или пароль указаны неверно.</div>";
  }
 }
else
 {// если уже авторизированны то просто проверяем сесии
  $result=pg_query("select login as nick,ws.session,us.secure,us.userid from sessions as ws, users as us 
		    where ws.session='$phpses' and us.userid=ws.userid and datedrop >CURRENT_TIMESTAMP");
  $rows=pg_num_rows($result);
  if ($rows!=1){pg_query ("delete from sessions where session='$phpses' and datedrop >CURRENT_TIMESTAMP");$phpses="";}else{
  $nick=pg_result($result,0,"nick");
  $userid=pg_result($result,0,"userid");
  $secure=pg_result($result,0,"secure");
//  echo $secure."--";
  if ($secure<0){pg_query ("delete from sessions where session='$phpses' and datedrop >CURRENT_TIMESTAMP");$phpses="";}
 }}

?>
