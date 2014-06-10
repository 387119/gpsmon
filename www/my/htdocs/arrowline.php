//////////////////// определение линии со стрелочками
             ArrowOverlay = function (geometry, data, options) {
                ArrowOverlay.superclass.constructor.call(this, geometry, data, options);

                var lastArrowOffset = 0;
                //будем следить за этой опцией, вообще для этого есть option.Monitor, но можно и так
                this.options.events.add('change', function () {
                    if (this._graphicsOverlay) {
                        if (lastArrowOffset != this.options.get('arrowOffset')) {
                            lastArrowOffset = this.options.get('arrowOffset');
                            this.applyGeometry();
                        }
                    }

                }, this);
            };
            
//            alert(navigator.userAgent);

            //лично я наследуюсь от overlay.Base, но лучше реализовать интефейс, а не использовать закрытые классы
            ymaps.util.augment(ArrowOverlay, ymaps.overlay.Base, {

                setMap: function (map) {
                    ArrowOverlay.superclass.setMap.call(this, map);
                    //заместо себя создадим графический оверлей и свяжем его с картой
                    if (map) {
                        this._graphicsOverlay = ymaps.geoObject.overlayFactory.staticGraphics.createOverlay(this.getArrowGeometry(), this._data);
                        this._graphicsOverlay.options.setParent(this.options);
                        this._graphicsOverlay.setMap(this._map);
                    } else {
                        if (this._graphicsOverlay) {
                        this._graphicsOverlay.setMap(null);
                            this._graphicsOverlay = null;
                        }
                    }
                },

                getArrowGeometry: function () {
                    //в данной функции используется закрытые модули graphics.Path и graphics.generator
                    //на самом деле их использовать очень сильно не рекомендуется
                    var lineCoordinates = this.getGeometry().getCoordinates(),
                            strokeWidth = this.options.get('strokeWidth'),
                            arrowLen = strokeWidth * 20,
                            arrowWidth = strokeWidth * 2,
                            dataLine = ymaps.graphics.Path.fromArray(lineCoordinates),
                            dashes = ymaps.graphics.generator.stroke.dash(dataLine, [arrowLen, arrowLen], this.options.get('arrowOffset', 0)),
                            lines = ymaps.graphics.Path.toArray(dashes),
                            result = [];
                    //мы создали обводку(strokeStyle:[arrowLen,arrowLen] и дополняем ее стрелочками
                    for (var i = 0, l = lines.length; i < l; ++i) {
                        var point = lines[i];
                        //разрыв обводки - конец сегмента
                        if (!point) {
                            point = lines[i - 1];
                            //мы работаем уже в пиксельном мире
                            //тут тут уже можно заместо координат отдать '0', который трактуется как разрыв
                            result.push(0);
                            var lastPoint = lines[i - 2],
                                    vector = [point[0] - lastPoint[0], point[1] - lastPoint[1]],
                                    length = Math.sqrt(vector[0] * vector[0] + vector[1] * vector[1]),
                                    normal = [-arrowWidth * vector[0] / length, -arrowWidth * vector[1] / length],
                                    arrow1 = [-normal[1], normal[0]],
                                    arrow2 = [ normal[1], -normal[0]];

                            result.push([point[0] - arrow1[0] + normal[0] * 2, point[1] - arrow1[1] + normal[1] * 2]);
                            result.push(point);
                            result.push([point[0] - arrow2[0] + normal[0] * 2, point[1] - arrow2[1] + normal[1] * 2]);
                            result.push(0);
                        } else {
                            result.push(point);
                        }

                    }
                    return new ymaps.geometry.pixel.LineString(result, 'nonZero');
                },

                applyGeometry: function () {
                    //пробрасываем геометрию
                    this._graphicsOverlay.setGeometry(this.getArrowGeometry());
                }
            });

            //создадим свою фабрику( еще один закрытый класс )
            wFactory = new ymaps.geoObject.OverlayFactory();
            wFactory.add("LineString", ArrowOverlay);

            line = new ymaps.GeoObject({
                geometry: {
                    type: 'LineString',
                    coordinates: [
                        [48.2198, 37.8053],
                        [48.0608, 38.1404],
                        [47.6801, 38.0003]
                    ]
                }
            }, {
                overlayFactory: wFactory,//используем ее
                strokeWidth: 4,
                
            });
            wMap.geoObjects.add(line);

/////////////////////////////////////////////////////
