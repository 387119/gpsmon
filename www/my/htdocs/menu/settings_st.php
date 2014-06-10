<script>
$(function(){
			            $('#div_menu_settings').dialog({modal:false,autoOpen:false});
				    $('#div_menu_settings').dialog('option','height',400);
			    	    $('#div_menu_settings').dialog('option','width',700);
});

function menu_settings_show (){
 $('#iconload_settings').html('<img src=images/load.gif>');
 $('#table_menu_settings_tbody').load('menu/settings_jq.php',function(){
				    $('#iconload_settings').html('');
});
 $('#div_menu_settings').dialog('open');

};
function menu_settings_save (obj){
         $.ajax({
         url: 'menu/settings_save.php',
         data: 'id='+obj.id+'&value='+obj.checked,
         success: function (result) {
          if (result == 'ok')alert ("Сохранено");
           else alert ("Ошибка сохранения: "+result);
         }});
};

function menu_settings_save_map (obj){
 var lat =wMap.getCenter()[0];
 var lon =wMap.getCenter()[1];
 var zoom=wMap.getZoom();
 $.ajax({
    url: 'menu/settings_save.php',
         data: 'id='+obj.id+'&value='+lat+','+lon+','+zoom,
         success: function (result) {
          if (result == 'ok')alert ("Сохранено");
           else alert ("Ошибка сохранения: "+result);
         }});
 
};

</script>
<div id='div_menu_settings' title='Настройки' style='display:none;'>
<table><tr><td id='iconload_settings'></td></tr></table>
<table class=table2 id='table_menu_settings'>
<tbody id='table_menu_settings_tbody'>
</tbody>
</table>
</div>

