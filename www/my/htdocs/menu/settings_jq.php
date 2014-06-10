<?php
require_once "../config.php";
$sql="select fam,name,otch,show_direction,show_webnotify from users where userid=$userid";
$res=pg_query ($sql);
if (pg_num_rows($res)==1) {
extract (pg_fetch_array($res),EXTR_OVERWRITE);
if ($show_direction==1)$showdir="checked";else $showdir="";
if ($show_webnotify=='t')$shownotify="checked";else $shownotify="";
echo "<tr><td>$fam $name $otch</td></tr>";
echo "<tr><td><input type=button id='menu_settings_savemapcenter' onclick='menu_settings_save_map(this);' value='Установить текущую позицию карты по умолчанию'></td></tr>";
echo "<tr><td><input type=checkbox id='menu_settings_showarrow' name='menu_settings_showarrow' onclick='menu_settings_save(this);' $showdir> Показывать направление движения машин на карте</td></tr>";
echo "<tr><td><input type=checkbox id='menu_settings_shownotify' name='menu_settings_shownotify' onclick='menu_settings_save(this);' $shownotify> Показывать всплывающие уведомления</td></tr>";

}
?>
