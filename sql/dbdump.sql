--
-- PostgreSQL database dump
--

-- Dumped from database version 9.3.4
-- Dumped by pg_dump version 9.3.2
-- Started on 2014-05-26 11:16:02 EEST

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
-- TOC entry 4622 (class 0 OID 0)
-- Dependencies: 33
-- Name: SCHEMA accounts; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA accounts IS 'объекты мониторинга и их настройки ';


--
-- TOC entry 6 (class 2615 OID 17975)
-- Name: data; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA data;


ALTER SCHEMA data OWNER TO gpsmon;

--
-- TOC entry 4623 (class 0 OID 0)
-- Dependencies: 6
-- Name: SCHEMA data; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA data IS 'данные с объектов мониторинга';


--
-- TOC entry 35 (class 2615 OID 394128)
-- Name: map; Type: SCHEMA; Schema: -; Owner: gpsmon
--

CREATE SCHEMA map;


ALTER SCHEMA map OWNER TO gpsmon;

--
-- TOC entry 4624 (class 0 OID 0)
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
-- TOC entry 4625 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA olap; Type: COMMENT; Schema: -; Owner: gpsmon
--

COMMENT ON SCHEMA olap IS 'посчитанные периоды';


SET search_path = data, pg_catalog;

--
-- TOC entry 675 (class 1255 OID 332728)
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
-- TOC entry 676 (class 1255 OID 339361)
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
-- TOC entry 729 (class 1255 OID 305941)
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

update data.online as t1  set azimut=t2.azimut, tstamp=(case when t2.tstamp>t1.tstamp then t2.tstamp else t1.tstamp end), lat=t2.lat,lon=t2.lon,speed=t2.speed,trackerid=t2.trackerid,gpsdop=t2.gpsdop,gsmsignal=t2.gsmsignal
--      dest_day=(case when extract (day from t2.tstamp) != extract (day from CURRENT_TIMESTAMP) then t1.dest_day else t1.dest_day+t2.dest end)
--      dest_day=t1.dest_day+t2.dest
        from tmp2 as t2
        where t1.carid=t2.carid and t2.tstamp = (select tmp3.tstamp from tmp3 where tmp3.carid=t1.carid);

update data.online as t1 set speed_max=t2.speed_max,dest_day=t1.dest_day+t2.dest
        from tmp3 as t2
        where t1.carid=t2.carid;
-- добавляем новые данные в постоянную таблицу хранения
insert into data.gps (carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt) (select carid,trackerid,lat,lon,speed,tstamp,dest,dut,azimut,gsmsignal,gpsdop,signal_restart,volt from tmp1);

-- отчищаем промежуточную таблицу от данных которые были записаны
delete from data.gps_from_trackers as gft using tmp1 where gft.imei=tmp1.imei and gft.tstamp=tmp1.tstamp;
END
$$;


ALTER FUNCTION data.gps_from_trackers() OWNER TO postgres;

SET search_path = public, pg_catalog;

--
-- TOC entry 671 (class 1255 OID 17978)
-- Name: first_agg(anyelement, anyelement); Type: FUNCTION; Schema: public; Owner: gpsmon
--

CREATE FUNCTION first_agg(anyelement, anyelement) RETURNS anyelement
    LANGUAGE sql IMMUTABLE STRICT
    AS $_$
        SELECT $1;
$_$;


ALTER FUNCTION public.first_agg(anyelement, anyelement) OWNER TO gpsmon;

--
-- TOC entry 673 (class 1255 OID 327867)
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
-- TOC entry 672 (class 1255 OID 17979)
-- Name: last_agg(anyelement, anyelement); Type: FUNCTION; Schema: public; Owner: gpsmon
--

CREATE FUNCTION last_agg(anyelement, anyelement) RETURNS anyelement
    LANGUAGE sql IMMUTABLE STRICT
    AS $_$
        SELECT $2;
$_$;


ALTER FUNCTION public.last_agg(anyelement, anyelement) OWNER TO gpsmon;

--
-- TOC entry 2192 (class 1255 OID 17980)
-- Name: first(anyelement); Type: AGGREGATE; Schema: public; Owner: gpsmon
--

CREATE AGGREGATE first(anyelement) (
    SFUNC = first_agg,
    STYPE = anyelement
);


ALTER AGGREGATE public.first(anyelement) OWNER TO gpsmon;

--
-- TOC entry 2193 (class 1255 OID 17981)
-- Name: last(anyelement); Type: AGGREGATE; Schema: public; Owner: gpsmon
--

CREATE AGGREGATE last(anyelement) (
    SFUNC = last_agg,
    STYPE = anyelement
);


ALTER AGGREGATE public.last(anyelement) OWNER TO gpsmon;

SET search_path = data, pg_catalog;

SET default_tablespace = gpsdata;

SET default_with_oids = false;

--
-- TOC entry 635 (class 1259 OID 18092)
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
-- TOC entry 637 (class 1259 OID 53453)
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
-- TOC entry 615 (class 1259 OID 17982)
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
-- TOC entry 616 (class 1259 OID 17988)
-- Name: imei_not_db; Type: TABLE; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE TABLE imei_not_db (
    imei character varying,
    lastsend timestamp with time zone
);


ALTER TABLE data.imei_not_db OWNER TO gpsmon;

--
-- TOC entry 617 (class 1259 OID 17994)
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
    gpsdop integer
);


ALTER TABLE data.online OWNER TO gpsmon;

SET default_tablespace = gpsdata;

--
-- TOC entry 639 (class 1259 OID 354684)
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
-- TOC entry 4628 (class 0 OID 0)
-- Dependencies: 639
-- Name: COLUMN trackers.id; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN trackers.id IS 'порядковый номер';


--
-- TOC entry 4629 (class 0 OID 0)
-- Dependencies: 639
-- Name: COLUMN trackers.tstampdb; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN trackers.tstampdb IS 'время записи данных в базу';


--
-- TOC entry 4630 (class 0 OID 0)
-- Dependencies: 639
-- Name: COLUMN trackers.json_data; Type: COMMENT; Schema: data; Owner: gpsmon
--

COMMENT ON COLUMN trackers.json_data IS 'данные ';


--
-- TOC entry 638 (class 1259 OID 354682)
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
-- TOC entry 4631 (class 0 OID 0)
-- Dependencies: 638
-- Name: trackers_id_seq; Type: SEQUENCE OWNED BY; Schema: data; Owner: gpsmon
--

ALTER SEQUENCE trackers_id_seq OWNED BY trackers.id;


SET search_path = olap, pg_catalog;

SET default_tablespace = '';

--
-- TOC entry 618 (class 1259 OID 18004)
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
-- TOC entry 619 (class 1259 OID 18007)
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
-- TOC entry 620 (class 1259 OID 18014)
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
-- TOC entry 4632 (class 0 OID 0)
-- Dependencies: 620
-- Name: cars_carsid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE cars_carsid_seq OWNED BY cars.carid;


--
-- TOC entry 621 (class 1259 OID 18016)
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
-- TOC entry 622 (class 1259 OID 18022)
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
-- TOC entry 4633 (class 0 OID 0)
-- Dependencies: 622
-- Name: clients_clientid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE clients_clientid_seq OWNED BY clients.clientid;


--
-- TOC entry 623 (class 1259 OID 18024)
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
-- TOC entry 624 (class 1259 OID 18030)
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
-- TOC entry 4634 (class 0 OID 0)
-- Dependencies: 624
-- Name: geozone_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE geozone_id_seq OWNED BY geozones.id;


--
-- TOC entry 636 (class 1259 OID 18101)
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
-- TOC entry 4635 (class 0 OID 0)
-- Dependencies: 636
-- Name: VIEW gpsdatafull; Type: COMMENT; Schema: public; Owner: gpsmon
--

COMMENT ON VIEW gpsdatafull IS 'обработанные данные по объектам, для выборок, отчётов, построения треков и тд.';


--
-- TOC entry 625 (class 1259 OID 18032)
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
-- TOC entry 626 (class 1259 OID 18039)
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
-- TOC entry 4637 (class 0 OID 0)
-- Dependencies: 626
-- Name: logs_logid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE logs_logid_seq OWNED BY logs.logid;


--
-- TOC entry 627 (class 1259 OID 18041)
-- Name: phonecard; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE phonecard (
    number character varying NOT NULL,
    trackerid integer NOT NULL
);


ALTER TABLE public.phonecard OWNER TO gpsmon;

--
-- TOC entry 655 (class 1259 OID 590998)
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
-- TOC entry 628 (class 1259 OID 18047)
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
-- TOC entry 629 (class 1259 OID 18053)
-- Name: settings; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE settings (
    name character varying(64),
    value character varying(255),
    info character varying
);


ALTER TABLE public.settings OWNER TO gpsmon;

--
-- TOC entry 630 (class 1259 OID 18062)
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
-- TOC entry 631 (class 1259 OID 18068)
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
-- TOC entry 4638 (class 0 OID 0)
-- Dependencies: 631
-- Name: trackers_trackerid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE trackers_trackerid_seq OWNED BY trackers.trackerid;


--
-- TOC entry 632 (class 1259 OID 18070)
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
-- TOC entry 633 (class 1259 OID 18079)
-- Name: users_cars; Type: TABLE; Schema: public; Owner: gpsmon; Tablespace: 
--

CREATE TABLE users_cars (
    userid integer,
    carid integer
);


ALTER TABLE public.users_cars OWNER TO gpsmon;

--
-- TOC entry 634 (class 1259 OID 18082)
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
-- TOC entry 4639 (class 0 OID 0)
-- Dependencies: 634
-- Name: users_userid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpsmon
--

ALTER SEQUENCE users_userid_seq OWNED BY users.userid;


--
-- TOC entry 640 (class 1259 OID 359006)
-- Name: vtrackers_in; Type: VIEW; Schema: public; Owner: gpsmon
--

CREATE VIEW vtrackers_in AS
 SELECT trackers.json_data
   FROM data.trackers;


ALTER TABLE public.vtrackers_in OWNER TO gpsmon;

--
-- TOC entry 4640 (class 0 OID 0)
-- Dependencies: 640
-- Name: VIEW vtrackers_in; Type: COMMENT; Schema: public; Owner: gpsmon
--

COMMENT ON VIEW vtrackers_in IS 'данные от трекеров';


--
-- TOC entry 641 (class 1259 OID 359056)
-- Name: vtrackers_in_h; Type: VIEW; Schema: public; Owner: gpsmon
--

CREATE VIEW vtrackers_in_h AS
 SELECT trackers.id,
    trackers.tstampdb,
    trackers.json_data
   FROM data.trackers;


ALTER TABLE public.vtrackers_in_h OWNER TO gpsmon;

--
-- TOC entry 4641 (class 0 OID 0)
-- Dependencies: 641
-- Name: VIEW vtrackers_in_h; Type: COMMENT; Schema: public; Owner: gpsmon
--

COMMENT ON VIEW vtrackers_in_h IS 'данные от трекеров, в читабельном виде';


SET search_path = data, pg_catalog;

--
-- TOC entry 4475 (class 2604 OID 354687)
-- Name: id; Type: DEFAULT; Schema: data; Owner: gpsmon
--

ALTER TABLE ONLY trackers ALTER COLUMN id SET DEFAULT nextval('trackers_id_seq'::regclass);


SET search_path = public, pg_catalog;

--
-- TOC entry 4464 (class 2604 OID 18105)
-- Name: carid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY cars ALTER COLUMN carid SET DEFAULT nextval('cars_carsid_seq'::regclass);


--
-- TOC entry 4465 (class 2604 OID 18106)
-- Name: clientid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY clients ALTER COLUMN clientid SET DEFAULT nextval('clients_clientid_seq'::regclass);


--
-- TOC entry 4466 (class 2604 OID 18107)
-- Name: id; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY geozones ALTER COLUMN id SET DEFAULT nextval('geozone_id_seq'::regclass);


--
-- TOC entry 4468 (class 2604 OID 18108)
-- Name: logid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY logs ALTER COLUMN logid SET DEFAULT nextval('logs_logid_seq'::regclass);


--
-- TOC entry 4469 (class 2604 OID 18109)
-- Name: trackerid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY trackers ALTER COLUMN trackerid SET DEFAULT nextval('trackers_trackerid_seq'::regclass);


--
-- TOC entry 4473 (class 2604 OID 18110)
-- Name: userid; Type: DEFAULT; Schema: public; Owner: gpsmon
--

ALTER TABLE ONLY users ALTER COLUMN userid SET DEFAULT nextval('users_userid_seq'::regclass);


SET search_path = data, pg_catalog;

SET default_tablespace = gpsdata;

--
-- TOC entry 4498 (class 2606 OID 18153)
-- Name: gps_unique_carid_tstmap; Type: CONSTRAINT; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

ALTER TABLE ONLY gps
    ADD CONSTRAINT gps_unique_carid_tstmap UNIQUE (carid, tstamp);


SET default_tablespace = '';

--
-- TOC entry 4500 (class 2606 OID 354693)
-- Name: trackers_id_key; Type: CONSTRAINT; Schema: data; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY trackers
    ADD CONSTRAINT trackers_id_key UNIQUE (id);


SET search_path = public, pg_catalog;

--
-- TOC entry 4482 (class 2606 OID 18116)
-- Name: cars_carsid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY cars
    ADD CONSTRAINT cars_carsid_key UNIQUE (carid);


--
-- TOC entry 4484 (class 2606 OID 18118)
-- Name: clients_clientid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_clientid_key UNIQUE (clientid);


--
-- TOC entry 4486 (class 2606 OID 18120)
-- Name: clients_company_name_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_company_name_key UNIQUE (company_name);


--
-- TOC entry 4488 (class 2606 OID 18122)
-- Name: phonecard_number_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY phonecard
    ADD CONSTRAINT phonecard_number_key UNIQUE (number);


--
-- TOC entry 4490 (class 2606 OID 18124)
-- Name: trackers_imei_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY trackers
    ADD CONSTRAINT trackers_imei_key UNIQUE (imei);


--
-- TOC entry 4492 (class 2606 OID 18126)
-- Name: trackers_trackerid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY trackers
    ADD CONSTRAINT trackers_trackerid_key UNIQUE (trackerid);


--
-- TOC entry 4494 (class 2606 OID 18128)
-- Name: users_userid_key; Type: CONSTRAINT; Schema: public; Owner: gpsmon; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_userid_key UNIQUE (userid);


SET search_path = data, pg_catalog;

SET default_tablespace = gpsdata;

--
-- TOC entry 4495 (class 1259 OID 18154)
-- Name: data_gps_index_carid_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

CREATE INDEX data_gps_index_carid_tstamp ON gps USING btree (carid, tstamp);


--
-- TOC entry 4496 (class 1259 OID 18155)
-- Name: data_gps_index_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: gpsdata
--

CREATE INDEX data_gps_index_tstamp ON gps USING btree (tstamp);


SET default_tablespace = '';

--
-- TOC entry 4478 (class 1259 OID 18129)
-- Name: gps_from_trackers_imei; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX gps_from_trackers_imei ON gps_from_trackers USING btree (imei);


--
-- TOC entry 4479 (class 1259 OID 18130)
-- Name: gps_from_trackers_index_imei_tstamp; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX gps_from_trackers_index_imei_tstamp ON gps_from_trackers USING btree (imei, tstamp);


--
-- TOC entry 4480 (class 1259 OID 18131)
-- Name: gps_from_trackers_index_lat_lon; Type: INDEX; Schema: data; Owner: gpsmon; Tablespace: 
--

CREATE INDEX gps_from_trackers_index_lat_lon ON gps_from_trackers USING btree (lat, lon);


--
-- TOC entry 4627 (class 0 OID 0)
-- Dependencies: 8
-- Name: public; Type: ACL; Schema: -; Owner: gpsmon
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM gpsmon;
GRANT ALL ON SCHEMA public TO gpsmon;
GRANT ALL ON SCHEMA public TO PUBLIC;


SET search_path = public, pg_catalog;

--
-- TOC entry 4636 (class 0 OID 0)
-- Dependencies: 636
-- Name: gpsdatafull; Type: ACL; Schema: public; Owner: gpsmon
--

REVOKE ALL ON TABLE gpsdatafull FROM PUBLIC;
REVOKE ALL ON TABLE gpsdatafull FROM gpsmon;
GRANT ALL ON TABLE gpsdatafull TO gpsmon;
GRANT ALL ON TABLE gpsdatafull TO postgres;
GRANT ALL ON TABLE gpsdatafull TO tgps;


-- Completed on 2014-05-26 11:16:08 EEST

--
-- PostgreSQL database dump complete
--

