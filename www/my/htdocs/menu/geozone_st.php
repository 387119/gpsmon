<?php

## этот коммент можешь удалить!
## 1 - css оформление таблицы с машинами используй class=table2
## 2 - адрес почты уведомлений убери отсюда, так как я его перенесу в настроки профиля пользователя
## 3 - этот файл именуется исходя из <name>_static.php (сокращённо geozone_st.php), 
##     а файлы которые ты подключаешь по ajax проименуй их geozone_jqXX.php - XX это цифры если они нужны

?>
<script>

$(function(){
				    $('#divgeozone').dialog({modal:false,autoOpen:false});
				    $('#divgeozone').dialog('option','height',400);
			    	    $('#divgeozone').dialog('option','width',700);
				    $('#table_geozone').liveFilter('fade');

});

function menu_geozone_update (){
 $('#iconload_geozone').html('<img src=images/load.gif>');
 $('#table_geozone_tbody').load('menu/geozone_jq.php',function(){
                        $('#table_geozone').tablesorter();
                    $('#iconload_geozone').html('');
                        // $('#filter_geozone').val('');
});
};

function menu_geozone_show (){
    menu_geozone_update();
 $('#divgeozone').dialog('open');
};

</script>
<div id='divgeozone' title='Управление геозонами' style='display:none;'>
<table class=table14><tr><td>Фильтр:<input class='filter' id='filter_table_geozone' name='livefilter' text='' value=''></td></tr></table>

    <div id='tablesorter'>
        <table class=table2 id='table_geozone'>
        <thead>
            <th>Тип</th>
            <th>Название объекта</th>
            <th>Госномер</th>
            <th>Центрировать</th>
            <th>Геозоны</th>
        </thead>
        <tbody id='table_geozone_tbody'>
        </tbody>
        </table>
    </div>
</div>



<!-- ################################################################################ -->



<style type="text/css">
    #geozonesEditor {
        overflow: hidden; 
        position: absolute; 
        left:50px; 
        top: 50px; 
        padding: 5px; 
        background-color:#FFFFFF; 
        display:none;
        border: 1px solid #999999;
        z-index: 999;
    }

    #geozonesEditor input[type=checkbox], #geozonesEditor label {
        vertical-align: middle;
    }

    #geozonesEditor input[type=text] {
        border: 1px solid #999999;
        background: none;
    }

    #geozone-header {
        background-color: #CCCCCC;
        font-weight: bold;
        margin: -5px -5px 5px;
        padding: 3px 5px;        
    }

    #geozone-footer {
        background-color: #CCCCCC;
        font-weight: bold;
        margin: 10px -5px -5px;
        padding: 3px 5px;    
    }


</style>


<script type="text/javascript">
    var selectedCarId;
    var selectedOverlayId;
    var overlays = Array(); // все оверлеи
    var overlaysToDelete = Array(); // объекты выбранные для удаления из базы

    var overlaysCollection;
    var selectedOverlay;

    // панель управления геозонами
    $("#wmap").append(' \
        <div id="geozonesEditor"> \
            <div id="geozone-header">Домашние зоны для <span id="geozone-car"></span></div> \
            <input id="editGeozones" type="checkbox" onchange="toggleEditable()" /> \
            <label for="editGeozones">Режим редактирования</label><br /> \
            Имя:&nbsp;<input id="geozoneName" type="text" />&nbsp;<button onclick="changeGeozoneName()">Изменить</button><br /><br /> \
            <button id="add-geozone" onclick="addGeozone()">Добавить</button> \
            <button id="finish-adding-geozone" onclick="finishAddingGeozone()" disabled>Завершить</button> \
            <button onclick="deleteGeozone()" style="color:red">Удалить</button> \
            <div id="geozone-footer"> \
                <button onclick="saveGeozones()">Сохранить</button> \
                <button onclick="hideGeozones()">Закрыть</button> \
            </div> \
        </div> \
    ');

    function changeGeozoneName() {
        if ($("#geozoneName").val().length > 0 && selectedOverlay != undefined) { // если поле "имя" заполенено
            selectedOverlay.properties.set("name", $("#geozoneName").val());
            selectedOverlay.properties.set("hintContent", $("#geozoneName").val());
        }
    }

    function toggleEditable() { // изменение флага редактирования объектов
        if ($("#editGeozones").attr("checked")) {
            selectedOverlay.editor.startEditing();
            selectedOverlay.options.set({draggable: true});
        } else {
            selectedOverlay.editor.stopEditing();
            selectedOverlay.options.set({draggable: false});
        }
    }


    function showGeozonesEditor() {
        $("#geozonesEditor").show();
    }

    function getCarInfo(carId) {
        if (carId != undefined) {
            $.getJSON("menu/geozone_backend.php", {"action": "getCarInfo", "carid": carId}, function(data) {
                $("#geozone-car").text(data[0] + " (" + data[1] + ")");
            });
        }
    }

    function hideGeozones() {
        $("#geozonesEditor").hide();
        overlaysCollection.removeAll();
        selectedOverlay = undefined;
        overlaysToDelete.length = 0;
    }

    function deleteGeozone() {
        if (selectedOverlay != undefined) {
            if (selectedOverlay.properties.get("id") != undefined) { // если зона есть в базе
                overlaysToDelete.push(selectedOverlay.properties.get("id"));
            }
            overlaysCollection.remove(selectedOverlay);
            selectedOverlay = undefined;

            $("#geozoneName").val("");
        }
    }

    function addGeozone() {
        var overlay = new ymaps.Polygon([], {}, {
            fillColor: '#6699ff',
            strokeWidth: 3,
            strokeColor: "#000000",
            opacity: 0.5//,
        });

        overlay.properties.set("overlayType", "polygon");
        overlay.properties.set("carId", selectedCarId);

        overlay.events.add('click', function() {
            selectCurrentOverlay(this);
        }, overlay);

        overlaysCollection.add(overlay);

        overlay.editor.startEditing(); 
        overlay.editor.startDrawing(); 

        selectedOverlay = overlay;

        $("#geozoneName").val("");
        $('#add-geozone').attr('disabled', true);
        $('#finish-adding-geozone').attr('disabled', false);
    }

    function finishAddingGeozone() {
        selectedOverlay.editor.stopEditing();
        $('#finish-adding-geozone').attr('disabled', true);
        $('#add-geozone').attr('disabled', false);
    }

    function getGeozones(carId) {
        if (carId != undefined) {
            if (overlaysCollection == undefined) {
                overlaysCollection = new ymaps.GeoObjectCollection(); // объявление коллекции
            } else {
                overlaysCollection.removeAll(); // очистка коллекции
            }
            
            getCarInfo(carId);

            $.getJSON("menu/geozone_backend.php", {"action": "getGeozones", "carid": carId}, function(data) {
                // console.log(data);
                for (var i in data) {
                    if (data[i].type == "polygon") { // рисование полигонов
                        var coords = []; // координаты вершин
                        for (var j in data[i].lat) {
                            coords.push([parseFloat(data[i].lat[j]), parseFloat(data[i].lon[j])]);
                        }
                        
                        var overlay = new ymaps.Polygon([
                            coords
                        ], {
                            hintContent: data[i].name
                        }, {
                            fillColor: '#6699ff',
                            strokeWidth: 3,
                            strokeColor: "#000000",
                            opacity: 0.5//,
                        });

                        overlay.properties.set("overlayType", data[i].type);
                        overlay.properties.set("id", parseInt(data[i].id));
                        overlay.properties.set("carId", parseInt(data[i].carid));
                        overlay.properties.set("name", data[i].name);

                        overlay.events.add('click', function() {
                            selectCurrentOverlay(this);
                        }, overlay);

                        overlaysCollection.add(overlay);
                    }    
                }
                wMap.geoObjects.add(overlaysCollection);
                wMap.setBounds(overlaysCollection.getBounds());
            });
            selectedCarId = carId;
            $("#geozoneName").val("");
            showGeozonesEditor();

        }
    }


    function saveGeozones() {
        var data = new Array();
        
        overlaysCollection.each(function(overlay) {
            var coords = overlay.geometry.getCoordinates();
            var coords_lat = new Array();
            var coords_lon = new Array();
            for (var i in coords) {
                for (var j in coords[i]) {
                    coords_lat.push(coords[i][j][0]);
                    coords_lon.push(coords[i][j][1]);
                }
            }
            data = {
                id: parseInt(overlay.properties.get("id")),
                carid: parseInt(overlay.properties.get("carId")),
                overlay_type: overlay.properties.get("overlayType"),
                name: overlay.properties.get("name"),
                latitude: coords_lat,
                longitude: coords_lon
            };

            $.getJSON("menu/geozone_backend.php", {"action": "saveGeozone", "data": JSON.stringify(data) }, function(data) {
                overlay.properties.set("id", data.id);
            });            
            
            // удаление геозон
            for (var i in overlaysToDelete) {
                $.getJSON("menu/geozone_backend.php", {"action": "deleteGeozone", "id": overlaysToDelete[i] }, function(data) {
                });            
            }
            console.log(data);
            
        });
    }

    function selectCurrentOverlay(overlay) {
        selectedOverlay = overlay;

        overlaysCollection.each(function(ovr) {
            if (selectedOverlay.properties.get("id") == ovr.properties.get("id")) {
                ovr.options.set({strokeColor: "#FF0000"})
            } else {
                ovr.options.set({strokeColor: "#000000"})
            }
        });
        
        $("#geozoneName").val(selectedOverlay.properties.get("name")); // заполнить поле "имя" именем объекта
    }

</script>








