<?php
require_once "config.php";
# car tracker user
$t=$_GET['t'];
$tid=$_GET['u'];
$car=$_GET['car'];if (strcmp($car,"")==0)$car="null";
$imei=$_GET['imei'];
$phone=$_GET['phone'];
$passwd=$_GET['passwd'];
$serialnum=$_GET['serialnum'];if (strcmp($serialnum,"")==0)$serialnum="null";

$res="неверный трекер";
if (is_numeric($tid)){
if ($t=='s'){
$res1=pg_query("select * from trackers where carid=$car and trackerid!=$tid;");
if (pg_num_rows($res1)>0)$res="к выбранной машине уже прикреплён один трекер, в данной версии ПО нельзя устанавливать более одного трекера на машину, выберете другую машину или предварительно освободите нужную машину от трекера";
else{
if ($tid==-1){
//Создаём новый трекер
$sql="
insert into logs (userid,text)values ($userid,'создан новый трекер, imei:$imei, phone:$phone, car:$car');
insert into trackers (carid,imei,phone,passwd,serialnum,clientid) values ($car,'$imei','$phone','$passwd',$serialnum,(select clientid from users where userid=$userid)) returning *;";
//echo $sql;
$res1=pg_query($sql);
if (pg_num_rows($res1)==1)$res=1;
 else $res="невозможно создать новый трекер";
}
else{
// меняем параметры существующего трекера
$sql="
insert into logs (userid,text)values ($userid,'изменение данных трекера №$tid, car:$car, imei:$imei, phone:$phone, pass:$passwd');
update data.online set trackerid=$tid where carid=$car;
update trackers set imei='$imei',carid=$car,phone='$phone',passwd='$passwd',serialnum=$serialnum where trackerid=$tid returning *;";
//echo $sql;
$res1=pg_query($sql);
if (pg_num_rows($res1)==1)$res=1;
 else $res="невозможно изменить данные трекера";

}
}
echo $res;
}//t=s

if ($t=='g'){
// получить данные трекера
$sql="select carid,imei,phone,passwd,serialnum from trackers where trackerid=$tid";
$req=pg_query($sql);
while ($resf=pg_fetch_array($req)){
 extract ($resf,EXTR_OVERWRITE);
 $arr=array('carid'=>"$carid",
 'imei'=>"$imei",
 'phone'=>"$phone",
 'passwd'=>"$passwd",
 'serialnum'=>"$serialnum");
}

$res=array('type'=>'success','regions'=>$arr);
print json_encode($res);
}
}
?>
