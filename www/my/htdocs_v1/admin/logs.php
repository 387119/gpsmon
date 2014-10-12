<?php
require_once "config.php";
echo "<div id='atab'>
 <table>
    <thead>
      <tr><th>№</th><th>Админ</th><th>Время</th><th>Текст</th></tr>
    </thead>";

 $sql="
select logid,date_trunc('seconds',tstamp) as tstamp,(select login from users where users.userid=logs.userid) as user,text from logs 
where userid in (select userid from users where clientid = (select clientid from users where userid=$userid))
order by logid desc;";

 $res=pg_query ($sql);
  while ($resf=pg_fetch_array($res)){
   extract ($resf,EXTR_OVERWRITE);
    echo "<tr><td>$logid</td><td>$user</td><td>$tstamp</td><td>$text</td></tr>";
  }

echo"</table></div>";

?>

