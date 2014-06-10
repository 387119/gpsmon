    <script src="http://api-maps.yandex.ru/2.0/?mode=debug&load=package.full,overlay.Base,geoObject.OverlayFactory,graphics.Path,graphics.generator.stroke,geometry.pixel.LineString&lang=ru-RU"
    
            type="text/javascript"></script>

    <script type="text/javascript">
    var ArrowOverlay;
    var wFactory;
    var wMap;
        // Как только будет загружен API и готов DOM, выполняем инициализацию
        ymaps.ready(init);

        function init () {
            // Создание экземпляра карты и его привязка к контейнеру с
            // заданным id ("map")
            wMap = new ymaps.Map('wmap', {
                    // При инициализации карты, обязательно нужно указать
                    // ее центр и коэффициент масштабирования
		    behaviors: ['default', 'scrollZoom'],
<?
#    theme.browser.touch.safariMobile,domEvent.Touch,
  $sql="select clientid,map_center_lat as lat,map_center_lon as lon,map_center_zoom as zoom into temporary tmp1 from users where userid=$userid;
		update tmp1 set lat=clients.map_center_lat,lon=clients.map_center_lon,zoom=clients.map_center_zoom from clients where lat is null and lon is null and tmp1.clientid=clients.clientid;
		select lat/600000::real as lat,lon/600000::real as lon,zoom from tmp1 where lat is not null and lon is not null;";
  $res=pg_query($sql);
  if (pg_num_rows($res)>0)
   echo "center: [".pg_result($res,0,"lat").", ".pg_result($res,0,"lon")."], zoom: ".pg_result($res,0,"zoom");
  else echo "center: [47.123714, 37.584915],zoom: 2
";
//                    center: [55.76, 37.64], // Москва
//                    zoom: 10
?>


                });
            wMap.controls
                // Кнопка изменения масштаба
                .add('zoomControl')
                // Список типов карты
                .add('typeSelector')
                // Стандартный набор кнопок
                .add('mapTools');
    var searchControl = new ymaps.control.SearchControl({boundedBy:[49.1535, 36.6799][47.1955, 39.8989],kind:'locality',useMapBounds:true,resultsPerPage:10});
    wMap.controls.add(searchControl, { left: '110px', top: '6px'});

// для обработки события левой кнопкой мыши надо сделать event на 'click'
            wMap.events.add('contextmenu', function (e) {
                if (!wMap.balloon.isOpen()) {
                    var coords = e.get('coordPosition');
                    wMap.balloon.open(coords, {
                        contentHeader: 'Координаты',
                        contentBody:[
                                coords[0].toPrecision(6),
                                coords[1].toPrecision(6)
                            ].join(', ') + '</p>',
                        contentFooter: ''
                    });
                } else {
                    wMap.balloon.close();
                }
            });

                
    wMarkers = new ymaps.GeoObjectCollection();
    wMarkers_history = new ymaps.GeoObjectCollection();
    wPolylines_history = new ymaps.GeoObjectCollection();
    updatedata();

        }
    </script>
<div id='wmap'></div>
