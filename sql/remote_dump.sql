--
-- PostgreSQL database dump
--

-- Dumped from database version 9.3.4
-- Dumped by pg_dump version 9.3.2
-- Started on 2014-06-27 15:21:03 EEST

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 33 (class 2615 OID 362664)
-- Name: accounts; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA accounts;


ALTER SCHEMA accounts OWNER TO gpsmon;

--
-- TOC entry 4763 (class 0 OID 0)
-- Dependencies: 33
-- Name: SCHEMA accounts; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA accounts IS 'объекты мониторинга и их настройки ';


--
-- TOC entry 459 (class 2615 OID 1867135)
-- Name: cfg; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA cfg;


ALTER SCHEMA cfg OWNER TO gpsmon;

--
-- TOC entry 4764 (class 0 OID 0)
-- Dependencies: 459
-- Name: SCHEMA cfg; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA cfg IS 'конфигурации, настройки, параметры';


--
-- TOC entry 6 (class 2615 OID 17975)
-- Name: data; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA data;


ALTER SCHEMA data OWNER TO gpsmon;

--
-- TOC entry 4765 (class 0 OID 0)
-- Dependencies: 6
-- Name: SCHEMA data; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA data IS 'данные с объектов мониторинга';


--
-- TOC entry 34 (class 2615 OID 394121)
-- Name: gis; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA gis;


ALTER SCHEMA gis OWNER TO gpsmon;

--
-- TOC entry 4766 (class 0 OID 0)
-- Dependencies: 34
-- Name: SCHEMA gis; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA gis IS 'для работы с геоинформационными данными, поиск, позиционирование, марштуры...';


--
-- TOC entry 452 (class 2615 OID 972413)
-- Name: log; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA log;


ALTER SCHEMA log OWNER TO postgres;

--
-- TOC entry 4767 (class 0 OID 0)
-- Dependencies: 452
-- Name: SCHEMA log; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA log IS 'все логи';


--
-- TOC entry 35 (class 2615 OID 394128)
-- Name: map; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA map;


ALTER SCHEMA map OWNER TO gpsmon;

--
-- TOC entry 4768 (class 0 OID 0)
-- Dependencies: 35
-- Name: SCHEMA map; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA map IS 'карты';


--
-- TOC entry 7 (class 2615 OID 17976)
-- Name: olap; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA olap;


ALTER SCHEMA olap OWNER TO gpsmon;

--
-- TOC entry 4769 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA olap; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA olap IS 'посчитанные периоды';


--
-- TOC entry 681 (class 3079 OID 12617)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 4772 (class 0 OID 0)
-- Dependencies: 681
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- TOC entry 682 (class 3079 OID 372729)
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA gis;


--
-- TOC entry 4773 (class 0 OID 0)
-- Dependencies: 682
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry, geography, and raster spatial types and functions';


SET search_path = data, pg_catalog;

--
-- TOC entry 701 (class 1255 OID 332728)
-- Name: gm_data_arh2fs(); Type: FUNCTION; Schema: data; Owner: postgres
--

CREATE FUNCTION gm_data_arh2fs() RETURNS SETOF timestamp with time zone
    LANGUAGE plpgsql
    AS $$
DECLARE
	r timestamp with time zone;
	STATEMENT TEXT;
	file date;
	year integer;
	month integer;
	path text := '/opt/pgsql/9.3/data_arh/arh';
	tinterval interval;
BEGIN
 -- изменить работу функции на использование целых месяцев для обработки
  -- функция сброса старых данных из gpsarh на файловую систему, ещё не проверялась в работе, так как небыло данных для сброса, необходимо протестировать
    select "value" into tinterval from settings where name='timeout_gpsarh2fs';
    FOR r IN select distinct date_trunc('day',tstamp) from data.gps_arh where tstamp < now() - tinterval
    LOOP
      file := date(r);
      year := date_part('year',file);
      month := date_part('month',file);
      STATEMENT := 'copy (select * from data.gps_arh where tstamp >= ''' || r || ''' and tstamp < ''' || r || '''::timestamp with time zone + interval ''1 day'') TO PROGRAM ''gzip >"'||path||'/in/'||file||'.sql.gz" ; mkdir -p '||path||'/'||"year"||'/'||"month"||'; mv '||path||'/in/'||file||'.sql.gz '||path||'/'||year||'/'||month||'; ''; delete from data.gps_arh where tstamp >= ''' || r || ''' and tstamp < ''' || r || '''::timestamp with time zone + interval ''1 day'';';
      EXECUTE STATEMENT;
      return next (r);
    END LOOP;
--VACUUM data.gps_arh;
 
END
$$;


ALTER FUNCTION data.gm_data_arh2fs() OWNER TO postgres;

--
-- TOC entry 702 (class 1255 OID 339361)
-- Name: gm_data_gps2arh(); Type: FUNCTION; Schema: data; Owner: postgres
--

CREATE FUNCTION gm_data_gps2arh() RETURNS TABLE(deleted integer, inserted integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
	tinterval interval;
	delrow integer;
	insrow integer;
BEGIN
-- в реальных условиях не тестировалось
	select "value" into tinterval from settings where name='timeout_gps2arh';
	with instmp as (insert into data.gps_arh (select * from data.gps where tstamp < date_trunc('month',tstamp) - tinterval) returning *) select count(*) into insrow from instmp;
	with deltmp as (delete from data.gps where tstamp < date_trunc('month',tstamp) - tinterval returning *) select count(*) into delrow from deltmp;
	return query (select insrow,delrow);
	--vacuum data.gps;
END
$$;


ALTER FUNCTION data.gm_data_gps2arh() OWNER TO postgres;

--
-- TOC entry 758 (class 1255 OID 305941)
-- Name: gps_from_trackers(); Type: FUNCTION; Schema: data; Owner: postgres
--

CREATE FUNCTION gps_from_trackers() RETURNS SETOF integer
    LANGUAGE plpgsql
    AS $$declare 
 tinterval interval;
BEGIN
 select "value" into tinterval from settings where name='timeout_gps2arh';

-- *************************************
---  чистим транзитную таблицу от неправильных или ненужных данных
 
-- удаляем из таблицы данные которые пришли за архивный период, так как считаем что эти данные невалидны
-- также удаляем данные за будущие дни через 1 сутки так как с учётом часовых поясов данные могут быть не более чем на сутки вперёд
 delete from data.gps_from_trackers where tstamp < now()-tinterval or tstamp > now() + interval '1 day';
 --create temporary table tmp1 on commit drop as 
 delete from data.gps_from_trackers where imei not in (select imei from trackers);
-- удаляем из транзитной таблицы дублирующие записи
 delete from data.gps_from_trackers as dg1 using data.gps as dg2 where dg2.carid in (select carid from trackers where imei=dg1.imei) and dg1.tstamp=dg2.tstamp;
-- удаляем записи непривязанных к машинам трекеров
delete from data.gps_from_trackers where imei not in (select imei from trackers);
-- удаляем записи которые говорят что данных по машине нет, или ошибочные
--delete from tmp1 where lat=-2147483648 or lon=-2147483648;

-- *************************************

-- делаем выборку во временную таблицу данных которые только поступили
create temporary table tmp1 on commit drop as 
select distinct (select carid from trackers where trackers.imei=gft.imei limit 1) as carid,
       (select trackerid from trackers where trackers.imei=gft.imei limit 1) as trackerid,
       imei,lat,lon,null::integer as latb,null::integer as lonb,
       null::float as flat,null::float as flon,null::float as flatb,null::float as flonb,
       null::float as a,null::float as c,null::float as d,
       null::float as rlat,null::float as rlon,speed,tstamp,null::timestamp with time zone as tstampb,0::integer as dest,
       dut,volt,case when speed=0 then 0 else azimut end as azimut,gsmsignal,gpsdop,signal_restart
   from data.gps_from_trackers as gft
   where class=2;

delete from tmp1 where carid is null;

create temporary table tmp2 on commit drop as 
select imei,tstamp,lat,lon, carid,trackerid,flat,flon,flatb,flonb,a,c,d,rlat,rlon,speed,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,
        lag(tstamp) over (partition by imei order by tstamp) as tstampb,
        lag(lat) over (partition by imei order by tstamp) as latb,
        lag(lon) over (partition by imei order by tstamp) as lonb
        from tmp1 where lat>0 and lon>0 order by imei,tstamp;

--(select tstamp,lat,lon from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1)
update tmp2 set
    tstampb=(select tstamp from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),
    latb=(select lat from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1),
    lonb=(select lon from data.gps as dg where dg.carid=tmp2.carid and dg.tstamp <= tmp2.tstamp and dg.lat>0 and dg.lon>0 order by tstamp desc limit 1)
    where tmp2.tstampb is null;


update tmp2 set flat=lat/600000::real,flon=lon/600000::real,flatb=latb/600000::real,flonb=lonb/600000::real where lat>0 and lon>0;
update tmp2 set dest=GM_dest(flat::real,flon::real,flatb::real,flonb::real) where lat>0 and lon>0;

update tmp1 set tstampb=tmp2.tstampb,latb=tmp2.latb,lonb=tmp2.lonb,dest=tmp2.dest from tmp2 where tmp1.carid=tmp2.carid and tmp1.tstamp=tmp2.tstamp;
-- обновляем таблицу онлайн по новым данным
-- обнуляем расстояние если день пройден
--update data.online set dest_day=(case when extract (day from tstamp)!=extract (day from CURRENT_TIMESTAMP) then 0 else dest_day end);
update data.online set dest_day=0 where extract (day from tstamp)!=extract (day from CURRENT_TIMESTAMP);

-- обновляем online по последним данным и сумируем пройденный путь
create temporary table tmp22 on commit drop as select * from tmp2;
update tmp22 set dest=0,speed=0 where speed<5;
create temporary table tmp3 on commit drop as select carid,max(tstamp) as tstamp ,max(speed) as speed_max,sum(dest) as dest from tmp22 where tstamp >= date_trunc('day', CURRENT_TIMESTAMP) group by carid;

-- update data.online as t1  set 
-- tstamp=(case when t2.tstamp>t1.tstamp then t2.tstamp else t1.tstamp end)
-- --      dest_day=(case when extract (day from t2.tstamp) != extract (day from CURRENT_TIMESTAMP) then t1.dest_day else t1.dest_day+t2.dest end)
-- --      dest_day=t1.dest_day+t2.dest
--         from tmp2 as t2
--         where t1.carid=t2.carid and t2.tstamp = (select tmp3.tstamp from tmp3 where tmp3.carid=t1.carid);

update data.online as t1 set speed_max=t2.speed_max,dest_day=t1.dest_day+t2.dest,tstamp_lastupdate=now(),who_lastupdate='gps_from_trackers'
        from tmp3 as t2
        where t1.carid=t2.carid;
-- добавляем новые данные в постоянную таблицу хранения
insert into data.gps (carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt) (select carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt from tmp1);

-- отчищаем промежуточную таблицу от данных которые были записаны
delete from data.gps_from_trackers as gft using tmp1 where gft.imei=tmp1.imei and gft.tstamp=tmp1.tstamp;
END
$$;


ALTER FUNCTION data.gps_from_trackers() OWNER TO postgres;

--
-- TOC entry 708 (class 1255 OID 998943)
-- Name: mod1_online_update_10(); Type: FUNCTION; Schema: data; Owner: gpsmon
--

CREATE FUNCTION mod1_online_update_10() RETURNS SETOF integer
    LANGUAGE plpgsql
    AS $$DECLARE
 res integer;
BEGIN
--  данном скрипте не учтено наличие 2-х трекеров у одной машины

-- создаем временную таблицу выбирая последние данные по машинам
create temporary table tmp1 on commit drop as 
select 
 (select carid from trackers where trackers.imei=tracker_imei) as carid,
 (select trackerid from trackers where trackers.imei=tracker_imei) as trackerid,
 tracker_imei as imei,
 locations_location1_latitude::integer as lat,
 locations_location1_longitude::integer as lon,
 (locations_location1_latitude::real/600000)::real as latr,
 (locations_location1_longitude::real/600000)::real as lonr,
 locations_location1_azimuth::integer as azimuth,
 locations_location1_speed::integer as speed,
 to_timestamp(tracker_tstamp::integer) as tstamp,
 "gsm_gsm1_rx-level"::integer as gsmsignal,
 "locations_location1_quality"::integer as gpsdop
from vtrackers_in_h 
where (tracker_imei,tracker_tstamp::integer) in (
select 
 tracker_imei as imei,
 max(tracker_tstamp::integer) as tstamp
 from vtrackers_in_h
where
 status=0
 and "class"='2'
 and module_id = '1'
 and to_timestamp(tracker_tstamp::integer)<=now() + interval '1 day'
-- and to_number(locations_location1_latitude,'9')>0
 group by imei
);

select count(*) into res from tmp1;

-- вставляем в online таблицу новые записи по машинам которых там нет
insert into data.online (carid) (select carid from tmp1 where carid not in (select carid from data.online));

-- обновляем таблицу online последней информацией
update data.online as t1 set 
 lat=t2.lat,
 lon=t2.lon,
 azimut=t2.azimuth,
 speed=t2.speed,
 tstamp=t2.tstamp,
 gsmsignal=t2.gsmsignal,
 gpsdop=t2.gpsdop,
 tstamp_lastupdate=now(),
 who_lastupdate='mod1_online_update_10',
 "location"='POINT('|| lonr::varchar ||' '|| latr::varchar||')'
from tmp1 as t2 
where t1.carid=t2.carid and t1.tstamp<=t2.tstamp and t2.lat>0;

-- обновляем статус в json таблице для всех записей у которых время меньше чем online
-- тоесть помечаем исторические данные которые можно дальше обрабатывать
update vtrackers_in set status=1 where id in (select distinct t1.id from vtrackers_in_h as t1,tmp1 as t2 where t1.tracker_imei=t2.imei and to_timestamp(t1.tracker_tstamp::integer)<=t2.tstamp);

RETURN NEXT (res);
END
$$;


ALTER FUNCTION data.mod1_online_update_10() OWNER TO gpsmon;

--
-- TOC entry 706 (class 1255 OID 1007230)
-- Name: online_update(); Type: FUNCTION; Schema: data; Owner: gpsmon
--

CREATE FUNCTION online_update() RETURNS SETOF integer
    LANGUAGE plpgsql
    AS $$
BEGIN
select * from data.mod1_online_update_10();
return next 1;
END
$$;


ALTER FUNCTION data.online_update() OWNER TO gpsmon;

--
-- TOC entry 4774 (class 0 OID 0)
-- Dependencies: 706
-- Name: FUNCTION online_update(); Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON FUNCTION online_update() IS 'обновление онлайн данных ';


--
-- TOC entry 704 (class 1255 OID 1006629)
-- Name: trackers_parse(); Type: FUNCTION; Schema: data; Owner: gpsmon
--

CREATE FUNCTION trackers_parse() RETURNS integer
    LANGUAGE plpgsql
    AS $$
BEGIN
 -- чтото сначала выполняем
 select * from data.online_update();
 
 -- чтото потом выполняем
 -- удаляем историю
 
END
$$;


ALTER FUNCTION data.trackers_parse() OWNER TO gpsmon;

--
-- TOC entry 4775 (class 0 OID 0)
-- Dependencies: 704
-- Name: FUNCTION trackers_parse(); Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON FUNCTION trackers_parse() IS 'консолидированная функция парсинга входящей информации от трекеров';


SET search_path = public, pg_catalog;

--
-- TOC entry 696 (class 1255 OID 17978)
-- Name: first_agg(anyelement, anyelement); Type: FUNCTION; Schema: public; Owner: gpsmon
--

CREATE FUNCTION first_agg(anyelement, anyelement) RETURNS anyelement
    LANGUAGE sql IMMUTABLE STRICT
    AS $_$
        SELECT $1;
$_$;


ALTER FUNCTION public.first_agg(anyelement, anyelement) OWNER TO gpsmon;

--
-- TOC entry 698 (class 1255 OID 327867)
-- Name: gm_dest(real, real, real, real); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION gm_dest(lat1 real, lon1 real, lat2 real, lon2 real) RETURNS SETOF integer
    LANGUAGE plpgsql
    AS $$
declare
 dest real;
BEGIN
 dest := abs(6371000*(2*atan2(
    sqrt(sin(radians(lat2-lat1)/2::real)^2::real+sin(radians(lon2-lon1)/2::real)^2::real*cos(radians(lat1)::real)::real*cos(radians(lat2)::real)::real)::real,
    sqrt(1-(sin(radians(lat2-lat1)/2::real)^2::real+sin(radians(lon2-lon1)/2::real)^2::real*cos(radians(lat1)::real)::real*cos(radians(lat2)::real)::real))::real)::real)::real);

 if dest is null then 
  dest := 0;
 end if;
 
 return next (dest::integer);
END
$$;


ALTER FUNCTION public.gm_dest(lat1 real, lon1 real, lat2 real, lon2 real) OWNER TO postgres;

--
-- TOC entry 697 (class 1255 OID 17979)
-- Name: last_agg(anyelement, anyelement); Type: FUNCTION; Schema: public; Owner: gpsmon
--

CREATE FUNCTION last_agg(anyelement, anyelement) RETURNS anyelement
    LANGUAGE sql IMMUTABLE STRICT
    AS $_$
        SELECT $2;
$_$;


ALTER FUNCTION public.last_agg(anyelement, anyelement) OWNER TO gpsmon;

--
-- TOC entry 699 (class 1255 OID 972936)
-- Name: ussd_setsend(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION ussd_setsend(i_ussdid integer) RETURNS SETOF integer
    LANGUAGE plpgsql
    AS $$DECLARE
 c integer;
BEGIN
 select count(*) into c from data.ussd where ussdid=i_ussdid;
 update data.ussd set status=1 where ussdid=i_ussdid;
 insert into log.ussd_status (ussdid,status) values (i_ussdid,1);
 return next (c);
END
$$;


ALTER FUNCTION public.ussd_setsend(i_ussdid integer) OWNER TO postgres;

--
-- TOC entry 2299 (class 1255 OID 17980)
-- Name: first(anyelement); Type: AGGREGATE; Schema: public; Owner: gpsmon
--

CREATE AGGREGATE first(anyelement) (
    SFUNC = first_agg,
    STYPE = anyelement
);


ALTER AGGREGATE public.first(anyelement) OWNER TO gpsmon;

--
-- TOC entry 2300 (class 1255 OID 17981)
-- Name: last(anyelement); Type: AGGREGATE; Schema: public; Owner: gpsmon
--

CREATE AGGREGATE last(anyelement) (
    SFUNC = last_agg,
    STYPE = anyelement
);


ALTER AGGREGATE public.last(anyelement) OWNER TO gpsmon;

SET search_path = cfg, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 678 (class 1259 OID 1867286)
-- Name: icon; Type: TABLE; Schema: cfg; Owner: gpsmon; Tablespace: 
--

CREATE TABLE icon (
    iconid integer NOT NULL,
    groupid integer,
    iconname character varying
);


ALTER TABLE cfg.icon OWNER TO gpsmon;

--
-- TOC entry 676 (class 1259 OID 1867193)
-- Name: icon_group; Type: TABLE; Schema: cfg; Owner: gpsmon; Tablespace: 
--

CREATE TABLE icon_group (
    groupid integer NOT NULL,
    groupname character varying,
    iconname character varying,
    sortnum integer,
    active boolean
);


ALTER TABLE cfg.icon_group OWNER TO gpsmon;

--
-- TOC entry 4776 (class 0 OID 0)
-- Dependencies: 676
-- Name: TABLE icon_group; Type: COMMENT; Schema: cfg; Owner: gpsmon
--

COMMENT ON TABLE icon_group IS 'группы иконок';


--
-- TOC entry 675 (class 1259 OID 1867191)
-- Name: icon_group_groupid_seq; Type: SEQUENCE; Schema: cfg; Owner: gpsmon
--

CREATE SEQUENCE icon_group_groupid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE cfg.icon_group_groupid_seq OWNER TO gpsmon;

--
-- TOC entry 4777 (class 0 OID 0)
-- Dependencies: 675
-- Name: icon_group_groupid_seq; Type: SEQUENCE OWNED BY; Schema: cfg; Owner: gpsmon
--

ALTER SEQUENCE icon_group_groupid_seq OWNED BY icon_group.groupid;


--
-- TOC entry 677 (class 1259 OID 1867284)
-- Name: icon_iconid_seq; Type: SEQUENCE; Schema: cfg; Owner: gpsmon
--

CREATE SEQUENCE icon_iconid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE cfg.icon_iconid_seq OWNER TO gpsmon;

--
-- TOC entry 4778 (class 0 OID 0)
-- Dependencies: 677
-- Name: icon_iconid_seq; Type: SEQUENCE OWNED BY; Schema: cfg; Owner: gpsmon
--

ALTER SEQUENCE icon_iconid_seq OWNED BY icon.iconid;


--
-- TOC entry 679 (class 1259 OID 1867685)
-- Name: vicon; Type: VIEW; Schema: cfg; Owner: gpsmon
--

CREATE VIEW vicon AS
 SELECT t1.iconid,
    t1.groupid,
    t1.iconname
   FROM (icon t1
   LEFT JOIN icon_group t2 USING (groupid))
  WHERE (t2.active = true)
  ORDER BY t1.groupid, t1.iconid;


ALTER TABLE cfg.vicon OWNER TO gpsmon;

--
-- TOC entry 680 (class 1259 OID 1867717)
-- Name: vicon_group; Type: VIEW; Schema: cfg; Owner: gpsmon
--

CREATE VIEW vicon_group AS
 SELECT icon_group.groupid,
    icon_group.groupname,
    icon_group.iconname
   FROM icon_group
  WHERE (icon_group.active = true)
  ORDER BY icon_group.sortnum;


ALTER TABLE cfg.vicon_group OWNER TO gpsmon;

SET search_path = data, pg_catalog;

SET default_tablespace = gpsdata;

--
-- TOC entry 643 (class 1259 OID 18092)
-- Name: gps; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

CREATE TABLE gps (
    carid integer DEFAULT (-1) NOT NULL,
    trackerid integer NOT NULL,
    tstamp timestamp with time zone NOT NULL,
    lat integer NOT NULL,
    lon integer NOT NULL,
    speed integer,
    dest integer,
    dut integer[],
    azimut integer,
    gsmsignal integer,
    gpsdop integer,
    signal_restart integer,
    volt integer[]
);


ALTER TABLE data.gps OWNER TO gpsmon;

SET default_tablespace = gpsdataarh;

--
-- TOC entry 645 (class 1259 OID 53453)
-- Name: gps_arh; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: gpsdataarh
--

CREATE TABLE gps_arh (
    carid integer,
    trackerid integer,
    tstamp timestamp with time zone,
    lat integer,
    lon integer,
    speed integer,
    dest integer,
    dut integer[],
    azimut integer,
    gsmsignal integer,
    gpsdop integer,
    signal_restart integer,
    volt integer[]
);


ALTER TABLE data.gps_arh OWNER TO gpsmon;

SET default_tablespace = '';

--
-- TOC entry 623 (class 1259 OID 17982)
-- Name: gps_from_trackers; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE TABLE gps_from_trackers (
    imei character varying NOT NULL,
    tstamp timestamp with time zone NOT NULL,
    lat integer NOT NULL,
    lon integer NOT NULL,
    speed integer NOT NULL,
    dut integer[],
    azimut integer,
    gsmsignal integer,
    gpsdop integer,
    signal_restart integer,
    volt integer[],
    class integer
);


ALTER TABLE data.gps_from_trackers OWNER TO gpsmon;

--
-- TOC entry 624 (class 1259 OID 17988)
-- Name: imei_not_db; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE TABLE imei_not_db (
    imei character varying,
    lastsend timestamp with time zone
);


ALTER TABLE data.imei_not_db OWNER TO gpsmon;

--
-- TOC entry 674 (class 1259 OID 1705654)
-- Name: location; Type: TABLE; Schema: data; Owner: postgres; Tablespace: 
--

CREATE TABLE location (
    locationid bigint NOT NULL,
    objectid integer,
    fromid integer,
    tstamp_begin timestamp with time zone,
    tstamp_end timestamp with time zone,
    location gis.geometry
);


ALTER TABLE data.location OWNER TO postgres;

--
-- TOC entry 4779 (class 0 OID 0)
-- Dependencies: 674
-- Name: TABLE location; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON TABLE location IS 'пройденные прямые отрезки пути и стоянки ';


--
-- TOC entry 4780 (class 0 OID 0)
-- Dependencies: 674
-- Name: COLUMN location.locationid; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON COLUMN location.locationid IS 'id отрезка';


--
-- TOC entry 4781 (class 0 OID 0)
-- Dependencies: 674
-- Name: COLUMN location.objectid; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON COLUMN location.objectid IS 'id объекта мониторинга';


--
-- TOC entry 4782 (class 0 OID 0)
-- Dependencies: 674
-- Name: COLUMN location.fromid; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON COLUMN location.fromid IS 'id устройства с которого были отправленны данные';


--
-- TOC entry 4783 (class 0 OID 0)
-- Dependencies: 674
-- Name: COLUMN location.tstamp_begin; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON COLUMN location.tstamp_begin IS 'время начала отрезка';


--
-- TOC entry 4784 (class 0 OID 0)
-- Dependencies: 674
-- Name: COLUMN location.tstamp_end; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON COLUMN location.tstamp_end IS 'всера конца отрезка';


--
-- TOC entry 4785 (class 0 OID 0)
-- Dependencies: 674
-- Name: COLUMN location.location; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON COLUMN location.location IS 'геометрия отрезка
line - движение
point - стоянка';


--
-- TOC entry 673 (class 1259 OID 1705652)
-- Name: location_locationid_seq; Type: SEQUENCE; Schema: data; Owner: postgres
--

CREATE SEQUENCE location_locationid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE data.location_locationid_seq OWNER TO postgres;

--
-- TOC entry 4786 (class 0 OID 0)
-- Dependencies: 673
-- Name: location_locationid_seq; Type: SEQUENCE OWNED BY; Schema: data; Owner: postgres
--

ALTER SEQUENCE location_locationid_seq OWNED BY location.locationid;


--
-- TOC entry 625 (class 1259 OID 17994)
-- Name: online; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE TABLE online (
    carid integer DEFAULT (-1) NOT NULL,
    trackerid integer,
    tstamp timestamp with time zone NOT NULL,
    lat integer NOT NULL,
    lon integer NOT NULL,
    speed integer NOT NULL,
    dest_day integer DEFAULT 0 NOT NULL,
    dut integer[],
    speed_max integer DEFAULT 0 NOT NULL,
    speed_sr integer DEFAULT 0 NOT NULL,
    azimut integer,
    gsmsignal integer,
    gpsdop integer,
    tstamp_lastupdate timestamp with time zone,
    who_lastupdate character varying,
    location gis.geometry
);


ALTER TABLE data.online OWNER TO gpsmon;

SET default_tablespace = gpsdata;

--
-- TOC entry 647 (class 1259 OID 354684)
-- Name: trackers; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

CREATE TABLE trackers (
    id bigint NOT NULL,
    tstampdb timestamp with time zone DEFAULT now() NOT NULL,
    json_data json,
    status integer DEFAULT 0 NOT NULL
);


ALTER TABLE data.trackers OWNER TO gpsmon;

--
-- TOC entry 4787 (class 0 OID 0)
-- Dependencies: 647
-- Name: TABLE trackers; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON TABLE trackers IS 'json данные от трекеров';


--
-- TOC entry 4788 (class 0 OID 0)
-- Dependencies: 647
-- Name: COLUMN trackers.id; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN trackers.id IS 'порядковый номер';


--
-- TOC entry 4789 (class 0 OID 0)
-- Dependencies: 647
-- Name: COLUMN trackers.tstampdb; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN trackers.tstampdb IS 'время записи данных в базу';


--
-- TOC entry 4790 (class 0 OID 0)
-- Dependencies: 647
-- Name: COLUMN trackers.json_data; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN trackers.json_data IS 'данные ';


--
-- TOC entry 646 (class 1259 OID 354682)
-- Name: trackers_id_seq; Type: SEQUENCE; Schema: data; Owner: gpsmon
--

CREATE SEQUENCE trackers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE data.trackers_id_seq OWNER TO gpsmon;

--
-- TOC entry 4791 (class 0 OID 0)
-- Dependencies: 646
-- Name: trackers_id_seq; Type: SEQUENCE OWNED BY; Schema: data; Owner: gpsmon
--

ALTER SEQUENCE trackers_id_seq OWNED BY trackers.id;


SET default_tablespace = '';

--
-- TOC entry 668 (class 1259 OID 972459)
-- Name: ussd; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE TABLE ussd (
    ussdid integer NOT NULL,
    tstamp_create timestamp with time zone DEFAULT now() NOT NULL,
    tstamp_lastupdate timestamp with time zone DEFAULT now() NOT NULL,
    trackerid integer NOT NULL,
    status integer DEFAULT 0 NOT NULL,
    ussd_text character varying
);


ALTER TABLE data.ussd OWNER TO gpsmon;

--
-- TOC entry 4792 (class 0 OID 0)
-- Dependencies: 668
-- Name: COLUMN ussd.status; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN ussd.status IS '0 - необходимо отправить
1 - отправлен
2 - получен ответ
3 - перенесён в архив
4 - удалён
';


--
-- TOC entry 4793 (class 0 OID 0)
-- Dependencies: 668
-- Name: COLUMN ussd.ussd_text; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN ussd.ussd_text IS 'сам текст ussd запроса';


--
-- TOC entry 667 (class 1259 OID 972457)
-- Name: ussd_ussdid_seq; Type: SEQUENCE; Schema: data; Owner: gpsmon
--

CREATE SEQUENCE ussd_ussdid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE data.ussd_ussdid_seq OWNER TO gpsmon;

--
-- TOC entry 4794 (class 0 OID 0)
-- Dependencies: 667
-- Name: ussd_ussdid_seq; Type: SEQUENCE OWNED BY; Schema: data; Owner: gpsmon
--

ALTER SEQUENCE ussd_ussdid_seq OWNED BY ussd.ussdid;


--
-- TOC entry 663 (class 1259 OID 788813)
-- Name: vtrackers_h_v010; Type: VIEW; Schema: data; Owner: postgres
--

CREATE VIEW vtrackers_h_v010 AS
 SELECT trackers.id,
    trackers.tstampdb,
    trackers.status,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['class'::text]))::integer AS class,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['jsonversion'::text]))::character varying AS jsonversion,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'name'::text]))::character varying AS module_name,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'version'::text]))::character varying AS module_version,
    to_timestamp(((json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'tstamp'::text]))::integer)::double precision) AS module_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'imei'::text]))::character varying AS tracker_imei,
    to_timestamp(((json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'tstamp'::text]))::integer)::double precision) AS tracker_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'version'::text]))::character varying AS tracker_version,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot_flag'::text]))::character varying AS tracker_bootflag,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'location_area_code'::text]))::character varying AS tracker_gsm_areacode,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'cell_index'::text]))::character varying AS tracker_gsm_cellindex,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mcc'::text]))::character varying AS tracker_gsm_mcc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mnc'::text]))::character varying AS tracker_gsm_mnc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'rx_level'::text]))::character varying AS tracker_gsm_rxlevel,
    to_timestamp(((json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'tstamp'::text]))::integer)::double precision) AS locations_location1_tstamp,
    ((json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'latitude'::text]))::double precision / (600000)::double precision) AS locations_location1_latitude,
    ((json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'longitude'::text]))::double precision / (600000)::double precision) AS locations_location1_longitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'altitude'::text]))::character varying AS locations_location1_altitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'speed'::text]))::character varying AS locations_location1_speed,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'azimut'::text]))::character varying AS locations_location1_azimut,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'quality'::text]))::character varying AS locations_location1_quality,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'temperature'::text, 'tracker'::text]))::character varying AS sensors_temperature_tracker,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'external'::text, 'ext1'::text]))::character varying AS sensors_voltage_external_ext1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'internal'::text, 'int1'::text]))::character varying AS sensors_voltage_internal_int1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut1'::text]))::character varying AS sensors_dut_dut1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut2'::text]))::character varying AS sensors_dut_dut2
   FROM trackers;


ALTER TABLE data.vtrackers_h_v010 OWNER TO postgres;

--
-- TOC entry 4795 (class 0 OID 0)
-- Dependencies: 663
-- Name: VIEW vtrackers_h_v010; Type: COMMENT; Schema: data; Owner: postgres
--

COMMENT ON VIEW vtrackers_h_v010 IS 'парсинг входящей информации по версии протокола 0.1.0';


--
-- TOC entry 664 (class 1259 OID 789781)
-- Name: vtrackers_h_v012; Type: VIEW; Schema: data; Owner: gpsmon
--

CREATE VIEW vtrackers_h_v012 AS
 SELECT trackers.id,
    trackers.tstampdb,
    trackers.status,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['class'::text]))::integer AS class,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['jsonversion'::text]))::character varying AS jsonversion,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'name'::text]))::character varying AS module_name,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'version'::text]))::character varying AS module_version,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'tstamp'::text]))::character varying AS module_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'imei'::text]))::character varying AS tracker_imei,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'tstamp'::text]))::character varying AS tracker_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'version'::text]))::character varying AS tracker_version,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot_flag'::text]))::character varying AS tracker_bootflag,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'location-area-code'::text]))::character varying AS "tracker_gsm_location-area-code",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'cell-index'::text]))::character varying AS "tracker_gsm_cell-index",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mcc'::text]))::character varying AS tracker_gsm_mcc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mnc'::text]))::character varying AS tracker_gsm_mnc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'rx-level'::text]))::character varying AS "tracker_gsm_rx-level",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'tstamp'::text]))::character varying AS locations_location1_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'latitude'::text]))::character varying AS locations_location1_latitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'longitude'::text]))::character varying AS locations_location1_longitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'altitude'::text]))::character varying AS locations_location1_altitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'speed'::text]))::character varying AS locations_location1_speed,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'azimut'::text]))::character varying AS locations_location1_azimut,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'quality'::text]))::character varying AS locations_location1_quality,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'temperature'::text, 'tracker'::text]))::character varying AS sensors_temperature_tracker,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'external'::text, 'ext1'::text]))::character varying AS sensors_voltage_external_ext1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'internal'::text, 'int1'::text]))::character varying AS sensors_voltage_internal_int1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut1'::text]))::character varying AS sensors_dut_dut1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut2'::text]))::character varying AS sensors_dut_dut2
   FROM trackers;


ALTER TABLE data.vtrackers_h_v012 OWNER TO gpsmon;

--
-- TOC entry 665 (class 1259 OID 971153)
-- Name: vtrackers_h_v013; Type: VIEW; Schema: data; Owner: gpsmon
--

CREATE VIEW vtrackers_h_v013 AS
 SELECT trackers.id,
    trackers.tstampdb,
    trackers.status,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['jsonversion'::text]))::character varying AS jsonversion,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['class'::text]))::character varying AS class,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'name'::text]))::character varying AS module_name,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'version'::text]))::character varying AS module_version,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'tstamp'::text]))::character varying AS module_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'imei'::text]))::character varying AS tracker_imei,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'tstamp'::text]))::character varying AS tracker_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'versionhw'::text]))::character varying AS tracker_versionhw,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'versionsw'::text]))::character varying AS tracker_versionsw,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot-flag'::text]))::character varying AS "tracker_boot-flag",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot-info'::text]))::character varying AS "tracker_boot-info",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'location-area-code'::text]))::character varying AS "tracker_gsm_location-area-code",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'cell-index'::text]))::character varying AS "tracker_gsm_cell-index",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mcc'::text]))::character varying AS tracker_gsm_mcc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mnc'::text]))::character varying AS tracker_gsm_mnc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'rx-level'::text]))::character varying AS "tracker_gsm_rx-level",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'tstamp'::text]))::character varying AS locations_location1_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'latitude'::text]))::character varying AS locations_location1_latitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'longitude'::text]))::character varying AS locations_location1_longitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'altitude'::text]))::character varying AS locations_location1_altitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'speed'::text]))::character varying AS locations_location1_speed,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'azimuth'::text]))::character varying AS locations_location1_azimuth,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'satellites'::text]))::character varying AS locations_location1_satellites,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'data-valid'::text]))::character varying AS "locations_location1_data-valid",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'worked'::text]))::character varying AS locations_location1_worked,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'quality'::text]))::character varying AS locations_location1_quality,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'temperature'::text, 'tracker'::text]))::character varying AS sensors_temperature_tracker,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'external'::text, 'ext1'::text]))::character varying AS sensors_voltage_external_ext1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'internal'::text, 'int1'::text]))::character varying AS sensors_voltage_internal_int1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut1'::text]))::character varying AS sensors_dut_dut1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut2'::text]))::character varying AS sensors_dut_dut2
   FROM trackers;


ALTER TABLE data.vtrackers_h_v013 OWNER TO gpsmon;

--
-- TOC entry 666 (class 1259 OID 972276)
-- Name: vtrackers_h_v014; Type: VIEW; Schema: data; Owner: gpsmon
--

CREATE VIEW vtrackers_h_v014 AS
 SELECT trackers.id,
    trackers.tstampdb,
    trackers.status,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['jsonversion'::text]))::character varying AS jsonversion,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['class'::text]))::character varying AS class,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'name'::text]))::character varying AS module_name,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'version'::text]))::character varying AS module_version,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'tstamp'::text]))::character varying AS module_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'imei'::text]))::character varying AS tracker_imei,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'tstamp'::text]))::character varying AS tracker_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'versionhw'::text]))::character varying AS tracker_versionhw,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'versionsw'::text]))::character varying AS tracker_versionsw,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot-flag'::text]))::character varying AS "tracker_boot-flag",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot-info'::text]))::character varying AS "tracker_boot-info",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'location-area-code'::text]))::character varying AS "tracker_gsm_location-area-code",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'cell-index'::text]))::character varying AS "tracker_gsm_cell-index",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mcc'::text]))::character varying AS tracker_gsm_mcc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'mnc'::text]))::character varying AS tracker_gsm_mnc,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'rx-level'::text]))::character varying AS "tracker_gsm_rx-level",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'ussd-request'::text]))::character varying AS "tracker_gsm_ussd-request",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'gsm'::text, 'ussd-response'::text]))::character varying AS "tracker_gsm_ussd-response",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'tstamp'::text]))::character varying AS locations_location1_tstamp,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'latitude'::text]))::character varying AS locations_location1_latitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'longitude'::text]))::character varying AS locations_location1_longitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'altitude'::text]))::character varying AS locations_location1_altitude,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'speed'::text]))::character varying AS locations_location1_speed,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'azimuth'::text]))::character varying AS locations_location1_azimuth,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'satellites'::text]))::character varying AS locations_location1_satellites,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'data-valid'::text]))::character varying AS "locations_location1_data-valid",
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'worked'::text]))::character varying AS locations_location1_worked,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'quality'::text]))::character varying AS locations_location1_quality,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'temperature'::text, 'tracker'::text]))::character varying AS sensors_temperature_tracker,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'external'::text, 'ext1'::text]))::character varying AS sensors_voltage_external_ext1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'internal'::text, 'int1'::text]))::character varying AS sensors_voltage_internal_int1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut1'::text]))::character varying AS sensors_dut_dut1,
    (json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut2'::text]))::character varying AS sensors_dut_dut2
   FROM trackers;


ALTER TABLE data.vtrackers_h_v014 OWNER TO gpsmon;

--
-- TOC entry 671 (class 1259 OID 1080874)
-- Name: vtrackers_h_v015; Type: VIEW; Schema: data; Owner: gpsmon
--

CREATE VIEW vtrackers_h_v015 AS
 SELECT trackers.id,
    trackers.tstampdb,
    trackers.status,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['jsonversion'::text]) AS jsonversion,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['class'::text]) AS class,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'id'::text]) AS module_id,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'name'::text]) AS module_name,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'info'::text]) AS module_info,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'version'::text]) AS module_version,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['module'::text, 'tstamp'::text]) AS module_tstamp,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'imei'::text]) AS tracker_imei,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'tstamp'::text]) AS tracker_tstamp,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'versionhw'::text]) AS tracker_versionhw,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'versionsw'::text]) AS tracker_versionsw,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot-flag'::text]) AS "tracker_boot-flag",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['tracker'::text, 'boot-info'::text]) AS "tracker_boot-info",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'location-area-code'::text]) AS "gsm_gsm1_location-area-code",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'cell-index'::text]) AS "gsm_gsm1_cell-index",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'mcc'::text]) AS gsm_gsm1_mcc,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'mnc'::text]) AS gsm_gsm1_mnc,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'rx-level'::text]) AS "gsm_gsm1_rx-level",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'ussd-request'::text]) AS "gsm_gsm1_ussd-request",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['gsm'::text, 'gsm1'::text, 'ussd-response'::text]) AS "gsm_gsm1_ussd-response",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'tstamp'::text]) AS locations_location1_tstamp,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'latitude'::text]) AS locations_location1_latitude,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'longitude'::text]) AS locations_location1_longitude,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'altitude'::text]) AS locations_location1_altitude,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'speed'::text]) AS locations_location1_speed,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'azimuth'::text]) AS locations_location1_azimuth,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'satellites'::text]) AS locations_location1_satellites,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'data-valid'::text]) AS "locations_location1_data-valid",
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'worked'::text]) AS locations_location1_worked,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['locations'::text, 'location1'::text, 'quality'::text]) AS locations_location1_quality,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'temperature'::text, 'tracker'::text]) AS sensors_temperature_tracker,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'external'::text, 'ext1'::text]) AS sensors_voltage_external_ext1,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'voltage'::text, 'internal'::text, 'int1'::text]) AS sensors_voltage_internal_int1,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut1'::text]) AS sensors_dut_dut1,
    json_extract_path_text(trackers.json_data, VARIADIC ARRAY['sensors'::text, 'dut'::text, 'dut2'::text]) AS sensors_dut_dut2
   FROM trackers;


ALTER TABLE data.vtrackers_h_v015 OWNER TO gpsmon;

SET search_path = log, pg_catalog;

--
-- TOC entry 669 (class 1259 OID 972554)
-- Name: ussd_status; Type: TABLE; Schema: log; Owner: gpsmon; Tablespace: 
--

CREATE TABLE ussd_status (
    ussdid integer,
    tstamp timestamp with time zone DEFAULT now() NOT NULL,
    status integer
);


ALTER TABLE log.ussd_status OWNER TO gpsmon;

--
-- TOC entry 4796 (class 0 OID 0)
-- Dependencies: 669
-- Name: TABLE ussd_status; Type: COMMENT; Schema: log; Owner: gpsmon
--

COMMENT ON TABLE ussd_status IS 'изменение статусов ussd запросов';


--
-- TOC entry 4797 (class 0 OID 0)
-- Dependencies: 669
-- Name: COLUMN ussd_status.ussdid; Type: COMMENT; Schema: log; Owner: gpsmon
--

COMMENT ON COLUMN ussd_status.ussdid IS 'номер ussd запроса';


--
-- TOC entry 4798 (class 0 OID 0)
-- Dependencies: 669
-- Name: COLUMN ussd_status.tstamp; Type: COMMENT; Schema: log; Owner: gpsmon
--

COMMENT ON COLUMN ussd_status.tstamp IS 'время изменения';


--
-- TOC entry 4799 (class 0 OID 0)
-- Dependencies: 669
-- Name: COLUMN ussd_status.status; Type: COMMENT; Schema: log; Owner: gpsmon
--

COMMENT ON COLUMN ussd_status.status IS 'новый статус';


SET search_path = olap, pg_catalog;

--
-- TOC entry 626 (class 1259 OID 18004)
-- Name: gps_average_day; Type: TABLE; Schema: olap; Owner: gpsmon; Tablespace: 
--

CREATE TABLE gps_average_day (
    carid integer NOT NULL,
    day date NOT NULL,
    speed_max integer,
    dest integer,
    fill_up_count integer,
    fill_down_count integer,
    fill_up_litr integer,
    fill_down_litr integer,
    time_move_seconds integer,
    time_stop_seconds integer,
    time_moto_seconds integer,
    speed_sum integer,
    counts integer
);


ALTER TABLE olap.gps_average_day OWNER TO gpsmon;

SET search_path = public, pg_catalog;

--
-- TOC entry 627 (class 1259 OID 18007)
-- Name: cars; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE cars (
    carid integer NOT NULL,
    clientid integer NOT NULL,
    name character varying,
    gosnum character varying NOT NULL,
    icon character varying DEFAULT 'car1.png'::character varying,
    maxspeed integer,
    dutmin integer[],
    dutmax integer[],
    dutlitr integer[],
    fiodriver1 character varying,
    teldriver1 character varying,
    deadzone integer[]
);


ALTER TABLE public.cars OWNER TO gpsmon;

--
-- TOC entry 628 (class 1259 OID 18014)
-- Name: cars_carsid_seq; Type: SEQUENCE; Schema: public; Owner: gpsmon
--

CREATE SEQUENCE cars_carsid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cars_carsid_seq OWNER TO gpsmon;

--
-- TOC entry 4800 (class 0 OID 0)
-- Dependencies: 628
-- Name: cars_carsid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE cars_carsid_seq OWNED BY cars.carid;


--
-- TOC entry 629 (class 1259 OID 18016)
-- Name: clients; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE clients (
    clientid integer NOT NULL,
    company_name character varying NOT NULL,
    pays_client integer NOT NULL,
    map_center_lat integer,
    map_center_lon integer,
    map_center_zoom integer
);


ALTER TABLE public.clients OWNER TO gpsmon;

--
-- TOC entry 630 (class 1259 OID 18022)
-- Name: clients_clientid_seq; Type: SEQUENCE; Schema: public; Owner: gpsmon
--

CREATE SEQUENCE clients_clientid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clients_clientid_seq OWNER TO gpsmon;

--
-- TOC entry 4801 (class 0 OID 0)
-- Dependencies: 630
-- Name: clients_clientid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE clients_clientid_seq OWNED BY clients.clientid;


--
-- TOC entry 631 (class 1259 OID 18024)
-- Name: geozones; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE geozones (
    id smallint NOT NULL,
    carid smallint NOT NULL,
    name character varying(255),
    type character varying(16),
    lat numeric(20,15)[],
    lon numeric(20,15)[],
    radius numeric(20,15),
    contains_car boolean
);


ALTER TABLE public.geozones OWNER TO gpsmon;

--
-- TOC entry 632 (class 1259 OID 18030)
-- Name: geozone_id_seq; Type: SEQUENCE; Schema: public; Owner: gpsmon
--

CREATE SEQUENCE geozone_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.geozone_id_seq OWNER TO gpsmon;

--
-- TOC entry 4802 (class 0 OID 0)
-- Dependencies: 632
-- Name: geozone_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE geozone_id_seq OWNED BY geozones.id;


--
-- TOC entry 644 (class 1259 OID 18101)
-- Name: gpsdatafull; Type: VIEW; Schema: public; Owner: gpsmon
--

CREATE VIEW gpsdatafull AS
 SELECT gps.carid,
    gps.trackerid,
    gps.tstamp,
    ((gps.lat)::double precision / (600000)::real) AS lat,
    ((gps.lon)::double precision / (600000)::real) AS lon,
    gps.speed,
    gps.dest,
    gps.dut,
    gps.azimut,
    gps.gsmsignal,
    gps.gpsdop,
    gps.signal_restart,
    gps.volt
   FROM data.gps;


ALTER TABLE public.gpsdatafull OWNER TO gpsmon;

--
-- TOC entry 4803 (class 0 OID 0)
-- Dependencies: 644
-- Name: VIEW gpsdatafull; Type: COMMENT; Schema: public; Owner: gpsmon
--

COMMENT ON VIEW gpsdatafull IS 'обработанные данные по объектам, для выборок, отчётов, построения треков и тд.';


--
-- TOC entry 633 (class 1259 OID 18032)
-- Name: logs; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE logs (
    logid integer NOT NULL,
    tstamp timestamp with time zone DEFAULT now() NOT NULL,
    userid integer NOT NULL,
    text character varying NOT NULL
);


ALTER TABLE public.logs OWNER TO gpsmon;

--
-- TOC entry 634 (class 1259 OID 18039)
-- Name: logs_logid_seq; Type: SEQUENCE; Schema: public; Owner: gpsmon
--

CREATE SEQUENCE logs_logid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.logs_logid_seq OWNER TO gpsmon;

--
-- TOC entry 4805 (class 0 OID 0)
-- Dependencies: 634
-- Name: logs_logid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE logs_logid_seq OWNED BY logs.logid;


--
-- TOC entry 635 (class 1259 OID 18041)
-- Name: phonecard; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE phonecard (
    number character varying NOT NULL,
    trackerid integer NOT NULL
);


ALTER TABLE public.phonecard OWNER TO gpsmon;

--
-- TOC entry 662 (class 1259 OID 590998)
-- Name: reconfigure; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE reconfigure (
    imei character varying,
    passwd character varying,
    tstamp timestamp without time zone,
    status integer
);


ALTER TABLE public.reconfigure OWNER TO gpsmon;

--
-- TOC entry 636 (class 1259 OID 18047)
-- Name: sessions; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE sessions (
    userid integer NOT NULL,
    typeses integer NOT NULL,
    session character varying NOT NULL,
    datedrop timestamp with time zone NOT NULL
);


ALTER TABLE public.sessions OWNER TO gpsmon;

--
-- TOC entry 637 (class 1259 OID 18053)
-- Name: settings; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE settings (
    name character varying(64),
    value character varying(255),
    info character varying
);


ALTER TABLE public.settings OWNER TO gpsmon;

--
-- TOC entry 638 (class 1259 OID 18062)
-- Name: trackers; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE trackers (
    trackerid integer NOT NULL,
    carid integer,
    imei character varying NOT NULL,
    phone character varying,
    passwd character varying,
    clientid integer,
    serialnum integer
);


ALTER TABLE public.trackers OWNER TO gpsmon;

--
-- TOC entry 639 (class 1259 OID 18068)
-- Name: trackers_trackerid_seq; Type: SEQUENCE; Schema: public; Owner: gpsmon
--

CREATE SEQUENCE trackers_trackerid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.trackers_trackerid_seq OWNER TO gpsmon;

--
-- TOC entry 4806 (class 0 OID 0)
-- Dependencies: 639
-- Name: trackers_trackerid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE trackers_trackerid_seq OWNED BY trackers.trackerid;


--
-- TOC entry 640 (class 1259 OID 18070)
-- Name: users; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE users (
    userid integer NOT NULL,
    login character varying NOT NULL,
    password character varying NOT NULL,
    clientid integer NOT NULL,
    fam character varying,
    name character varying,
    otch character varying,
    secure integer DEFAULT (-1),
    map_center_lat integer,
    map_center_lon integer,
    map_center_zoom integer,
    show_direction integer DEFAULT 0 NOT NULL,
    show_webnotify boolean DEFAULT false
);


ALTER TABLE public.users OWNER TO gpsmon;

--
-- TOC entry 641 (class 1259 OID 18079)
-- Name: users_cars; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE users_cars (
    userid integer,
    carid integer
);


ALTER TABLE public.users_cars OWNER TO gpsmon;

--
-- TOC entry 642 (class 1259 OID 18082)
-- Name: users_userid_seq; Type: SEQUENCE; Schema: public; Owner: gpsmon
--

CREATE SEQUENCE users_userid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_userid_seq OWNER TO gpsmon;

--
-- TOC entry 4807 (class 0 OID 0)
-- Dependencies: 642
-- Name: users_userid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE users_userid_seq OWNED BY users.userid;


--
-- TOC entry 670 (class 1259 OID 972713)
-- Name: ussd_forsend; Type: VIEW; Schema: public; Owner: gpsmon
--

CREATE VIEW ussd_forsend AS
 SELECT dus.ussdid,
    dus.tstamp_lastupdate AS tstamp,
    dus.ussd_text,
    ( SELECT trackers.imei
           FROM trackers
          WHERE (trackers.trackerid = dus.trackerid)) AS imei
   FROM data.ussd dus
  WHERE (dus.status = 0);


ALTER TABLE public.ussd_forsend OWNER TO gpsmon;

--
-- TOC entry 4808 (class 0 OID 0)
-- Dependencies: 670
-- Name: VIEW ussd_forsend; Type: COMMENT; Schema: public; Owner: gpsmon
--

COMMENT ON VIEW ussd_forsend IS 'список запросов которые неоходимо отправить';


--
-- TOC entry 648 (class 1259 OID 359006)
-- Name: vtrackers_in; Type: VIEW; Schema: public; Owner: gpsmon
--

CREATE VIEW vtrackers_in AS
 SELECT trackers.json_data,
    trackers.id,
    trackers.status,
    trackers.tstampdb
   FROM data.trackers;


ALTER TABLE public.vtrackers_in OWNER TO gpsmon;

--
-- TOC entry 4809 (class 0 OID 0)
-- Dependencies: 648
-- Name: VIEW vtrackers_in; Type: COMMENT; Schema: public; Owner: gpsmon
--

COMMENT ON VIEW vtrackers_in IS 'данные от трекеров';


--
-- TOC entry 672 (class 1259 OID 1169883)
-- Name: vtrackers_in_h; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW vtrackers_in_h AS
 SELECT vtrackers_h_v015.id,
    vtrackers_h_v015.tstampdb,
    vtrackers_h_v015.status,
    vtrackers_h_v015.jsonversion,
    vtrackers_h_v015.class,
    vtrackers_h_v015.module_id,
    vtrackers_h_v015.module_name,
    vtrackers_h_v015.module_info,
    vtrackers_h_v015.module_version,
    vtrackers_h_v015.module_tstamp,
    vtrackers_h_v015.tracker_imei,
    vtrackers_h_v015.tracker_tstamp,
    vtrackers_h_v015.tracker_versionhw,
    vtrackers_h_v015.tracker_versionsw,
    vtrackers_h_v015."tracker_boot-flag",
    vtrackers_h_v015."tracker_boot-info",
    vtrackers_h_v015."gsm_gsm1_location-area-code",
    vtrackers_h_v015."gsm_gsm1_cell-index",
    vtrackers_h_v015.gsm_gsm1_mcc,
    vtrackers_h_v015.gsm_gsm1_mnc,
    vtrackers_h_v015."gsm_gsm1_rx-level",
    vtrackers_h_v015."gsm_gsm1_ussd-request",
    vtrackers_h_v015."gsm_gsm1_ussd-response",
    vtrackers_h_v015.locations_location1_tstamp,
    vtrackers_h_v015.locations_location1_latitude,
    vtrackers_h_v015.locations_location1_longitude,
    vtrackers_h_v015.locations_location1_altitude,
    vtrackers_h_v015.locations_location1_speed,
    vtrackers_h_v015.locations_location1_azimuth,
    vtrackers_h_v015.locations_location1_satellites,
    vtrackers_h_v015."locations_location1_data-valid",
    vtrackers_h_v015.locations_location1_worked,
    vtrackers_h_v015.locations_location1_quality,
    vtrackers_h_v015.sensors_temperature_tracker,
    vtrackers_h_v015.sensors_voltage_external_ext1,
    vtrackers_h_v015.sensors_voltage_internal_int1,
    vtrackers_h_v015.sensors_dut_dut1,
    vtrackers_h_v015.sensors_dut_dut2
   FROM data.vtrackers_h_v015;


ALTER TABLE public.vtrackers_in_h OWNER TO postgres;

SET search_path = cfg, pg_catalog;

--
-- TOC entry 4594 (class 2604 OID 1867289)
-- Name: iconid; Type: DEFAULT; Schema: cfg; Owner: gpsmon
--

ALTER TABLE ONLY icon ALTER COLUMN iconid SET DEFAULT nextval('icon_iconid_seq'::regclass);


--
-- TOC entry 4593 (class 2604 OID 1867196)
-- Name: groupid; Type: DEFAULT; Schema: cfg; Owner: gpsmon
--

ALTER TABLE ONLY icon_group ALTER COLUMN groupid SET DEFAULT nextval('icon_group_groupid_seq'::regclass);


SET search_path = data, pg_catalog;

--
-- TOC entry 4592 (class 2604 OID 1705657)
-- Name: locationid; Type: DEFAULT; Schema: data; Owner: postgres
--

ALTER TABLE ONLY location ALTER COLUMN locationid SET DEFAULT nextval('location_locationid_seq'::regclass);


--
-- TOC entry 4583 (class 2604 OID 354687)
-- Name: id; Type: DEFAULT; Schema: data; Owner: gpsmon
--

ALTER TABLE ONLY trackers ALTER COLUMN id SET DEFAULT nextval('trackers_id_seq'::regclass);


--
-- TOC entry 4587 (class 2604 OID 972462)
-- Name: ussdid; Type: DEFAULT; Schema: data; Owner: gpsmon
--

ALTER TABLE ONLY ussd ALTER COLUMN ussdid SET DEFAULT nextval('ussd_ussdid_seq'::regclass);


SET search_path = public, pg_catalog;

--
-- TOC entry 4572 (class 2604 OID 18105)
-- Name: carid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY cars ALTER COLUMN carid SET DEFAULT nextval('cars_carsid_seq'::regclass);


--
-- TOC entry 4573 (class 2604 OID 18106)
-- Name: clientid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY clients ALTER COLUMN clientid SET DEFAULT nextval('clients_clientid_seq'::regclass);


--
-- TOC entry 4574 (class 2604 OID 18107)
-- Name: id; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY geozones ALTER COLUMN id SET DEFAULT nextval('geozone_id_seq'::regclass);


--
-- TOC entry 4576 (class 2604 OID 18108)
-- Name: logid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY logs ALTER COLUMN logid SET DEFAULT nextval('logs_logid_seq'::regclass);


--
-- TOC entry 4577 (class 2604 OID 18109)
-- Name: trackerid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY trackers ALTER COLUMN trackerid SET DEFAULT nextval('trackers_trackerid_seq'::regclass);


--
-- TOC entry 4581 (class 2604 OID 18110)
-- Name: userid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY users ALTER COLUMN userid SET DEFAULT nextval('users_userid_seq'::regclass);


SET search_path = cfg, pg_catalog;

--
-- TOC entry 4629 (class 2606 OID 1867201)
-- Name: groupid_pk; Type: CONSTRAINT; Schema: cfg; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY icon_group
    ADD CONSTRAINT groupid_pk PRIMARY KEY (groupid);


--
-- TOC entry 4631 (class 2606 OID 1867294)
-- Name: iconid_pk; Type: CONSTRAINT; Schema: cfg; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY icon
    ADD CONSTRAINT iconid_pk PRIMARY KEY (iconid);


SET search_path = data, pg_catalog;

SET default_tablespace = gpsdata;

--
-- TOC entry 4619 (class 2606 OID 18153)
-- Name: gps_unique_carid_tstmap; Type: CONSTRAINT; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

ALTER TABLE ONLY gps
    ADD CONSTRAINT gps_unique_carid_tstmap UNIQUE (carid, tstamp);


SET default_tablespace = '';

--
-- TOC entry 4627 (class 2606 OID 1705662)
-- Name: location_locationid_key; Type: CONSTRAINT; Schema: data; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY location
    ADD CONSTRAINT location_locationid_key UNIQUE (locationid);


--
-- TOC entry 4599 (class 2606 OID 1705664)
-- Name: online_carid_key; Type: CONSTRAINT; Schema: data; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY online
    ADD CONSTRAINT online_carid_key UNIQUE (carid);


--
-- TOC entry 4623 (class 2606 OID 354693)
-- Name: trackers_id_key; Type: CONSTRAINT; Schema: data; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY trackers
    ADD CONSTRAINT trackers_id_key UNIQUE (id);


--
-- TOC entry 4625 (class 2606 OID 972467)
-- Name: ussd_ussdid_key; Type: CONSTRAINT; Schema: data; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY ussd
    ADD CONSTRAINT ussd_ussdid_key UNIQUE (ussdid);


SET search_path = olap, pg_catalog;

--
-- TOC entry 4601 (class 2606 OID 1705811)
-- Name: gps_average_day_carid_day; Type: CONSTRAINT; Schema: olap; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY gps_average_day
    ADD CONSTRAINT gps_average_day_carid_day UNIQUE (carid, day);


SET search_path = public, pg_catalog;

--
-- TOC entry 4603 (class 2606 OID 18116)
-- Name: cars_carsid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY cars
    ADD CONSTRAINT cars_carsid_key UNIQUE (carid);


--
-- TOC entry 4605 (class 2606 OID 18118)
-- Name: clients_clientid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_clientid_key UNIQUE (clientid);


--
-- TOC entry 4607 (class 2606 OID 18120)
-- Name: clients_company_name_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_company_name_key UNIQUE (company_name);


--
-- TOC entry 4609 (class 2606 OID 18122)
-- Name: phonecard_number_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY phonecard
    ADD CONSTRAINT phonecard_number_key UNIQUE (number);


--
-- TOC entry 4611 (class 2606 OID 18124)
-- Name: trackers_imei_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY trackers
    ADD CONSTRAINT trackers_imei_key UNIQUE (imei);


--
-- TOC entry 4613 (class 2606 OID 18126)
-- Name: trackers_trackerid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY trackers
    ADD CONSTRAINT trackers_trackerid_key UNIQUE (trackerid);


--
-- TOC entry 4615 (class 2606 OID 18128)
-- Name: users_userid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_userid_key UNIQUE (userid);


SET search_path = data, pg_catalog;

--
-- TOC entry 4620 (class 1259 OID 1705666)
-- Name: data_gps_arh_index_carid_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX data_gps_arh_index_carid_tstamp ON gps_arh USING btree (carid, tstamp);


--
-- TOC entry 4621 (class 1259 OID 1705694)
-- Name: data_gps_arh_index_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX data_gps_arh_index_tstamp ON gps_arh USING btree (tstamp);


SET default_tablespace = gpsdata;

--
-- TOC entry 4616 (class 1259 OID 18154)
-- Name: data_gps_index_carid_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

CREATE INDEX data_gps_index_carid_tstamp ON gps USING btree (carid, tstamp);


--
-- TOC entry 4617 (class 1259 OID 18155)
-- Name: data_gps_index_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

CREATE INDEX data_gps_index_tstamp ON gps USING btree (tstamp);


SET default_tablespace = '';

--
-- TOC entry 4595 (class 1259 OID 18129)
-- Name: gps_from_trackers_imei; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX gps_from_trackers_imei ON gps_from_trackers USING btree (imei);


--
-- TOC entry 4596 (class 1259 OID 18130)
-- Name: gps_from_trackers_index_imei_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX gps_from_trackers_index_imei_tstamp ON gps_from_trackers USING btree (imei, tstamp);


--
-- TOC entry 4597 (class 1259 OID 18131)
-- Name: gps_from_trackers_index_lat_lon; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX gps_from_trackers_index_lat_lon ON gps_from_trackers USING btree (lat, lon);


SET search_path = cfg, pg_catalog;

--
-- TOC entry 4632 (class 2606 OID 1867295)
-- Name: groupid_fk; Type: FK CONSTRAINT; Schema: cfg; Owner: gpsmon
--

ALTER TABLE ONLY icon
    ADD CONSTRAINT groupid_fk FOREIGN KEY (groupid) REFERENCES icon_group(groupid);


--
-- TOC entry 4771 (class 0 OID 0)
-- Dependencies: 8
-- Name: public; Type: ACL; Schema: -; Owner: gpsmon
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM gpsmon;
GRANT ALL ON SCHEMA public TO gpsmon;
GRANT ALL ON SCHEMA public TO PUBLIC;


SET search_path = public, pg_catalog;

--
-- TOC entry 4804 (class 0 OID 0)
-- Dependencies: 644
-- Name: gpsdatafull; Type: ACL; Schema: public; Owner: gpsmon
--

REVOKE ALL ON TABLE gpsdatafull FROM PUBLIC;
REVOKE ALL ON TABLE gpsdatafull FROM gpsmon;
GRANT ALL ON TABLE gpsdatafull TO gpsmon;
GRANT ALL ON TABLE gpsdatafull TO postgres;
GRANT ALL ON TABLE gpsdatafull TO tgps;


-- Completed on 2014-06-27 15:21:13 EEST

--
-- PostgreSQL database dump complete
--

