<?php
/*echo "
<script>
    $(function(){
      $( '.tabs' ).tabs({
        cookie: { expires:1 }
      });
});
</script>
";
*/
include "./config.php";

$sql="select groupid,groupname,iconname from cfg.vicon_group;";
$res=pg_query($sql);
echo "<div class='tabs' id='icontabs'>
	<ul class='tabNavigation'>";
while ($resf=pg_fetch_array($res)){
	extract ($resf,EXTR_OVERWRITE);
	echo "<li><a class='' href='#icontab$groupid'><img src='/images/cars/$iconname' title='$groupname'></a></li>\n";
}
echo "</ul>\n";
$sql="select iconid,groupid,iconname from cfg.vicon;";
$res=pg_query($sql);
$groupid_old=-1;
while ($resf=pg_fetch_array($res)){
	extract ($resf,EXTR_OVERWRITE);
	if ($groupid_old!=$groupid){
		if ($groupid_old!=-1)echo "</div>\n";
		echo "<div id='icontab$groupid' style='padding:10px 10px 10px 10px;'>";
		$groupid_old=$groupid;
		
	}
	echo "<a href=\"javascript:setcaricon('$iconname');\"><img border=0 vspace=0 hspace=0 cellspacing=0 cellpadding=0 src=/images/cars/$iconname></a>";
}
echo "</div>\n";//закрываем тег последней группы
echo "</div>";
?>
