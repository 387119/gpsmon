
int getCheckTime(char * imei){
	PGconn		*conn;
	PGresult	*res;
	char		query[1024];
	int		row,ctime=0;
	memset(&query,'\0',1024);	//Чистим строку запроса
	sprintf(query,"select checkpoint from sensors.vcheckpoint where imei='%s'",imei); 	//Формируем строку запроса к БД
	conn = PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);  		//Соеденяемся с БД
	if(PQstatus(conn) == CONNECTION_BAD){		
		return -1;	//Ошибка подключения к БД
	}
	res = PQexec(conn,query);		//Выполняем запрос к БД
	if(PQresultStatus(res) != PGRES_TUPLES_OK){
		return -2;	// Ошибка выполнения запроса
	}
	
	row = PQntuples(res);
	if(row<1){
		return -3;  //Нет данного imei в таблицы чекпоинтов
	}
	else if(row>=2){
		return -4;  //Больше одного совпадения по заданному Imei
	}
	else{ 
		sscanf(PQgetvalue(res, 0 , 0),"%d",&ctime);//Конвентируемся строковое представление времени в целочисленное и записываем в переменную ctime
		return ctime;
	}

	
}
