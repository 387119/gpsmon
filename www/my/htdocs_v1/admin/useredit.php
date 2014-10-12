<?php
require_once "config.php";
# car tracker user
$t=$_GET['t'];
$uid=$_GET['u'];
$login=$_GET['login'];
$fam=$_GET['fam'];
$name=$_GET['name'];
$otch=$_GET['otch'];
$secure=$_GET['secure'];
$passwd=$_GET['passwd'];

$res="пользователь не найден $userid";
if (is_numeric($uid)){
if ($t=='s'){
if ($uid==-1){
//Создаём нового пользователя
$sql="
insert into logs (userid,text)values ($userid,'создан новый пользователь логин $login, $fam $name $otch, доступ $secure');
insert into users (login,fam,name,otch,password,secure,clientid) values ('$login','$fam','$name','$otch','$passwd',$secure,(select clientid from users where userid=$userid)) returning *;";
//echo $sql;
$res=pg_query($sql);
if (pg_num_rows($res)==1)$res=1;
 else $res="невозможно создать нового пользователя";
}
else{
// меняем параметры существующего пользователя
$sql="
insert into logs (userid,text)values ($userid,'изменение данных пользователя $login, номер $uid');
update users set login='$login',fam='$fam',name='$name',otch='$otch',secure=$secure where userid=$uid returning *;";
//echo $sql;
$res=pg_query($sql);
if (pg_num_rows($res)==1)$res=1;
 else $res="невозможно изменить данные пользователя";

}

echo $res;
}//t=s
if ($t=='g'){
// получить данные на пользователя
$sql="select login,fam,name,otch,secure from users where userid=$uid";
$req=pg_query($sql);
while ($resf=pg_fetch_array($req)){
 extract ($resf,EXTR_OVERWRITE);
 $arr=array('login'=>"$login",
 'fam'=>"$fam",
 'name'=>"$name",
 'otch'=>"$otch",
 'sec'=>"$secure");
}

$res=array('type'=>'success','regions'=>$arr);
print json_encode($res);
}
}
?>
