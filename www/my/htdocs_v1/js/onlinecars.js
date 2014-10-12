  var markers=new Array();
  var infowindows=new Array();
  var datacar=new Array(new Array());
  var imagecar='/images/cars/car1.png';
  var tmplng;
  var wMarkers;
  var iii=0;
function updatemarkers (){
 var len=datacar.length,i;
 // удаляем все маркеры
// markers.length=0;
//  if (wMarkers !== undefined) wMarkers.removeAll();

  for (i=1;i<len;i++){
  //создаём новые маркеры
  if (datacar[i] !== undefined){
  if (markers[i] === undefined){
    markers[i] = new ymaps.Placemark([datacar[i][0],datacar[i][1]],{
			balloonContent: '<img src=\"images/cars/'+datacar[i][2]+'\" style=\"float: right;\"><br><b>Машина:</b>'+datacar[i][3]+
					'<br><b>гос. номер: </b>'+datacar[i][11]+
					'<br><b>ФИО водителя: </b>'+datacar[i][9]+
					'<br><b>тел. водителя: </b>'+datacar[i][10]+
					'<br><b>Скорость: </b>'+datacar[i][6]+" км/ч."+
					'<br><b>дата обновления: </b> <font style=\"color:'+datacar[i][13]+'\">'+datacar[i][4]+'</font>'+
					'<br><b>Качество сигнала GPS: </b> <img src=images/gps_s_'+datacar[i][5]+'.png style=\"height:17px;\">'+
					'<br><b>Качество сигнала GSM: </b> <img src=images/gsmsig'+datacar[i][12]+'.png style=\"height:15px;\">'+
					'<br><b>Заряд батареи: </b> '+datacar[i][16]+''+
					'<br><br><img src=images/icon_edit.png>&nbsp;<a href=\"javascript:menu_editcars_show('+i+');\">редактировать данные машины</a>'
			},{
			iconImageHref: 'images/cars.php?carid='+i+'&icon='+datacar[i][2]+'&az'+datacar[i][8]+'&spd='+datacar[i][6], // картинка иконки
			iconImageSize: [50, 50] // размеры картинки
//			iconImageOffset: [0, 0] // смещение картинки
			});
    wMarkers.add(markers[i]);
 }
 else {//маркер для этой машины уже существует, поэтому просто изменяем его данные
markers[i].geometry.setCoordinates([datacar[i][0],datacar[i][1]]);//change coordinates
markers[i].properties.set('balloonContent','<img src=\"images/cars/'+datacar[i][2]+'\" style=\"float: right;\"><br><b>Машина:</b>'+datacar[i][3]+
					'<br><b>гос. номер: </b>'+datacar[i][11]+
					'<br><b>ФИО водителя: </b>'+datacar[i][9]+
					'<br><b>тел. водителя: </b>'+datacar[i][10]+
					'<br><b>Скорость: </b>'+datacar[i][6]+" км/ч."+
					'<br><b>дата обновления: </b> <font style=\"color:'+datacar[i][13]+'\">'+datacar[i][4]+'</font>'+
					'<br><b>Качество сигнала GPS: </b> <img src=images/gps_s_'+datacar[i][5]+'.png style=\"height:17px;\">'+
					'<br><b>Качество сигнала GSM: </b> <img src=images/gsmsig'+datacar[i][12]+'.png style=\"height:15px;\">'+
					'<br><b>Заряд батареи: </b> '+datacar[i][16]+''+
					'<br><br><img src=images/icon_edit.png>&nbsp;<a href=\"javascript:menu_editcars_show('+i+');\">редактировать данные машины</a>');//change balloon
markers[i].options.set('iconImageHref', 'images/cars.php?carid='+i+'&icon='+datacar[i][2]+'&az'+datacar[i][8]+'&spd='+datacar[i][6]);//change icon
 }

//   infowindows[i].setContent(datacar[i][3]);
  }//if datacar != undefined
}//for
   wMap.geoObjects.add(wMarkers);
iii++;   
};//function updatemarkers

//function centermapcar(){
// var latlngbounds = new google.maps.LatLngBounds();
// $.each(markers,function(carid,x) {
//  if (carid!=0)latlngbounds.extend(x);
// });
// var i;
// for ( i=0; i<markers.length; i++ ){
//     }
// map.setCenter( latlngbounds.getCenter(), map.fitBounds(latlngbounds));
//};

//function updatehtml(){
// var len=datacar.length,i;
//  for (i=1;i<len;i++){
//  if (datacar[i] !== undefined){

//   document.getElementById("status_gps["+i+"]").text=datacar[i][4];
//   document.getElementById("speed_info["+i+"]").innerHTML=datacar[i][6]+' км/ч';
//   document.getElementById("dest_info["+i+"]").innerHTML=datacar[i][7]+' км';
//   document.getElementById("status_icon["+i+"]").src='images/gps_s_'+datacar[i][5]+'.png';
//   if (datacar[i][5]=='off')document.getElementById("status_icon["+i+"]").title='Данных давно небыло';
//   if (datacar[i][5]=='bad')document.getElementById("status_icon["+i+"]").title='Задержка';
//   if (datacar[i][5]=='on')document.getElementById("status_icon["+i+"]").title='Данные актуальны';
//  };
// };
//};

function updatedata (){
        var caridtmp;
        $.ajax({
         url: 'jq/getonlinedata.php',
         dataType: "json",
         success: function (result) {
          if (result.type == 'error') {
            alert('error of update data, please push F5 for update web page');
            return(false);
          }
          else{
            $(result.regions).each(function() {
             caridtmp=$(this).attr('carid');
              datacar[caridtmp]=[
            		$(this).attr('lat'),
            		$(this).attr('lon'),
            		$(this).attr('icon'),
            		$(this).attr('name'),
            		$(this).attr('maxtstamp'),
            		$(this).attr('gpsicon'),
            		$(this).attr('speed'),
            		$(this).attr('dsts'),
            		$(this).attr('azimut'),
             		$(this).attr('fiodriver1'),
             		$(this).attr('teldriver1'),
            		$(this).attr('gosnum'),
            		$(this).attr('gsmsignal'),
            		$(this).attr('colortstamp'),
            		$(this).attr('gpsdop'),
            		$(this).attr('maxspeed'),
            		$(this).attr('battery_percent')
              ];
              });
	      updatemarkers();
//	      updatehtml();
             }
            }
      });
}//fun updatedata

$(document).ready(function(){
   setInterval("updatedata()", 60000);
});
                      
