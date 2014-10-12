<?php include_once "../config.php"; 
$date_now=date("d.m.Y");
?>
<script>
var number_of_report=0;
$(function(){
				    $('#divreports').dialog({modal:false,autoOpen:false});
				    $('#divreports').dialog('option','height',360);
			    	    $('#divreports').dialog('option','width',450);
				    $('#report_datefrom,#report_dateto' ).datepicker({ gotoCurrent: true });
				    $('#report_datefrom,#report_dateto' ).datepicker( 'option', 'dateFormat', 'dd.mm.yy' );
				    $('#divreports_selectcar').dialog({modal:true,autoOpen:false});
				    $('#divreports_selectcar').dialog('option','height',400);
				    $('#divreports_selectcar').dialog('option','width',400);
				    $('#table_reports_selectcar').tablesorter();
				    $('#table_reports_selectcar').liveFilter('fade');
		});
function menu_reports_selectcar_cball(status) {
 $(".cb_selectcar").each( function() {
  $(this).attr("checked",status);
})
};

function menu_reports_show (){
 $('#divreports').dialog('open');
};
function menu_reports_selectcar(){
  $('#divreports_selectcar').dialog('open');
};

function getpost(){
var i=0;
var res = [];
$('.cb:checked').each(function(){
 res[i] = $(this).attr('id');
 i++;
});
return res;
}

function menu_reports_checkform(){
var cars=getpost();
var typereport=$('#typereport option:selected').val();
var formatreport=$('#formatreport option:selected').val();
var datefrom=$('#report_datefrom').val();
var dateto=$('#report_dateto').val();
var timefrom=$('#report_timefrom').val();
var timeto=$('#report_timeto').val();
  $('#repdev').load('reports/check.php',{cars: cars,typereport: typereport,formatreport: formatreport,datefrom:datefrom,dateto:dateto,timefrom:timefrom,timeto:timeto,number_of_report:number_of_report});
}

function menu_reports_shownewreport(){
 var cars=getpost();
 var typereport=$('#typereport option:selected').val();
 var formatreport=$('#formatreport option:selected').val();
 var datefrom=$('#report_datefrom').val();
 var dateto=$('#report_dateto').val();
 var timefrom=$('#report_timefrom').val();
 var timeto=$('#report_timeto').val();
 var divtmp=$('#repsshow').html();
 $('#repsshow').html('divtmp+<div id=newrep'+number_of_report+' title=\"Отчёт №'+number_of_report+'\">Подождите идёт генерация отчёта</div>');
 $('#newrep'+number_of_report).dialog({modal:false,autoOpen:false});
 $('#newrep'+number_of_report).dialog('option','height',500);
 $('#newrep'+number_of_report).dialog('option','width',500);
 $('#newrep'+number_of_report).load('reports/get_jquery.php',{cars: cars,typereport: typereport,formatreport: formatreport,datefrom:datefrom,dateto:dateto,timefrom:timefrom,timeto:timeto});
 $('#newrep'+number_of_report).dialog('open');

 $('#newrep'+number_of_report).bind( "dialogclose", function(event, ui) {
  $('#newrep'+number_of_report).html('');
  $('#newrep'+number_of_report).dialog('destroy');
 });

number_of_report++;
}

</script>
<?php
echo "<div id='repsshow'></div>";
echo "<div id='divreports_selectcar' title='Выбор машин'>
 <table class=table14><tr><td>Фильтр:<input class='filter' id='filter_reports_selectcar' name='livefilter' text='' value=''></td><td><input type=checkbox onclick=\"menu_reports_selectcar_cball(this.checked);\"> Select/Unselect All</td></tr></table>

<div id='tablesorter'>
<table class=table2 id='table_reports_selectcar'><thead><th></th><th>Название</th><th>Госномер</th></thead><tbody>";
$sql="select carid,name,gosnum,icon from cars where clientid in (select clientid from users where userid=$userid) and carid in (select carid from users_cars where userid=$userid)  order by name,gosnum;";
$res=pg_query($sql);
while ($resf=pg_fetch_array($res)){
 extract ($resf,EXTR_OVERWRITE);
 echo "<tr><td><input id=$carid name=cars[$carid] type=checkbox class=\"cb cb_selectcar\"><img src=/images/cars/$icon></td><td>$name</td><td>$gosnum</td></tr>";
}
echo"</tbody></table></div></div>";
?>


<div id='divreports' title='Генерация отчётов' style='display:none;'>
<div id=repdev></div>
<table class=table2 id='table_reports'>

<?php
echo "
<tr><td>Машины</td><td><input type=button id=buttonselectcar class=button1 onclick=\"menu_reports_selectcar();\" value='Выбрать'></td></tr>
<tr class=odd><td>Тип отчёта</td><td><select name=typereport id=typereport>";
$sql="select value,info from settings where name='typereport' order by value;";
$res=pg_query($sql);
while ($resf=pg_fetch_array($res)){
 extract($resf,EXTR_OVERWRITE);
 echo "<option value='$value'>$info</option>";
}
echo "</select></td></tr>
<tr><td>Формат отчёта</td><td><select name=formatreport id=formatreport>";
$sql="select value,info from settings where name='formatreport' order by value;";
$res=pg_query($sql);
while ($resf=pg_fetch_array($res)){
 extract($resf,EXTR_OVERWRITE);
 echo "<option value='$value'>$info</option>";
}
#<option value='html'>в html</option><option value='xls'>в xls</option>
echo "</select></td></tr>
<tr class=odd><td>Дата</td><td>с <input id=report_datefrom name=report_datefrom size=11><input id=report_timefrom name=report_timefrom size=4 value='00:00'>по <input id=report_dateto name=report_dateto size=11><input id=report_timeto name=report_timeto size=4 value='23:59'></td></tr>
";
?>
</table>
<div>
<button type=button class=button4 onclick='menu_reports_checkform();'>Подготовить отчёт</button>
</div>

</div>

