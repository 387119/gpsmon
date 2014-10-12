<script>
var carid_for_edit=-1;
$(function(){
			            $('#diveditcars').dialog({modal:false,autoOpen:false});
				    $('#diveditcars').dialog('option','height',350);
			    	    $('#diveditcars').dialog('option','width',330);
});

function menu_editcars_save (){
 var fcar=$('#input_editcars_name').val();
 var ffiodriver1=$('#input_editcars_fiodriver1').val();
 var fteldriver1=$('#input_editcars_teldriver1').val();
 var nlocation=$('#input_editcars_nlocation').val();

 $('#iconload_editcars').html('<img src=images/load.gif>');
 $('#iconload_editcars').load('menu/editcars_jq.php',{carid:carid_for_edit,name:fcar,fiodriver1:ffiodriver1,teldriver1:fteldriver1,nlocation:nlocation},function(){
   datacar[carid_for_edit][3]=fcar;
   datacar[carid_for_edit][9]=ffiodriver1;
   datacar[carid_for_edit][10]=fteldriver1;
 });

};

function menu_editcars_show (carid){
 carid_for_edit=carid;
 $('#iconload_editcars').html('');
 $('#input_editcars_name').val(datacar[carid][3]);
 $('#input_editcars_fiodriver1').val(datacar[carid][9]);
 $('#input_editcars_teldriver1').val(datacar[carid][10]);
 $('#input_editcars_nlocation').val('');
// $('#iconload_editcars').html('<img src=images/load.gif>');
// $('#table_editcars_tbody').load('menu/editcars_jq.php',{carid,carid},function(){
//				    $('#iconload_editcars').html('');
// });
 $('#diveditcars').dialog('open');
};

</script>
<div id='diveditcars' title='Редактирование данных машины' style='display:none;'>
<div id='iconload_editcars'></div>
<table class=table2 id='table_editcars'>
<tbody id='table_editcars_tbody'>
 <tr><td>Название машины:</td><td><input id='input_editcars_name'></td></tr>
 <tr><td>ФИО водителя:</td><td><input id='input_editcars_fiodriver1'></td></tr>
 <tr><td>тел. водителя:</td><td><input id='input_editcars_teldriver1'></td></tr>
 <tr><td>Новые координаты:</td><td><input id='input_editcars_nlocation'></td></tr>
 <tr><td colspan=2><button style='width:100%;' onclick='menu_editcars_save();'>Сохранить</button></td></tr>
</tbody>
</table>
</div>

