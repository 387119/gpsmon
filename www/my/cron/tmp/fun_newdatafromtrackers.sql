CREATE OR REPLACE FUNCTION update_data_gps_from_sensors() RETURNS void AS
$BODY$
begin work;

--- Добавляем параметр Severity согластно которому разные функции будут понимать какие данные им можно брать для обработки
--- Severity = 0 - данные только получены
--- Severity = 90 - данные подготовлены к переносу в архив и рабочую таблицу
--- Severity = 100 - данные можно чистить
-- добавить среднюю скорость

--drop table tmp1;
--drop table tmp2;

-- удаляем из таблицы приёма данных те записи imei которых не зарегестрирован в базе (можно сделать на on insert)
delete from data.gps_from_trackers where imei not in (select imei from trackers);
select * into  gps_ft from data.gps_from_trackers where class=2;
-- делаем выборку во временную таблицу данных которые только поступили
select distinct (select carid from trackers where trackers.imei=gft.imei limit 1) as carid,
       (select trackerid from trackers where trackers.imei=gft.imei limit 1) as trackerid,
       imei,lat,lon,null::integer as latb,null::integer as lonb,
       null::float as flat,null::float as flon,null::float as flatb,null::float as flonb,
       null::float as a,null::float as c,null::float as d,
       null::float as rlat,null::float as rlon,speed,tstamp,null::timestamp with time zone as tstampb,0::integer as dest,
       dut,volt,azimut,gsmsignal,gpsdop,signal_restart
   into  tmp1 
   from gps_ft as gft;
--   where imei in (select imei from trackers);
-- удаляем из временной таблицы дублирующие записи (можно сделать на sensor таблицы на oninsert)
delete from tmp1 as dg1 using data.gps as dg2 where dg1.carid=dg2.carid and dg1.tstamp=dg2.tstamp;

-- делаем корректировки (можно сделать на oninsert)
update tmp1 set azimut=0 where speed=0;
-- удаляем записи непривязанных к машинам трекеров (можно сделать на on insert)
select distinct imei,max(tstamp) as tstamp into  tmp1_imei from tmp1 where carid is null group by imei;
insert into data.imei_not_db  (imei,lastsend) (select imei,tstamp from tmp1_imei where tmp1_imei.imei not in (select imei from data.imei_not_db));
select imei,tstamp into  tmpnotdb from tmp1 where carid is null;
delete from tmp1 where carid is null;
-- удаляем записи которые говорят что данных по машине нет, или ошибочные
--delete from tmp1 where lat=-2147483648 or lon=-2147483648;

-- подсчитываем пройденный путь для каждого промежутка времен во временной таблице
--select imei,max(tstamp) as tstamp,null::integer as lon,null::integer as lat into  gpsf_tmp1 from data.gps_from_trackers group by imei;
--update gpsf_tmp1 set lon=,lat= from 
--select carid,max(tstamp),null::integer as lon,null::integer as lat into  gps_tmp1 from data.gps group by carid;


--update tmp1 as t1 set tstampb=(select tstamp from tmp1 as t2 where t2.imei=t1.imei and t2.tstamp<t1.tstamp order by tstamp desc limit 1),
--		      latb=(select lat from tmp1 as t2 where t2.imei=t1.imei and t2.tstamp<t1.tstamp order by tstamp desc limit 1),
--		      lonb=(select lon from tmp1 as t2 where t2.imei=t1.imei and t2.tstamp<t1.tstamp order by tstamp desc limit 1);
--update tmp1 as t1 set tstampb=(select tstamp from data.gps as t2 where t2.carid=t1.carid and t2.tstamp<t1.tstamp order by tstamp desc limit 1),
--		      latb=(select lat from data.gps as t2 where t2.carid=t1.carid and t2.tstamp<t1.tstamp order by tstamp desc limit 1),
--		      lonb=(select lon from data.gps as t2 where t2.carid=t1.carid and t2.tstamp<t1.tstamp order by tstamp desc limit 1)
--	where tstampb is null;
 
-- можно колонку tstampb, latb, lonb  добавить в основную таблицу sensors (запускать надо периодически так как данные могут приходить не последовательно)
select imei,tstamp,lat,lon, carid,trackerid,flat,flon,flatb,flonb,a,c,d,rlat,rlon,speed,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,
	lag(tstamp) over (partition by imei order by tstamp) as tstampb,
	lag(lat) over (partition by imei order by tstamp) as latb,
	lag(lon) over (partition by imei order by tstamp) as lonb
	into  tmp2
	from tmp1 where lat>0 and lon>0 order by imei,tstamp;

--(select tstamp,lat,lon from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1) 
update tmp2 set 
    tstampb=(select tstamp from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),
    latb=(select lat from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),
    lonb=(select lon from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1)
    where tmp2.tstampb is null;

--update tmp2 set flat=lat/600000::real,flon=lon/600000::real,flatb=latb/600000::real,flonb=lonb/600000::real;
--update tmp2 set a=sin(radians(flatb-flat)/2::real)^2::real+sin(radians(flonb-flon)/2::real)^2::real*cos(radians(flat)::real)::real*cos(radians(flatb)::real)::real;
--update tmp2 set c=2*atan2(sqrt(a)::real,sqrt(1-a)::real)::real;
--update tmp2 set d=6371000*c::real;
--update tmp2 set dest=abs(d);
--update tmp2 set dest=0 where dest is null;

--- поменять вычисление расстояния на функцию postgis
update tmp2 set flat=lat/600000::real,flon=lon/600000::real,flatb=latb/600000::real,flonb=lonb/600000::real where lat>0 and lon>0;
update tmp2 set dest=case when abs(6371000*(2*atan2(
    sqrt(sin(radians(flatb-flat)/2::real)^2::real+sin(radians(flonb-flon)/2::real)^2::real*cos(radians(flat)::real)::real*cos(radians(flatb)::real)::real)::real,
    sqrt(1-(sin(radians(flatb-flat)/2::real)^2::real+sin(radians(flonb-flon)/2::real)^2::real*cos(radians(flat)::real)::real*cos(radians(flatb)::real)::real))::real)::real)::real) is null 
then 0 else abs(6371000*(2*atan2(
    sqrt(sin(radians(flatb-flat)/2::real)^2::real+sin(radians(flonb-flon)/2::real)^2::real*cos(radians(flat)::real)::real*cos(radians(flatb)::real)::real)::real,
    sqrt(1-(sin(radians(flatb-flat)/2::real)^2::real+sin(radians(flonb-flon)/2::real)^2::real*cos(radians(flat)::real)::real*cos(radians(flatb)::real)::real))::real)::real)::real) end
    where lat>0 and lon>0;
--select tstamp,tstampb,lat,lon,latb,lonb,carid,dest,speed from tmp2 order by carid,tstamp desc;

update tmp1 set tstampb=tmp2.tstampb,latb=tmp2.latb,lonb=tmp2.lonb,dest=tmp2.dest from tmp2 where tmp1.carid=tmp2.carid and tmp1.tstamp=tmp2.tstamp;
-- обновляем таблицу онлайн по новым данным
-- обнуляем расстояние если день пройден
--update data.online set dest_day=(case when extract (day from tstamp)!=extract (day from CURRENT_TIMESTAMP) then 0 else dest_day end);

--- таблицу онлайн обновляем на событие oninsert а также после подсчёта периодических данных
update data.online set dest_day=0 where extract (day from tstamp)!=extract (day from CURRENT_TIMESTAMP);

-- обновляем online по последним данным и сумируем пройденный путь
select * into  tmp22 from tmp2;
update tmp22 set dest=0,speed=0 where speed<5;
select carid,max(tstamp) as tstamp ,max(speed) as speed_max,sum(dest) as dest into  tmp3 from tmp22 where tstamp >= date_trunc('day', CURRENT_TIMESTAMP) group by carid;

update data.online as t1  set azimut=t2.azimut, tstamp=(case when t2.tstamp>t1.tstamp then t2.tstamp else t1.tstamp end), lat=t2.lat,lon=t2.lon,speed=t2.speed,trackerid=t2.trackerid,gpsdop=t2.gpsdop,gsmsignal=t2.gsmsignal
--	dest_day=(case when extract (day from t2.tstamp) != extract (day from CURRENT_TIMESTAMP) then t1.dest_day else t1.dest_day+t2.dest end)
--	dest_day=t1.dest_day+t2.dest
	from tmp2 as t2 
	where t1.carid=t2.carid and t2.tstamp = (select tmp3.tstamp from tmp3 where tmp3.carid=t1.carid);

update data.online as t1 set speed_max=t2.speed_max,dest_day=t1.dest_day+t2.dest
	from tmp3 as t2
	where t1.carid=t2.carid;
-- добавляем новые данные в постоянную таблицу хранения
-- запись и обновление архивных и рабочих таблиц делаем периодически после того как все пересчёты будут завершены
--- Severity = 90
insert into data.gps (carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt) (select carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt from tmp1);
insert into data.gps_arh (carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt) (select carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt from tmp1);


-- отчищаем промежуточную таблицу от данных которые были записаны
-- Severity = 100
delete from data.gps_from_trackers as gft using gps_ft as tmp1 where gft.imei=tmp1.imei and gft.tstamp=tmp1.tstamp;
--delete from data.gps_from_trackers as gft using tmpnotdb where gft.imei=tmpnotdb.imei and gft.tstamp=tmpnotdb.tstamp;
--truncate data.gps_from_trackers;
--select carid,trackerid,imei,lat,lon,latb,lonb,speed, tstamp,tstampb,dest from tmp1 order by tstamp;
--select * from tmp1;
end work;
$BODY$
LANGUAGE sql VOLATILE;

