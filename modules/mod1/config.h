
extern void parsdata_355_psql(){
    PGconn		*conn;
    PGresult		*res,*json_res;
		conn=PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);
		if(PQstatus(conn)==CONNECTION_BAD){
			syslog(LOG_DEBUG,"Ошибка подключения к DB");
			exit(0);
			}
}
