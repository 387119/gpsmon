<script>
 function center_car (carid){
 var lat=datacar[carid][0];
 var lon=datacar[carid][1];
  wMap.setCenter([lat,lon]);
  markers[carid].balloon.open(wMap.getCenter());
 }

$(function(){
			            $('#tekinfo').dialog({modal:false,autoOpen:false});
				    $('#tekinfo').dialog('option','height',400);
			    	    $('#tekinfo').dialog('option','width',700);
			    	    $('#table_statusnow').liveFilter('fade');
			    	    $('#table_statusnow').tablesorter();
});
function menu_statusnow_update (){
 $('#iconload_statusnow').html('<img src=images/load.gif>');
 $('#table_statusnow_tbody').load('menu/statusnow_jq.php',function(){
				    $('#table_statusnow').trigger('update'); 
				    $('#iconload_statusnow').html('');
			    	    $('#filter_statusnow').val('');
});

};

function menu_statusnow_show (){
 menu_statusnow_update();
 $('#tekinfo').dialog('open');
};

</script>
<div id='tekinfo' title='Общая информация' style='display:none;'>
<table class=table14><tr><td>Фильтр:<input class='filter' id='filter_statusnow' name='livefilter' text='' value=''></td>
	<td><button onclick='menu_statusnow_update();'>Обновить данные</button></td>
	<td id='iconload_statusnow'></td></tr></table>
<div id='tablesorter'>
<table class=table2 id='table_statusnow'>
<thead><th>Тип</th><th>Название объекта</th><th>гос.номер</th><th>км/ч.</th><th>пробег км. (24ч.)</th><th>GPS</th><th>GSM</th><th>последние данные</th><th>действия</th><th>ФИО</th><th>тел.</th></thead>
<tbody id='table_statusnow_tbody'>
</tbody>
</table>
</div>
</div>

