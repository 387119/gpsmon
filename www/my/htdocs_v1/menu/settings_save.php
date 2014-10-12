<?php
require_once ("../config.php");
$ID=$_GET['id'];
$VALUE=$_GET['value'];
$result="Ошибка входных данных";
$sql="";

if (strcmp($ID,"menu_settings_showarrow")==0){
 if (strcmp($VALUE,"true")==0)$set="1";else $set="0";
 $sql="update users set show_direction=$set where userid=$userid returning *";
}

if (strcmp($ID,"menu_settings_savemapcenter")==0){
 $arr=split(',',$VALUE);
 $sql="update users set map_center_lat=".($arr[0]*600000).",map_center_lon=".($arr[1]*600000).",map_center_zoom=".$arr[2]." where userid=$userid returning *";
}

if (strcmp($ID,"menu_settings_shownotify")==0){
 if (strcmp($VALUE,"true")==0)$set="true";else $set="false";
 $sql="update users set show_webnotify=$set where userid=$userid returning *";
}

if (strcmp($sql,"")!=0){
 $res=pg_query ($sql);
 if (pg_num_rows($res)!=1)$result="ошибка обновления данных в базе";
 else $result="ok";
}
echo $result;
?>
