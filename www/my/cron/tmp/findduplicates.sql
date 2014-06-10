select distinct * into temporary tmp1 from data.gps_from_trackers ;
select imei,tstamp,count(*) into temporary tmp2 from tmp1 group by imei,tstamp;
select * from tmp2 where count>1;

