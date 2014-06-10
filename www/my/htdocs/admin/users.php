<?php
require_once "config.php";
echo "<div id='atab'>
 <table>
    <thead>
      <tr><th>login</th><th>ФИО</th><th>доступ</th><th>Управление</th></tr>
    </thead>";

 $sql="select userid as uid,login,fam,name,otch,secure as secus from users as u1 where clientid in (select clientid from users as u2 where u2.userid=$userid) and secure<=$secure order by login;";

 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    echo "<tr><td>$login</td><td>$fam $name $otch</td><td>$secus</td><td><a href='javascript:userformedit($uid);'>изменить</a>&nbsp;<a href='javascript:usercar($uid);'>машины</a>&nbsp;
    <a href='javascript:changepasswd($uid,\"$login\");'>пароль</a>&nbsp;
<a href=\"javascript:delcar('user','пользователя',$uid);\">удалить</a></td></tr>";
  }

echo"</table></div>";
echo "<button id=newuser  onclick=\"userformnew();\">Новый пользователь</button>";
?>

