

//Функция записи данных------------------------
void WriteData(FILE* stream, char* format,...){
	if(stream==NULL){
		openlog("ERROR",LOG_PID,LOG_LOCAL1);
		syslog(LOG_ERR,"Ошибка записи данных по трекеру %s '%s'",gg.trackers[gg.i].imei,gg.d);
		closelog();
		exit(1);
		}
	else{
		va_list data;
		va_start(data,format);
		vfprintf(stream,format,data);
		va_end(data);
	}
}
//---------------------------------------------

//Создаем сокет-------------------------------------------------------------------------
createSocket(short *port)
{
	
	gg.sock=socket(AF_INET,SOCK_DGRAM,0);
	if(gg.sock<0){
		openlog("ERROR",LOG_PID,LOG_LOCAL1);
		syslog(LOG_ERR,"Ошибка при создание сокета");
		closelog();
		exit(EXIT_FAILURE);
		}
	//else printf("Socket is created\n");
	gg.servaddr.sin_family=AF_INET;
	gg.servaddr.sin_port=htons(*port);
	gg.servaddr.sin_addr.s_addr=htonl(INADDR_ANY);

	gg.bind_sock=bind(gg.sock, (struct sockaddr *)&gg.servaddr, sizeof(gg.servaddr));
	if(gg.bind_sock<0){
		openlog("ERROR",LOG_PID,LOG_LOCAL1);
		syslog(LOG_ERR,"Ошибка при привязки сокета");
		closelog();
		exit(EXIT_FAILURE);
		}
return 0;
}
//-------------------------------------------------------------------------------------
void init_344(void){
    if(gg.debug_level>2&&gg.debug_level>5){
	int ip_ip;
	ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
	ipp=&ip_ip;
	ip4=*ipp;
	openlog("init_344",LOG_PID,LOG_LOCAL0);
	syslog(LOG_DEBUG,"Запрос init от %d.%d.%d.%d:%d",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	closelog();
	}

    memset(&gg.txbuf,'\0',2048);										//Обнуляем буфер
    gg.tstamp=time(NULL);											//Генерируем время
    gg.time_init=gg.tstamp;
    gg.txbuf[0]=0x43;												//Формируем буфер для отправки трекеру
    gg.txbuf[1]=4;
    memcpy(&gg.txbuf[2],&gg.time_init,4);
    gg.txbuf[6]=0x41;
    gg.txbuf[7]=4;
    for(gg.i=0;gg.i<4;gg.i++)gg.txbuf[gg.i+8]=gg.rxbuf[gg.i+2];
    sendto(gg.sock,gg.txbuf,12,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));			//Отправляем трекеру подтверждение

}


void session_344(void){
	char imei[15],iccid[19];
	int version_sw,version_hw;
    if(gg.debug_level>2&&gg.debug_level<5){
	int ip_ip;
	ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
	ipp=&ip_ip;
	ip4=*ipp;
	openlog("Session_344",LOG_PID,LOG_LOCAL0);
	syslog(LOG_DEBUG,"Запрос авторизации от %d.%d.%d.%d:%d",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	closelog();
	}
    gg.f=0;
    gg.tstamp=time(NULL);
    int pos=0;
    //unsigned char type;
    while(pos<gg.byte_read){
	switch(gg.rxbuf[pos]){
		case 0x31:
			//memcpy(&imei,&gg.rxbuf[pos+2],gg.rxbuf[pos+1]);
			//syslog(LOG_DEBUG,"TEG = %p size = %d, DATA = %s",gg.rxbuf[pos],gg.rxbuf[pos+1],imei);
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x32:
			//memcpy(&version_sw,&gg.rxbuf[pos+2],gg.rxbuf[pos+1]);
			//syslog(LOG_DEBUG,"TEG = %p size = %d, DATA = %d",gg.rxbuf[pos],gg.rxbuf[pos+1],version_sw);
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x33:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x34:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x39:
			//memcpy(&iccid,&gg.rxbuf[pos+2],gg.rxbuf[pos+1]);
			//syslog(LOG_DEBUG,"TEG = %p size = %d, DATA: %s",gg.rxbuf[pos],gg.rxbuf[pos+1],iccid);
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x35:
			//memcpy(&gg.trackers[gg.i].versionhw,&gg.rxbuf[pos+2],gg.rxbuf[pos+1]);
			memcpy(&version_hw,&gg.rxbuf[pos+2],gg.rxbuf[pos+1]);
			//syslog(LOG_DEBUG,"TEG = %p size = %d, DATA = %d",gg.rxbuf[pos],gg.rxbuf[pos+1],version_hw);
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x38:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
	}
    }
    for(gg.i=0;gg.i<15;gg.i++)gg.imei[gg.i]=gg.rxbuf[gg.i+2];							//Читаем Imei трекера
    for(gg.i=0;gg.i<10000;gg.i++){
	if(!strcmp(gg.trackers[gg.i].imei,gg.imei)){								//Проверяем Imei в сруктуре трекеров
		gg.trackers[gg.i].ip=htonl(gg.clientaddr.sin_addr.s_addr);					//Обновляем Ip по трекеру
		gg.trackers[gg.i].port=htons(gg.clientaddr.sin_port);						//Обновляем порт по трекеру
		memcpy(&gg.trackers[gg.i].versionsw,&gg.rxbuf[19],4);
		gg.trackers[gg.i].versionhw=version_hw;
		gg.trackers[gg.i].time=gg.tstamp;
		ipp=&gg.trackers[gg.i].ip;
		ip4=*ipp;
		//printf("№%d Данные по трекеру %s успешно обновлены %d.%d.%d.%d:%d\n",gg.i,gg.trackers[gg.i].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		if(gg.debug_level>0&&gg.debug_level<5){
			openlog("INFO",LOG_PID,LOG_LOCAL1);
			syslog(LOG_INFO,"№%d Данные по трекеру %s обновлены, %d.%d.%d.%d:%d",gg.i,gg.trackers[gg.i].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
			closelog();
			}
		gg.f=1;
		sendto(gg.sock,gg.CRC_OK,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));		//Отправляем ответ трекеру
		break;
		}
	}
    if(gg.f==0){												//Если трекер не найден то добовляем новую запись
	
	gg.trackers[gg.counter].ip=htonl(gg.clientaddr.sin_addr.s_addr);
	gg.trackers[gg.counter].port=htons(gg.clientaddr.sin_port);
	gg.trackers[gg.counter].time=gg.tstamp;
	for(gg.i=0;gg.i<15;gg.i++)gg.trackers[gg.counter].imei[gg.i]=gg.imei[gg.i];
	memcpy(&gg.trackers[gg.counter].versionsw,&gg.rxbuf[19],4);
	gg.trackers[gg.counter].versionhw=version_hw;
	ipp=&gg.trackers[gg.counter].ip;
	ip4=*ipp;
	//printf("№%d Трекер %s успешно авторизован с %d.%d.%d.%d:%d\n",gg.counter,gg.trackers[gg.counter].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.counter].port);
	if(gg.debug_level>0&&gg.debug_level<5){
		openlog("INFO",LOG_PID,LOG_LOCAL1);
		syslog(LOG_DEBUG,"№%d Трекер %s авторизован с %d.%d.%d.%d:%d ",gg.counter,gg.trackers[gg.counter].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.counter].port);
		closelog();
		}
	gg.counter++;												//Счетчик трекеров ув. на 1
	sendto(gg.sock,gg.CRC_OK,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
	}
}

boot_if(){
    switch(d344.boot_flag){
	case 1:
		return d344.boot_reason;
		break;
	case 0:
		return -1;
		break;
	default:
		break;
	}
}

void parsdata_344(){
    struct timeval msec;
    openlog("Parsdata_344",LOG_PID,LOG_LOCAL0);
    char	data_lock[130],raw_lock[130],copy_lock[130],timed[40],timec[30],zone[6],sec[20];
    int		z=0,x,countdata=0;
    int lat,lng,t;
    unsigned short vv1=0,vv2=0;
    memset(&data_lock,'\0',130);
    memset(&copy_lock,'\0',130);
    memset(&raw_lock,'\0',130);
    struct tm *timein;
    strcpy(data_lock,gg.d);
    strcpy(copy_lock,gg.copyd);
    strcpy(raw_lock,gg.rawd);
    gg.tstamp=time(NULL);
    gettimeofday(&msec,NULL);
    timein=localtime(&gg.tstamp);
    strftime(zone,6,"%z",timein);
    strftime(timed,30,"_%Y-%m-%dT%H:%M:%S",timein);
    strftime(timec,30,"/%Y-%m-%dT%H:%M:%S",timein);
    sprintf(sec,".%d%s",msec.tv_usec,zone);

    for(gg.i=0;gg.i<10000;gg.i++){
    if(gg.trackers[gg.i].ip==htonl(gg.clientaddr.sin_addr.s_addr)&&gg.trackers[gg.i].port==htons(gg.clientaddr.sin_port)){		//Проверяем наличие трекера в базе
		ipp=&gg.trackers[gg.i].ip;
		ip4=*ipp;
		if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Принимаем данные от %s  '№%d' c  %d.%d.%d.%d:%d",gg.trackers[gg.i].imei,gg.packet_counter,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		
		strcat(data_lock,gg.trackers[gg.i].imei);
		strcat(copy_lock,gg.trackers[gg.i].imei);
		strcat(raw_lock,gg.trackers[gg.i].imei);
		if(cr.ct==1)mkdir(copy_lock);
		if(cr.rt==1)mkdir(raw_lock);
		strcat(data_lock,timed);
		strcat(copy_lock,timec);
		strcat(raw_lock,timec);
		strcat(data_lock,sec);
		strcat(copy_lock,sec);
		strcat(raw_lock,sec);
		gg.file_data=fopen(data_lock,"w");
		WriteData(gg.file_data,"%s",gg.trackers[gg.i].imei);
		WriteCopy(&cr.ct,copy_lock,"%s",gg.trackers[gg.i].imei);							//Запись copy данных
		WriteRaw(&cr.rt,raw_lock,gg.rxbuf,&gg.byte_read);								//Запись RAW данных
		
		for(x=0;x<gg.byte_read;x++){
			if(gg.rxbuf[x]==0x3f&&gg.rxbuf[x+1]==0x1e||gg.rxbuf[x]==0x5f&&gg.rxbuf[x+1]==0x1e)countdata++;
			}
		
		for(x=0;x<countdata;x++){
			z=32*x;
			if(gg.rxbuf[z]==0x3f&&gg.rxbuf[z+1]==0x1e){
				p344=&gg.rxbuf[z];
				d344=*p344;
				mp=&d344.t;
				md=*mp;
				vv1=d344.v1+(d344.v1_hi<<8);
				vv2=d344.v2+(d344.v2_hi<<8);
				//printf("v1=%d v2=%d\n",vv1,vv2);
				WriteData(gg.file_data,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",md.t,md.lat,md.lng,d344.speed+d344.speed_hi/256,d344.dir*2,d344.rx,d344.dop,vv1,vv2,boot_if());
				WriteCopy(&cr.ct,copy_lock,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",md.t,md.lat,md.lng,d344.speed+d344.speed_hi/256,d344.dir*2,d344.rx,d344.dop,vv1,vv2,boot_if());
			}
			else if(gg.rxbuf[z]==0x5f&&gg.rxbuf[z+1]==0x1e){
				pdyt=&gg.rxbuf[z];
				ddyt=*pdyt;
				WriteData(gg.file_data,";%d;%d",ddyt.dyt1,ddyt.dyt2);
				WriteCopy(&cr.ct,copy_lock,";%d;%d",ddyt.dyt1,ddyt.dyt2);
				}
			}
	    if(gg.file_data)fclose(gg.file_data);
	    if(sendto(gg.sock,gg.CRC_OK,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr))){
		if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Отправляем 'CRC_OK' трекеру  %s '№%d' c  %d.%d.%d.%d:%d",gg.trackers[gg.i].imei,gg.packet_counter,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		}
	    break;
	    }
	}
    if(gg.i>9998){
	int ip_ip;
	ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
	ipp=&ip_ip;
	ip4=*ipp;
	if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Отправляем завпрос %d.%d.%d.%d:%d на установление новой сессии",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
	exit(0);
	}

closelog();
}


void parsdata_344_psql(){
    struct timeval	msec;
    struct tm		*timein;
    int countdata=0,x,z;
    char query[4096],str[200],copy_lock[130],raw_lock[130],timec[30],zone[6],c1[100],r1[100];
    char json_query[24000],json_str[1500];
    unsigned short vv1=0,vv2=0;
    PGconn *conn;
    PGresult *res,*json_res;
    openlog("Parsdata_344_psql",LOG_PID,LOG_LOCAL0);
    memset(&query,'\0',4096);
    memset(&json_query,'\0',24000);
    strcpy(query,"INSERT INTO data.gps_from_trackers(imei,tstamp,lat,lon,speed,dut,azimut,gsmsignal,gpsdop,signal_restart,volt,class) values");
    strcpy(json_query,"INSERT INTO vtrackers_in (json_data) values");
    for(gg.i=0;gg.i<10000;gg.i++){
	if(gg.trackers[gg.i].ip==htonl(gg.clientaddr.sin_addr.s_addr)&&gg.trackers[gg.i].port==htons(gg.clientaddr.sin_port)){
		memset(&copy_lock,'\0',130);
		memset(&raw_lock,'\0',130);
		memset(&r1,'\0',100);
		memset(&c1,'\0',100);
		gg.tstamp=time(NULL);
		gettimeofday(&msec,NULL);
		timein=localtime(&gg.tstamp);
		strftime(zone,6,"%z",timein);
		strftime(timec,30,"/%Y-%m-%dT%H:%M:%S",timein);
		sprintf(copy_lock,"%s%s%s.%d%s",gg.copyd,gg.trackers[gg.i].imei,timec,msec.tv_usec,zone);
		sprintf(raw_lock,"%s%s%s.%d%s",gg.rawd,gg.trackers[gg.i].imei,timec,msec.tv_usec,zone);
		sprintf(c1,"%s%s",gg.copyd,gg.trackers[gg.i].imei);
		sprintf(r1,"%s%s",gg.rawd,gg.trackers[gg.i].imei);
		if(cr.ct==1)mkdir(c1);
		if(cr.rt==1)mkdir(r1);
		ipp=&gg.trackers[gg.i].ip;
		ip4=*ipp;
		if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Принимаем данные от %s с %d.%d.%d.%d:%d",gg.trackers[gg.i].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		conn=PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);
		if(PQstatus(conn)==CONNECTION_BAD){
			syslog(LOG_DEBUG,"Ошибка подключения к BD");
			exit(0);
			}
		WriteRaw(&cr.rt,raw_lock,gg.rxbuf,&gg.byte_read);
		WriteCopy(&cr.ct,copy_lock,"%s",gg.trackers[gg.i].imei);
		for(x=0;x<gg.byte_read;x++){
			if(gg.rxbuf[x]==0x3f&&gg.rxbuf[x+1]==0x1e||gg.rxbuf[x]==0x5f&&gg.rxbuf[x+1]==0x1e)countdata++;
			}
			
		if(gg.rxbuf[0]==0x3f&&gg.rxbuf[1]==0x1e&&gg.rxbuf[32]==0x3f&&gg.rxbuf[33]==0x1e||gg.rxbuf[0]==0x3f&&gg.rxbuf[1]==0x1e&&countdata==1){
			for(x=0;x<countdata;x++){
				z=32*x;
				p344=&gg.rxbuf[z];
				d344=*p344;
				mp=&d344.t;
				md=*mp;
				vv1=d344.v1+(d344.v1_hi<<8);
				vv2=d344.v2+(d344.v2_hi<<8);
				memset(&str,'\0',200);
				memset(&json_str,'\0',1500);
				WriteCopy(&cr.ct,copy_lock,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",md.t,md.lat,md.lng,d344.speed+d344.speed_hi,d344.dir*2,d344.rx,d344.dop,vv1,vv2,boot_if());
				sprintf(str," ('%s',to_timestamp(%d),%d,%d,%d,E'{null,null}',%d,%d,%d,%d,E'{%d,%d}',2),",gg.trackers[gg.i].imei,md.t,md.lat,md.lng,d344.speed+d344.speed_hi,d344.dir*2,d344.rx,d344.dop,boot_if(),vv1,vv2);
				strcat(query,str);
				//sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.4\",\"module\":{\"name\":\"mega-gps\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d,\"gsm\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":\"null\",\"dut2\":\"null\"}}}'),",gg.tstamp,gg.trackers[gg.i].imei,md.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if(),d344.lac,d344.ci,d344.mcc,d344.mnc,d344.rx,md.t,md.lat,md.lng,d344.alt+d344.alt_hi,d344.speed+d344.speed_hi,d344.dir*2,d344.dop,d344.temp,vv1,vv2);
				sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.5\",\"module\":{\"id\":\"1\",\"name\":\"mega-gps\",\"info\":\"mega-gps-v355\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d},\"gsm\":{\"gsm1\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":\"\",\"dut2\":\"\"}}}'),",gg.tstamp,gg.trackers[gg.i].imei,md.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if(),d344.lac,d344.ci,d344.mcc,d344.mnc,d344.rx,md.t,md.lat,md.lng,d344.alt+d344.alt_hi,d344.speed+d344.speed_hi,d344.dir*2,d344.dop,d344.temp,vv1,vv2);
				strcat(json_query,json_str);
				}
			for(x=0;x<4096;x++){
				if(query[x]=='\0'){
					query[x-1]='\0';
					break;
					}
				}
				
			for(x=0;x<24000;x++){
				if(json_query[x]=='\0'){
					json_query[x-1]='\0';
					break;
					}
				}
				
			strcat(query,";");
			strcat(json_query,";");
			res=PQexec(conn,query);
			json_res=PQexec(conn,json_query);
			if(PQresultStatus(res)==PGRES_COMMAND_OK&&PQresultStatus(json_res)==PGRES_COMMAND_OK){
				if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Данные по трекеру %s успешно записаны в db",gg.trackers[gg.i].imei);
				sendto(gg.sock,gg.CRC_OK,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				}
			else {
				syslog(LOG_DEBUG,"Ошибка при записи данных в базу по трекеру %s",gg.trackers[gg.i].imei);
				syslog(LOG_DEBUG,"%s",json_query);
				exit(0);
				}
			}
		if(gg.rxbuf[0]==0x3f&&gg.rxbuf[1]==0x1e&&gg.rxbuf[32]==0x5f&&gg.rxbuf[33]==0x1e){
			for(x=0;x<countdata/2;x++){
				z=64*x;
				p344=&gg.rxbuf[z];
				d344=*p344;
				mp=&d344.t;
				md=*mp;
				pdyt=&gg.rxbuf[z+32];
				ddyt=*pdyt;
				vv1=d344.v1+(d344.v1_hi<<8);
				vv2=d344.v2+(d344.v2_hi<<8);
				memset(&str,'\0',200);
				memset(&json_str,'\0',1500);
				WriteCopy(&cr.ct,copy_lock,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",md.t,md.lat,md.lng,d344.speed+d344.speed_hi,d344.dir*2,d344.rx,d344.dop,vv1,vv2,boot_if(),ddyt.dyt1,ddyt.dyt2);
				sprintf(str," ('%s',to_timestamp(%d),%d,%d,%d,E'{%d,%d}',%d,%d,%d,%d,E'{%d,%d}',2),",gg.trackers[gg.i].imei,md.t,md.lat,md.lng,d344.speed+d344.speed_hi,ddyt.dyt1,ddyt.dyt2,d344.dir*2,d344.rx,d344.dop,boot_if(),vv1,vv2);
				strcat(query,str);
				//sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.4\",\"module\":{\"name\":\"mega-gps\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d,\"gsm\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":%d,\"dut2\":%d}}}'),",gg.tstamp,gg.trackers[gg.i].imei,md.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if(),d344.lac,d344.ci,d344.mcc,d344.mnc,d344.rx,md.t,md.lat,md.lng,d344.alt+d344.alt_hi,d344.speed+d344.speed_hi,d344.dir*2,d344.dop,d344.temp,vv1,vv2,ddyt.dyt1,ddyt.dyt2);
				sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.5\",\"module\":{\"id\":\"1\",\"name\":\"mega-gps\",\"info\":\"mega-gps-v355\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d},\"gsm\":{\"gsm1\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":%d,\"dut2\":%d}}}'),",gg.tstamp,gg.trackers[gg.i].imei,md.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if(),d344.lac,d344.ci,d344.mcc,d344.mnc,d344.rx,md.t,md.lat,md.lng,d344.alt+d344.alt_hi,d344.speed+d344.speed_hi,d344.dir*2,d344.dop,d344.temp,vv1,vv2,ddyt.dyt1,ddyt.dyt2);
				strcat(json_query,json_str);
				}
			for(x=0;x<4096;x++){
				if(query[x]=='\0')query[x-1]='\0';
				}
			for(x=0;x<24000;x++){
				if(json_query[x]=='\0'){
					json_query[x-1]='\0';
					break;
					}
				}
			strcat(query,";");
			strcat(json_query,";");
			res=PQexec(conn,query);
			json_res=PQexec(conn,json_query);
			if(PQresultStatus(res)==PGRES_COMMAND_OK&&PQresultStatus(json_res)==PGRES_COMMAND_OK){
				if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Данные по трекеру %s успешно записаны в db",gg.trackers[gg.i].imei);
				sendto(gg.sock,gg.CRC_OK,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				}
			else {
				if(gg.debug_level>1&&gg.debug_level<5)syslog(LOG_DEBUG,"Ошибка при записи данных в базу по трекеру %s",gg.trackers[gg.i].imei);
				syslog(LOG_DEBUG,"%s",json_query);
				exit(0);
				}
			}
		break;
		}
	}
    if(gg.i>9998){
		int ip_ip;
		ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
		ipp=&ip_ip;
		ip4=*ipp;
		if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Отправляем запрос %d.%d.%d.%d:%d на установление новой сесии",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
		sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
		//PQfinish(conn);
		exit(0);
		}
closelog();
}