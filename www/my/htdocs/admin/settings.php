<?php
require_once "config.php";
echo "<div id='atab'>";
if ($_POST['saveset']==1){
$res=0;
 $client=$_POST['client'];
 while(list($id,$data)=each($client)){
  $res=pg_query("insert into logs (userid,text) values ($userid,'смена названия предприятия с ' || (select company_name from clients where clientid=$id)||' на $data');
		update clients set company_name='$data' where clientid=$id returning *;");
  if (pg_num_rows($res)==1)$res=1;
  else $res=0;
 }
 
if ($res==1) echo "<div>Данные успешно обновлены</div>";
else echo "Ошибка обновления данных";
}
echo"<form method=post><input type=hidden name=saveset value=1><table>";

$sql="select clientid,company_name as name from clients where clientid in (select clientid from users where userid=$userid);";

 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
   if ($show_direction==1)$show_dir="checked"; else $show_dir="";
   echo "<tr><td align=right>Название предприятия:</td><td align=left><input name=client[$clientid] value='$name'></td></tr>";
  }
echo "</table>";
echo "<input type=submit id=newcar value='Сохранить'></form></div>";

?>
