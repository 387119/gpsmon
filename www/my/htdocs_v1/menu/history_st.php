<script>
var polyline=new Array();
var wMarkers_history;
var markers_history= new Array();

$(function(){
				    $('#divhistory').dialog({modal:false,autoOpen:false});
				    $('#divhistory').dialog('option','height',500);
			    	    $('#divhistory').dialog('option','width',600);
			    	    $('#table_history').liveFilter('fade');
			    	    $('#table_history').tablesorter();

});
function menu_history_update (){
 $('#iconload_history').html('<img src=images/load.gif>');
 $('#table_history_tbody').load('menu/history_jq.php',function(){
				    $('#table_history').trigger('update');
				    $('#iconload_history').html('');
			    	    $('#filter_history').val('');
				    $('#datefrom_history,#dateto_history' ).datepicker();
				    $('#datefrom_history,#dateto_history' ).datepicker( 'option', 'dateFormat', 'dd.mm.yy' );
});

};

function menu_history_show (){
 menu_history_update();
 $('#divhistory').dialog('open');
};

/// подготавливаем для вывода линии стрелочками
function extend (child, parent) {
//http://api.yandex.ru/maps/doc/jsapi/1.x/articles/tasks/overlays.xml#how-to-create-polyline-with-arrows
    var c = function () {};
    c.prototype = parent.prototype;
    c.prototype.constructor = parent;
    return child.prototype = new c();
};


function menu_history_hidetrek(){
// if (polyline !== undefined){wMap.geoObjects.remove(polyline);polyline.length=0;}
 if (wPolylines_history !== undefined){wPolylines_history.removeAll();polyline.length=0;}
 if (wMarkers_history !== undefined){wMarkers_history.removeAll();markers_history.length=0;}
 }
function menu_history_showtrek(){
//
//
//                {
//                    type: 'viaPoint', // метро арбатская - транзитная точка (проезжать через эту точку, но не останавливаться в ней)
//                    point: [47.9239, 37.5401]
//                },

//var ccords=new Array();
//ccords[0]=new Array(47.9138, 37.5501);
//ccords[1]=new Array(47.9239, 37.5401);
//ccords[2]=new Array(47.9314, 37.5356);

//  var i=0;
  var carid=$('input:radio[name=selcarhistory]:checked').val(); 
  var dsf=document.getElementById("datefrom_history").value;
  var tsf=document.getElementById("timefrom_history").value;
  var dst=document.getElementById("dateto_history").value;
  var tst=document.getElementById("timeto_history").value;
  var maxspeed;
  var type_show_line=-1,type_new_line,hint,first_placemark=0;
  var color,latold,lonold,polyline_count=0,tstamp,dest,azimutstr,srspeed,latold,lonold,signal_restart_str,icon_parking;
  var routeways=new Array();
  routeways.length=0;
  
 if (wPolylines_history !== undefined){wPolylines_history.removeAll();polyline.length=0;}
// if (polyline !== undefined){wMap.geoObjects.remove(polyline);polyline.length=0;}
 if (wMarkers_history !== undefined){wMarkers_history.removeAll();markers_history.length=0;}
  if (carid === undefined ){alert ("Ошибка: выберете машину");}
  else {
        maxspeed=datacar[carid][15];
        $.ajax({
         url: 'jq/getlastdata.php',
         data: 'carid='+carid+'&dsf='+dsf+'&tsf='+tsf+'&dst='+dst+'&tst='+tst+'&lambda=10',
         dataType: "json",
         success: function (result) {
          if (result.type == 'error') {
            $(result.regions).each(function() {
             alert($(this).attr('text'));
            });
            return(false);
          }
          else{
	  
            $(result.regions).each(function() {
// если скорость меньше 5 и время конца - время начала < минуты тогда парковка, иначе отрезок и насрать что скорость меньше 5
	    if (($(this).attr('speed')<5) && ($(this).attr('tstamp_r')>300)){
	if ($(this).attr('signal_restart')>0){
		icon_parking='images/icon_parking_bad.png';
		signal_restart_str="<br><b>Зарегестрировано отказов питания трекера: </b>"+$(this).attr('signal_restart');
	}
	 else {
	  icon_parking='images/icon_parking.png';
	  signal_restart_str='';
	 }
    markers_history[markers_history.length] = new ymaps.Placemark([$(this).attr('lat'),$(this).attr('lon')],{
			balloonContent: 'Время остановки: '+$(this).attr('tstampb')+
					'<br>Начало движения: '+$(this).attr('tstamp')+
					signal_restart_str
			},{
			iconImageHref: icon_parking, // картинка иконки
			iconImageSize: [20, 20], // размеры картинки
			iconImageOffset: [-10, -20] // смещение картинки
			});
   wMarkers_history.add(markers_history[markers_history.length-1]);
	    
	    }
	    else{// else speed >5 рисуем отрезок
hint="Cкорость:"+$(this).attr('speed')+"км/ч, Время:"+$(this).attr('tstamp')+", Расстояние:"+$(this).attr('dest')+" метров";
if(($(this).attr('azimut')>=20)&&($(this).attr('azimut')<70)){azimutstr='северо-восточное';}
if(($(this).attr('azimut')>=70)&&($(this).attr('azimut')<110)){azimutstr='восточное';}
if(($(this).attr('azimut')>=110)&&($(this).attr('azimut')<160)){azimutstr='юго-восточное';}
if(($(this).attr('azimut')>=160)&&($(this).attr('azimut')<200)){azimutstr='южное';}
if(($(this).attr('azimut')>=200)&&($(this).attr('azimut')<250)){azimutstr='юго-западное';}
if(($(this).attr('azimut')>=250)&&($(this).attr('azimut')<290)){azimutstr='западное';}
if(($(this).attr('azimut')>=290)&&($(this).attr('azimut')<340)){azimutstr='северо-западное';}

if (($(this).attr('speed')>=maxspeed)&&(maxspeed>0))color="#ff0000";else color="#0000ff";
if ($(this).attr('signal_restart')>0){color="#000000";signal_restart_str="<br><b>Зарегестрировано отказов питания трекера: </b>"+$(this).attr('signal_restart');}
else signal_restart_str="";

polyline[polyline.length]=new ymaps.Polyline (
[[$(this).attr('latb'),$(this).attr('lonb')],[$(this).attr('lat'),$(this).attr('lon')]], {hintContent: hint,
balloonContent:"<b><i>Данные относятся только к выбранному отрезку пути</i></b>"+
		"<br><b>Время начала: </b>"+$(this).attr('tstampb')+
		"<br><b>Время конца: </b>"+$(this).attr('tstamp')+
		"<br><b>Средняя скорость: </b>"+$(this).attr('speed')+" км/ч."+
		"<br><b>Пройденый путь: </b>"+$(this).attr('dest')+" м."+
		"<br><b>Направление движения: </b>"+"<img border=0 src=images/azimut.php?az="+$(this).attr('azimut')+"> "+azimutstr+
		signal_restart_str
		},{
draggable:false,
strokeWidth: 5,
strokeOpacity: 0.4,
strokeColor: color
});
wPolylines_history.add(polyline[polyline.length-1]);
//wMap.geoObjects.add(polyline[polyline.length-1]);
//balloonopen
polyline[polyline.length-1].events.add('balloonopen', function (e) {
 e.get('target').options.set('strokeOpacity','1');
});

polyline[polyline.length-1].events.add('balloonclose', function (e) {
 e.get('target').options.set('strokeOpacity','0.4');
});
 
	    }//  else speed >5 рисуем отрезок

              });
//    markers_history[markers_history.length] = new ymaps.Placemark([latold,lonold],{
//			balloonContent: 'Конец движения: '+tstamp
//			},{
//			iconImageHref: 'images/icon_finish.png', // картинка иконки
//			iconImageSize: [50, 50] // размеры картинки
//			iconImageOffset: [0, 0] // смещение картинки
//			});
//    wMarkers_history.add(markers_history[markers_history.length-1]);

wMap.geoObjects.add(wMarkers_history);
wMap.geoObjects.add(wPolylines_history);
		}//success 
         }//success all
      });//ajax load
     }//if carid !== undefined

};

</script>
<div id='divhistory' title='История движения' style='display:none;'>
<table class=table14><tr><td>Фильтр:<input class='filter' id='filter_history' name='livefilter' text='' value=''></td>
	<td><button onclick='menu_history_update();'>Обновить данные</button></td><td><button onclick='menu_history_showtrek();'>Показать трек</button></td><td><button onclick='menu_history_hidetrek();'>Скрыть трек</button></td><td id='iconload_history'></td></tr>
<tr><td colspan=6>С даты:<input id='datefrom_history' size=9> <input id='timefrom_history' size=4 value='00:00'> По дату:<input id='dateto_history' size=9><input id='timeto_history' size=4 value='23:59'></td></tr>
</table>
<div id='tablesorter'>
<table class=table2 id='table_history'>
<thead><th></th><th>Тип</th><th>Название объекта</th><th>гос.номер</th></thead>
<tbody id='table_history_tbody'>
</tbody>
</table>
</div>
</div>


