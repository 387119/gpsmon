<?php
require_once "config.php";
# car tracker user
$t=$_GET['t'];
$cid=$_GET['u'];
$cname=$_GET['name'];
$cgosnum=$_GET['gosnum'];
$cicon=$_GET['icon'];
$ctracker=$_GET['tracker'];if (strcmp($ctracker,"")==0)$ctracker="null";
$fiodriver1=$_GET['fiodriver1'];if (strcmp($fiodriver1,"")==0)$fiodriver1="null";else $fiodriver1="'$fiodriver1'";
$teldriver1=$_GET['teldriver1'];if (strcmp($teldriver1,"")==0)$teldriver1="null";else $teldriver1="'$teldriver1'";
$cmaxspeed=$_GET['maxspeed'];if (!is_numeric($cmaxspeed))$cmaxspeed="null";
$cdutlitr1=$_GET['dutlitr1'];if (!is_numeric($cdutlitr1))$cdutlitr1="null";
$cdutmin1=$_GET['dutmin1'];if (!is_numeric($cdutmin1))$cdutmin1="null";
$cdutmax1=$_GET['dutmax1'];if (!is_numeric($cdutmax1))$cdutmax1="null";
$cdutlitr2=$_GET['dutlitr2'];if (!is_numeric($cdutlitr2))$cdutlitr2="null";
$cdutmin2=$_GET['dutmin2'];if (!is_numeric($cdutmin2))$cdutmin2="null";
$cdutmax2=$_GET['dutmax2'];if (!is_numeric($cdutmax2))$cdutmax2="null";
$deadzone1=$_GET['deadzone1'];if (!is_numeric($deadzone1))$deadzone1="0";if($cdutlitr1=="null"&&$cdutmin1=="null"&&$cdutmax1=="null")$deadzone1="null";
$deadzone2=$_GET['deadzone2'];if (!is_numeric($deadzone2))$deadzone2="0";if($cdutlitr2=="null"&&$cdutmin2=="null"&&$cdutmax2=="null")$deadzone2="null";
$res="неверная машина";

if (is_numeric($cid)){
if ($t=='s'){
$res1=pg_query("select * from trackers where trackerid=$ctracker and trackerid is not null and carid!=$cid");
if (pg_num_rows($res1)>0)$res="выбранный трекер уже прикреплён к одной из машин, в данной версии ПО нельзя устанавливать более одного трекера на машину, выберете другой трекер или предварительно освободите нужный";
else{

if ($cid==-1){
//Создаём новую машину
//
$sql="
select max(carid) as max1 into temporary tmp1 from cars;
insert into logs (userid,text)values ($userid,'создана новая машина, name:$cname, gosnum:$cgosnum, tracker:$tracker');
insert into cars (name,gosnum,icon,dutlitr,dutmin,dutmax,clientid,maxspeed,fiodriver1,teldriver1,deadzone) values ('$cname','$cgosnum','$cicon','".'{'."$cdutlitr1,$cdutlitr2".'}'."','".'{'."$cdutmin1,$cdutmin2".'}'."','".'{'."$cdutmax1,$cdutmax2".'}'."',(select clientid from users where userid=$userid),$cmaxspeed,$fiodriver1,$teldriver1,'".'{'."$deadzone1,$deadzone2".'}'."');
select max(carid) as max2 into temporary tmp2 from cars;
insert into data.online (carid,trackerid,tstamp,lat,lon,speed) values ((select max2 from tmp2), $ctracker,'1980-01-01 0:0:0',0,0,0);
update trackers set carid=(select max2 from tmp2) where trackerid=$ctracker and trackerid is not null;
select max2-max1 as max from tmp1,tmp2;";
//echo $sql;
$res1=pg_query($sql);
extract(pg_fetch_array($res1),EXTR_OVERWRITE);
if ($max==1)$res=1;
 else $res="невозможно создать новую машину";

}
else{
// меняем параметры существующей машины
$sql="
insert into logs (userid,text)values ($userid,'изменение данных машины №$cid, name:$cname, gosnum:$cgosnum, tracker:$ctracker');
update trackers set carid=null where carid=$cid;
update trackers set carid=$cid where trackerid=$ctracker and trackerid is not null;
update data.online set trackerid=$ctracker where carid=$cid;
update cars set fiodriver1=$fiodriver1,teldriver1=$teldriver1,name='$cname',gosnum='$cgosnum',icon='$cicon',maxspeed=$cmaxspeed,dutlitr[1]=$cdutlitr1,dutmin[1]=$cdutmin1,dutmax[1]=$cdutmax1,dutlitr[2]=$cdutlitr2,dutmin[2]=$cdutmin2,dutmax[2]=$cdutmax2,deadzone[1]=$deadzone1, deadzone[2]=$deadzone2 where carid=$cid returning *;";
//echo $sql;
$res1=pg_query($sql);
if (pg_num_rows($res1)==1)$res=1;
 else $res="невозможно изменить данные машины";

}
}
echo $res;
}//t=s
if ($t=='g'){
// получить данные машины
$sql="select name,gosnum,icon,fiodriver1,teldriver1,maxspeed,dutlitr[1] as dutlitr1,dutmin[1] as dutmin1,dutmax[1] as dutmax1, dutlitr[2] as dutlitr2,dutmin[2] as dutmin2,dutmax[2] as dutmax2,deadzone[1] as deadzone1, deadzone[2] as deadzone2, (select trackerid from trackers where carid=$cid) as trackerid from cars where carid=$cid";
//echo $sql;
$req=pg_query($sql);
while ($resf=pg_fetch_array($req)){
 extract ($resf,EXTR_OVERWRITE);
 $arr=array('name'=>"$name",
 'gosnum'=>"$gosnum",
 'icon'=>"$icon",
 'tracker'=>"$trackerid",
 'maxspeed'=>"$maxspeed",
 'dutlitr1'=>"$dutlitr1",
 'dutmin1'=>"$dutmin1",
 'dutmax1'=>"$dutmax1",
 'dutlitr2'=>"$dutlitr2",
 'dutmin2'=>"$dutmin2",
 'dutmax2'=>"$dutmax2",
 'fiodriver1'=>"$fiodriver1",
 'teldriver1'=>"$teldriver1",
 'deadzone1'=>"$deadzone1",
 'deadzone2'=>"$deadzone2" );
}

$res=array('type'=>'success','regions'=>$arr);
print json_encode($res);

}
}

?>
