

extern void init_355(void){
    if(gg.debug_level>2&&gg.debug_level<5){
	int ip_ip;
	ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
	ipp=&ip_ip;
	ip4=*ipp;
	openlog("init_355",LOG_PID,LOG_LOCAL0);
	syslog(LOG_DEBUG,"Запрос init от %d.%d.%d.%d:%d",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	closelog();
	}
    memset(&gg.txbuf,'\0',2048);
    gg.tstamp=time(NULL);
    gg.txbuf[0]=0x03;
    gg.txbuf[1]=2;
    gg.txbuf[2]=gg.rxbuf[2];
    gg.txbuf[3]=gg.rxbuf[3];
    gg.txbuf[4]=0x43;
    gg.txbuf[5]=4;
    memcpy(&gg.txbuf[6],&gg.tstamp,4);
    gg.txbuf[10]=0x41;
    gg.txbuf[11]=4;
    gg.txbuf[12]=gg.rxbuf[6];
    gg.txbuf[13]=gg.rxbuf[7];
    gg.txbuf[14]=gg.rxbuf[8];
    gg.txbuf[15]=gg.rxbuf[9];
    
    sendto(gg.sock,gg.txbuf,16,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
}


extern void session_355(){
    if(gg.debug_level>2&&gg.debug_level<5){
	int ip_ip;
	ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
	ipp=&ip_ip;
	ip4=*ipp;
	openlog("Session_355",LOG_PID,LOG_LOCAL0);
	syslog(LOG_DEBUG,"Запрос авторизации от %d.%d.%d.%d:%d",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	closelog();
	}
    gg.f=0;
    gg.tstamp=time(NULL);
    unsigned int pos=0,version_hw,check_time;
    while(pos<gg.byte_read){
	switch(gg.rxbuf[pos]){
		case 0x02:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x31:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x32:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x33:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x34:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x39:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x35:
			memcpy(&version_hw,&gg.rxbuf[pos+2],gg.rxbuf[pos+1]);
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
		case 0x38:
			pos=pos+gg.rxbuf[pos+1]+2;
			break;
	}
    }

    memset(&gg.txbuf,'\0',2048);
    gg.CRC_OKK[2]=gg.rxbuf[2];
    gg.CRC_OKK[3]=gg.rxbuf[3];
    for (gg.i=0;gg.i<15;gg.i++){gg.imei[gg.i]=gg.rxbuf[6+gg.i];}
    for(gg.i=0;gg.i<10000;gg.i++){
	if(!strcmp(gg.trackers[gg.i].imei,gg.imei)){
		gg.trackers[gg.i].ip=htonl(gg.clientaddr.sin_addr.s_addr);
		gg.trackers[gg.i].port=htons(gg.clientaddr.sin_port);
		memcpy(&gg.trackers[gg.i].versionsw,&gg.rxbuf[23],4);
		gg.trackers[gg.i].versionhw=version_hw;
		gg.trackers[gg.i].time=gg.tstamp;
		mblock->cpoint[gg.i].sessiontime = gg.tstamp;
		ipp=&gg.trackers[gg.i].ip;
		ip4=*ipp;
		//printf("№%d Данные по трекеру %s обновлены, %d.%d.%d.%d:%d\n",gg.i,gg.trackers[gg.i].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		if(gg.debug_level>0&&gg.debug_level<5){
			openlog("INFO",LOG_PID,LOG_LOCAL1);
			syslog(LOG_INFO,"№%d Данные по трекеру %s обновлены, %d.%d.%d.%d:%d ",gg.i,gg.trackers[gg.i].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
			closelog();
			}
		gg.f=1;
		sendto(gg.sock,gg.CRC_OKK,4,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
		break;
		}
	}
    if(gg.f==0){
	gg.trackers[gg.counter].ip=htonl(gg.clientaddr.sin_addr.s_addr);
	gg.trackers[gg.counter].port=htons(gg.clientaddr.sin_port);
	gg.trackers[gg.counter].time=gg.tstamp;
	for(gg.i=0;gg.i<15;gg.i++){
		gg.trackers[gg.counter].imei[gg.i]=gg.imei[gg.i];
		mblock->cpoint[gg.counter].imei[gg.i]=gg.imei[gg.i];
	}
	mblock->cpoint[gg.counter].sessiontime = gg.tstamp;
	check_time=getCheckTime(mblock->cpoint[gg.counter].imei);
	if(check_time>=0)mblock->cpoint[gg.counter].time=check_time;
	else if(check_time==-1)syslog(LOG_DEBUG,"getCheckTime():Ошибка подключения к БД, imei='%s'",mblock->cpoint[gg.counter].imei);
	else if(check_time==-2)syslog(LOG_DEBUG,"getCheckTime():Ошибка выполнения запроса, imei='%s'",mblock->cpoint[gg.counter].imei);
	else if(check_time==-3)syslog(LOG_DEBUG,"getCheckTime():Не найдено совпадений по imei='%s'",mblock->cpoint[gg.counter].imei);
	else if(check_time==-4)syslog(LOG_DEBUG,"getCheckTime():Больше одного совпадения по imei='%s'",mblock->cpoint[gg.counter].imei);

	memcpy(&gg.trackers[gg.counter].versionsw,&gg.rxbuf[23],4);
	gg.trackers[gg.counter].versionhw=version_hw;
	ipp=&gg.trackers[gg.counter].ip;
	ip4=*ipp;
	//printf("№%d Трекер %s успешно авторизован с %d.%d.%d.%d:%d\n",gg.counter,gg.trackers[gg.counter].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.counter].port);
	if(gg.debug_level>0&&gg.debug_level<5){
		openlog("INFO",LOG_PID,LOG_LOCAL1);
		syslog(LOG_INFO,"№%d Трекер %s успешно авторизован с %d.%d.%d.%d:%d",gg.counter,gg.trackers[gg.counter].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.counter].port);
		//syslog(LOG_INFO,"Новая структура imei='%s'",mblock->cpoint[gg.counter].imei);
		closelog();
		}
	gg.counter++;
	sendto(gg.sock,gg.CRC_OKK,4,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
	}
}

boot_if_355(){
    switch(d355.boot_flag){
	case 1:
		return d355.boot_reason;
		break;
	case 0:
		return -1;
		break;
	default :
		break;
	}
}

extern void parsdata_355(void){
    struct timeval	msec;
    struct tm		*timein;
    char data_lock[130],copy_lock[130],raw_lock[130],timed[30],timec[30],zone[6],c1[100],r1[100];
    int x,countdata=0,z;
    gg.f=0;
    gg.CRC_OKK[2]=gg.rxbuf[2];gg.CRC_OKK[3]=gg.rxbuf[3];
    openlog("parsdata_355",LOG_PID,LOG_LOCAL0);
    for(gg.i=0;gg.i<10000;gg.i++){
	if(gg.trackers[gg.i].ip==htonl(gg.clientaddr.sin_addr.s_addr)&&gg.trackers[gg.i].port==htons(gg.clientaddr.sin_port)){
		memset(&data_lock,'\0',130);
		memset(&copy_lock,'\0',130);
		memset(&raw_lock,'\0',130);
		memset(&c1,'\0',100);
		memset(&r1,'\0',100);
		gg.tstamp=time(NULL);
		gettimeofday(&msec,NULL);
		timein=localtime(&gg.tstamp);
		strftime(zone,6,"%z",timein);
		strftime(timed,30,"_%Y-%m-%dT%H:%M:%S",timein);
		strftime(timec,30,"/%Y-%m-%dT%H:%M:%S",timein);
		sprintf(data_lock,"%s%s%s.%d%s",gg.d,gg.trackers[gg.i].imei,timed,msec.tv_usec,zone);					//Генерируем путь сохранения данных
		sprintf(copy_lock,"%s%s%s.%d%s",gg.copyd,gg.trackers[gg.i].imei,timec,msec.tv_usec,zone);				//Генерируем путь сохранения COPY
		sprintf(raw_lock,"%s%s%s.%d%s",gg.rawd,gg.trackers[gg.i].imei,timec,msec.tv_usec,zone);					//Генерируем пути сохранения RAW
		sprintf(c1,"%s%s",gg.copyd,gg.trackers[gg.i].imei);
		sprintf(r1,"%s%s",gg.rawd,gg.trackers[gg.i].imei);
		gg.file_data=fopen(data_lock,"w");
		if(cr.ct==1)mkdir(c1);
		if(cr.rt==1)mkdir(r1);
		ipp=&gg.trackers[gg.i].ip;
		ip4=*ipp;
		if(gg.debug_level>1&&gg.debug_level<5)syslog(LOG_DEBUG,"Принимаем данные от %s '№%d' с %d.%d.%d.%d:%d  ",gg.trackers[gg.i].imei,gg.packet_counter,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		WriteRaw(&cr.rt,raw_lock,gg.rxbuf,&gg.byte_read);
		WriteData(gg.file_data,"%s",gg.trackers[gg.i].imei);
		WriteCopy(&cr.ct,copy_lock,"%s",gg.trackers[gg.i].imei);
		
		for(x=0;x<gg.byte_read;x++)if(gg.rxbuf[2+x]==0x3d&&gg.rxbuf[3+x]==0x1e||gg.rxbuf[2+x]==0x5f&&gg.rxbuf[3+x]==0x1e)countdata++;	//Считаем колличество координат в пакете
		if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Колличесво координат в пакете %d",countdata);
		for(x=0;x<countdata;x++){
			z=x*32;
			if(gg.rxbuf[4+z]==0x3d&&gg.rxbuf[5+z]==0x1e){
				p355=&gg.rxbuf[4+z];
				d355=*p355;
				p3=&d355.tmp[0];
				d3=*p3;
				//printf("%p %d %d %d %d %d %d %d %d\n",d355.type,d355.len,d3.t,d3.lng,d3.lat,d3.speed,d3.dop,d3.dir,d355.mcc);
				WriteData(gg.file_data,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",d3.t,d3.lat,d3.lng,d3.speed,d3.dir*2,d355.rx,d3.dop,d3.v1,d3.v2,boot_if_355());
				WriteCopy(&cr.ct,copy_lock,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",d3.t,d3.lat,d3.lng,d3.speed,d3.dir*2,d355.rx,d3.dop,d3.v1,d3.v2,boot_if_355());
				}
			else if(gg.rxbuf[4+z]==0x5f&&gg.rxbuf[5+z]==0x1e){
				pdyt=&gg.rxbuf[4+z];
				ddyt=*pdyt;
				WriteData(gg.file_data,";%d;%d",ddyt.dyt1,ddyt.dyt2);
				WriteCopy(&cr.ct,copy_lock,";%d;%d",ddyt.dyt1,ddyt.dyt2);
				}
			}
		gg.f=1;
		if(gg.file_data)fclose(gg.file_data);
		if(sendto(gg.sock,gg.CRC_OKK,4,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr))){
			if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Отправляем 'CRC_OK' трекеру  %s '№%d' с %d.%d.%d.%d:%d",gg.trackers[gg.i].imei,gg.packet_counter,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
			}
		break;
		}
	}
    if(gg.f==0){
	int ip_ip;
	ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
	ipp=&ip_ip;
	ip4=*ipp;
	//printf("Отправили заспрос на авторизацию\n");
	if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Отправляем запрос %d.%d.%d.%d:%d на установление новой сесии",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
    }
closelog();
}

extern void parsdata_355_psql(){
    struct timeval	msec;
    struct tm		*timein;
    int			x,z,countdata=0;
    char		query[4096],str[200],copy_lock[130],raw_lock[130],c1[100],r1[100],zone[6],timec[30],savetime[30];
    char json_query[24000],json_str[1500];
    PGconn		*conn;
    PGresult		*res,*json_res;
    gg.f=0;
    gg.CRC_OKK[2]=gg.rxbuf[2];gg.CRC_OKK[3]=gg.rxbuf[3];
    openlog("Parsdata_355_psql",LOG_PID,LOG_LOCAL0);
    memset(&query,'\0',4096);
    memset(&json_query,'\0',24000);
    strcpy(json_query,"INSERT INTO sensors.vdatain (json_data) values");
    strcpy(query,"INSERT INTO data.gps_from_trackers(imei,tstamp,lat,lon,speed,dut,azimut,gsmsignal,gpsdop,signal_restart,volt,class) values");
    for(gg.i=0;gg.i<10000;gg.i++){
	if(gg.trackers[gg.i].ip==htonl(gg.clientaddr.sin_addr.s_addr)&&gg.trackers[gg.i].port==htons(gg.clientaddr.sin_port)){
		//syslog(LOG_DEBUG,"Принимаем данные от трекера %s, checkpoint = %d",mblock->cpoint[gg.i].imei,mblock->cpoint[gg.i].time);
		if(mblock->cpoint[gg.i].time<0){
			sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
			exit(0);
		}
		ipp=&gg.trackers[gg.i].ip;
		ip4=*ipp;
		if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Принимаем данные от %s с %d.%d.%d.%d:%d",gg.trackers[gg.i].imei,ip4.i4,ip4.i3,ip4.i2,ip4.i1,gg.trackers[gg.i].port);
		conn=PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);
		if(PQstatus(conn)==CONNECTION_BAD){
			syslog(LOG_DEBUG,"Ошибка подключения к DB");
			exit(0);
			}
		memset(&copy_lock,'\0',130);
		memset(&raw_lock,'\0',130);
		memset(&c1,'\0',100);
		memset(&r1,'\0',100);
		gg.tstamp=time(NULL);
		mblock->cpoint[gg.i].sessiontime=gg.tstamp;
		gettimeofday(&msec,NULL);
		timein=localtime(&gg.tstamp);
		strftime(zone,6,"%z",timein);
		strftime(timec,30,"/%Y-%m-%dT%H:%M:%S",timein);
		strftime(savetime,30,"%Y-%m-%d %H:%M:%S",timein);
		sprintf(copy_lock,"%s%s%s.%d%s",gg.copyd,gg.trackers[gg.i].imei,timec,msec.tv_usec,zone);
		sprintf(raw_lock,"%s%s%s.%d%s",gg.rawd,gg.trackers[gg.i].imei,timec,msec.tv_usec,zone);
		sprintf(c1,"%s%s",gg.copyd,gg.trackers[gg.i].imei);
		sprintf(r1,"%s%s",gg.rawd,gg.trackers[gg.i].imei);
		if(cr.ct==1)mkdir(c1);
		if(cr.rt==1)mkdir(r1);
		WriteRaw(&cr.rt,raw_lock,gg.rxbuf,&gg.byte_read);
		WriteCopy(&cr.ct,copy_lock,"%s",gg.trackers[gg.i].imei);
		
		for(x=4;x<gg.byte_read;x++)if(gg.rxbuf[x]==0x3d&&gg.rxbuf[x+1]==0x1e||gg.rxbuf[x]==0x5f&&gg.rxbuf[x+1]==0x1e)countdata++;
		if(gg.rxbuf[4]==0x3d&&gg.rxbuf[5]==0x1e&&gg.rxbuf[36]==0x3d&&gg.rxbuf[37]==0x1e||gg.rxbuf[4]==0x3d&&gg.rxbuf[5]==0x1e&&countdata==1){
			for(x=0;x<countdata;x++){
				z=x*32;
				p355=&gg.rxbuf[z+4];
				d355=*p355;
				p3=&d355.tmp[0];
				d3=*p3;
				memset(&str,'\0',200);
				memset(&json_str,'\0',1500);
				WriteCopy(&cr.ct,copy_lock,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",d3.t,d3.lat,d3.lng,d3.speed,d3.dir*2,d355.rx,d3.dop,d3.v1,d3.v2,boot_if_355());
				sprintf(str," ('%s',to_timestamp(%d),%d,%d,%d,E'{null,null}',%d,%d,%d,%d,E'{%d,%d}',2),",gg.trackers[gg.i].imei,d3.t,d3.lat,d3.lng,d3.speed,d3.dir*2,d355.rx,d3.dop,boot_if_355(),d3.v1,d3.v2);
				strcat(query,str);
				//sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.4\",\"module\":{\"name\":\"mega-gps\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d,\"gsm\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"satellites\":%d,\"data-valid\":%d,\"worked\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":\"null\",\"dut2\":\"null\"}}}'),",gg.tstamp,gg.trackers[gg.i].imei,d3.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if_355(),d355.lac,d355.ci,d355.mcc,d355.mnc,d355.rx,d3.t,d3.lat,d3.lng,d3.alt,d3.speed,d3.dir*2,d3.sat,d3.valid,d3.nogps,d3.dop,d355.temp,d3.v1,d3.v2);
				sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.7\",\"module\":{\"id\":\"1\",\"name\":\"mega-gps\",\"info\":\"mega-gps-v355\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d,\"checkpoint\":%d},\"gsm\":{\"gsm1\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"satellites\":%d,\"data-valid\":%d,\"worked\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":\"\",\"dut2\":\"\"}}}'),",gg.tstamp,gg.trackers[gg.i].imei,d3.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if_355(),checkPoint(d3.t,mblock->cpoint[gg.i].time,gg.i),d355.lac,d355.ci,d355.mcc,d355.mnc,d355.rx,d3.t,d3.lat,d3.lng,d3.alt,d3.speed,d3.dir*2,d3.sat,d3.valid,d3.nogps,d3.dop,d355.temp,d3.v1,d3.v2);
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
			//syslog(LOG_DEBUG,"%s",json_query);
			json_res=PQexec(conn,json_query);
			if(PQresultStatus(res)==PGRES_COMMAND_OK&&PQresultStatus(json_res)==PGRES_COMMAND_OK){
			//if(PQresultStatus(res)==PGRES_COMMAND_OK)
				if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Данные по трекеру %s успешно записаны в db",gg.trackers[gg.i].imei);
				sendto(gg.sock,gg.CRC_OKK,4,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				exit(0);
				}
			else {
				
				syslog(LOG_DEBUG,"Ошибка при записи данных в базу по трекеру %s",gg.trackers[gg.i].imei);
				syslog(LOG_DEBUG,"%s",json_query);
				exit(0);
				}
			}
		if(gg.rxbuf[4]==0x3d&&gg.rxbuf[5]==0x1e&&gg.rxbuf[36]==0x5f&&gg.rxbuf[37]==0x1e){
			for(x=0;x<countdata/2;x++){
				z=x*64;
				p355=&gg.rxbuf[z+4];
				d355=*p355;
				p3=&d355.tmp[0];
				d3=*p3;
				pdyt=&gg.rxbuf[z+36];
				ddyt=*pdyt;
				memset(&str,'\0',200);
				memset(&json_str,'\0',1500);
				WriteCopy(&cr.ct,copy_lock,"\n%d;%d;%d;%d;%d;%d;%d;%d;%d;%d;%d;%d",d3.t,d3.lat,d3.lng,d3.speed,d3.dir*2,d355.rx,d3.dop,d3.v1,d3.v2,boot_if_355(),ddyt.dyt1,ddyt.dyt2);
				sprintf(str," ('%s',to_timestamp(%d),%d,%d,%d,E'{%d,%d}',%d,%d,%d,%d,E'{%d,%d}',2),",gg.trackers[gg.i].imei,d3.t,d3.lat,d3.lng,d3.speed,ddyt.dyt1,ddyt.dyt2,d3.dir*2,d355.rx,d3.dop,boot_if_355(),d3.v1,d3.v2);
				strcat(query,str);
				//sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.4\",\"module\":{\"name\":\"mega-gps\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d,\"gsm\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"satellites\":%d,\"data-valid\":%d,\"worked\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":%d,\"dut2\":%d}}}'),",gg.tstamp,gg.trackers[gg.i].imei,d3.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if_355(),d355.lac,d355.ci,d355.mcc,d355.mnc,d355.rx,d3.t,d3.lat,d3.lng,d3.alt,d3.speed,d3.dir*2,d3.sat,d3.valid,d3.nogps,d3.dop,d355.temp,d3.v1,d3.v2,ddyt.dyt1,ddyt.dyt2);
				sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.7\",\"module\":{\"id\":\"1\",\"name\":\"mega-gps\",\"info\":\"mega-gps-v355\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":%d,\"versionsw\":%d,\"versionhw\":%d,\"boot-flag\":%d,\"checkpoint\":%d},\"gsm\":{\"gsm1\":{\"location-area-code\":%d,\"cell-index\":%d,\"mcc\":%d,\"mnc\":%d,\"rx-level\":%d}},\"locations\":{\"location1\":{\"tstamp\":\%d,\"latitude\":%d,\"longitude\":%d,\"altitude\":%d,\"speed\":%d,\"azimuth\":%d,\"satellites\":%d,\"data-valid\":%d,\"worked\":%d,\"quality\":%d}},\"sensors\":{\"temperature\":{\"tracker\":%d},\"voltage\":{\"external\":{\"ext1\":%d},\"internal\":{\"int1\":%d}},\"dut\":{\"dut1\":%d,\"dut2\":%d}}}'),",gg.tstamp,gg.trackers[gg.i].imei,d3.t,gg.trackers[gg.i].versionsw,gg.trackers[gg.i].versionhw,boot_if_355(),checkPoint(d3.t,mblock->cpoint[gg.i].time,gg.i),d355.lac,d355.ci,d355.mcc,d355.mnc,d355.rx,d3.t,d3.lat,d3.lng,d3.alt,d3.speed,d3.dir*2,d3.sat,d3.valid,d3.nogps,d3.dop,d355.temp,d3.v1,d3.v2,ddyt.dyt1,ddyt.dyt2);
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
			//syslog(LOG_DEBUG,"%s",json_query);
			json_res=PQexec(conn,json_query);
			if(PQresultStatus(res)==PGRES_COMMAND_OK&&PQresultStatus(json_res)==PGRES_COMMAND_OK){
			//if(PQresultStatus(res)==PGRES_COMMAND_OK)
				if(gg.debug_level>2&&gg.debug_level<5)syslog(LOG_DEBUG,"Данные по трекеру %s успешно записаны в db",gg.trackers[gg.i].imei);
				sendto(gg.sock,gg.CRC_OKK,4,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				exit(0);
				}
			else{
				if(gg.debug_level>1&&gg.debug_level<5)syslog(LOG_DEBUG,"Ошибка при записи данных в базу по трекеру %s",gg.trackers[gg.i].imei);
				syslog(LOG_DEBUG,"%s",json_query);
				exit(0);
				}
			}
		gg.f=1;
		break;
	}
    }
    if(gg.f==0){
	if(gg.debug_level>2&&gg.debug_level<5){
		int ip_ip;
		ip_ip=htonl(gg.clientaddr.sin_addr.s_addr);
		ipp=&ip_ip;
		ip4=*ipp;
		syslog(LOG_DEBUG,"Отправляем запрос %d.%d.%d.%d:%d на установление новой сесии",ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
	}
	sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
    }
}

