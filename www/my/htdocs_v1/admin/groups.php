<script>
function addgroup(){
grpname=$('#grpname').val();
if (grpname.length == 0 ){
	alert ('Не введено названия группы');
	return false;
}
$.ajax({
	type:"GET",
	url:"addgroup.php?name="+grpname,
	dataType:'json',
	success: function(res){
		if (res.type=='success'){
			location.reload();
		}
		else{
			alert ('Ошибка добавления группы: '+res.error);
		}
	},
	error: function (){
		alert ('Ошибка добавления группы');
	}
});

}
function adddepartment(){
depname=$('#depname').val();
if (depname.length == 0 ){
	alert ('Не введено названия департамента');
	return false;
}
$.ajax({
	type:"GET",
	url:"adddepartment.php?name="+depname,
	dataType:'json',
	success: function(res){
		if (res.type=='success'){
			location.reload();
		}
		else{
			alert ('Ошибка добавления департамента: '+res.error);
		}
	},
	error: function (){
		alert ('Ошибка добавления департамента');
	}
});

}
</script>
<h1 style='color:red'>В процессе разработки</h1>
<?php
require_once "config.php";

echo "<div id='atab'>";

echo "<h5>Подразделения</h5>";
$sql="select departmentid,departmentname,(select count(*) from cars as t2 where t1.departmentid=t2.departmentid) as countcars from departments as t1 where clientid=(select clientid from users where userid=$userid) order by departmentname;";
$res1=pg_query ($connection,$sql);
echo "<select size=5 name='departments[]' style='width:280px;'>";
  while ($resf1=pg_fetch_array($res1)){
   extract ($resf1,EXTR_OVERWRITE);
   echo "<option value=$departmentid>$departmentname ($countcars)</option>";
}
echo "</select>";
echo "<br><input id=depname><input type=button id=newdep value='Новый' onclick=\"adddepartment();\">
	<input type=button value='Машины'>";

echo "<h5>Группы</h5>";
$sql="select groupid,groupname,(select count(*) from cars2groups as t2 where t1.groupid=t2.groupid) as countcars from groups as t1 where clientid=(select clientid from users where userid=$userid) order by groupname;";
$res1=pg_query ($connection,$sql);
echo "<select size=5 name='groups[]' style='width:280px;'>";
  while ($resf1=pg_fetch_array($res1)){
   extract ($resf1,EXTR_OVERWRITE);
   echo "<option value=$groupid>$groupname ($countcars)</option>";
}
echo "</select>";
echo "<br><input id=grpname><input type=button id=newgrp value='Новый' onclick='addgroup();'>
	<input type=button value='Машины'>";
echo "</div>";

?>
