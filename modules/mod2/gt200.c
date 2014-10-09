#include "queclink.h"

extern void psql_gthbd(char *in){
	char type[10],imei[15],name[20],time[14],count[5],version[12];
	char to[30];
	int start,stop;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				memset(&type,'\0',10);
				memcpy(&type,&in[start],start+10);
				num=2;
			}
			else if(num==2){
				memset(&version,'\0',12);
				memcpy(&version,&in[start],i-start);
				num=3;
			}
			else if(num==3){
				memset(&imei,'\0',15);
				memcpy(&imei,&in[start],i-start);
				num=4;
			}
			else if(num==4){
				memset(&name,'\0',20);
				memcpy(&name,&in[start],i-start);
				num=5;
			}
			else if(num==5){
				memset(&time,'\0',14);
				memcpy(&time,&in[start],i-start);
				num=6;
			}
			else if(num==6){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=7;
			}
			start=i+1;
		}
		
		i++;
	}
	memset(&to,'\0',30);
	printf("version=%s\ncount=%s\n%s\n",version,count,to);
	sprintf(to,"+SACK:GTHBD,%6s,%4s$",version,count);
	printf("%s\n",to);
	sendto(sock,to,24,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
	
}

extern void psql_gtpdp(char *in){
	char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=7;
			}
			start=i+1;
		}
		
		i++;
	}
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
	
}


extern void psql_gtfri(char *in){
	unsigned char count[5],imei[15],lat[15],lon[15],alt[15],gpsdop[5],sendtime[15],gpstime[14],speed[10];
	char to[15],json_str[1024],json_query[2048],str[1024],str_query[2048];
	int start;
	int i=0,n,x;
	char s_gpstime[19],s_sendtime[19];
	unsigned char num=1;
	start=0;
	tstamp = time(NULL);
	memset(json_query,'\0',2048);
	memset(str_query,'\0',2048);
	strcpy(json_query,"INSERT INTO sensors.vdatain (json_data) values");
	strcpy(str_query,"INSERT INTO data.gps_from_trackers (imei,tstamp, lat, lon,speed, gpsdop,class) values");
	conn=PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);
	if(PQstatus(conn)==CONNECTION_BAD){
		syslog(LOG_DEBUG,"Ошибка подключения к DB");
		exit(0);
	}
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				memset(&imei,'\0',15);
				memcpy(&imei,&in[start],i-start);
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				memset(&gpsdop,'\0',5);
				memcpy(&gpsdop,&in[start],i-start);
				num=9;
			}
			else if(num==9){
				memset(&speed,'\0',10);
				memcpy(&speed,&in[start],i-start);
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				memset(&alt,'\0',15);
				memcpy(&alt,&in[start],i-start);
				num=12;
			}
			else if(num==12){				
				memset(&lon,'\0',15);
				memcpy(&lon,&in[start],i-start);
				num=13;
			}
			else if(num==13){
				memset(&lat,'\0',15);
				memcpy(&lat,&in[start],i-start);
				num=14;
			}
			else if(num==14){
				memset(&gpstime,'\0',14);
				memcpy(&gpstime,&in[start],i-start);
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				num=19;
			}
			else if(num==19){
				num=20;
			}
			else if(num==20){
				num=21;
			}
			else if(num==21){
				memset(&sendtime,'\0',15);
				memcpy(&sendtime,&in[start],i-start);
				num=22;
			}
			else if(num==22){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=23;
			}
			start=i+1;
		}
		i++;
	}
	sprintf(s_gpstime,"%c%c%c%c-%c%c-%c%c %c%c:%c%c:%c%c",gpstime[0],gpstime[1],gpstime[2],gpstime[3],gpstime[4],gpstime[5],gpstime[6],gpstime[7],gpstime[8],gpstime[9],gpstime[10],gpstime[11],gpstime[12],gpstime[13]);
	sprintf(s_sendtime,"%c%c%c%c-%c%c-%c%c %c%c:%c%c:%c%c",sendtime[0],sendtime[1],sendtime[2],sendtime[3],sendtime[4],sendtime[5],sendtime[6],sendtime[7],sendtime[8],sendtime[9],sendtime[10],sendtime[11],sendtime[12],sendtime[13]);
	memset(json_str,'\0',1024);
	memset(str,'\0',1024);
	sprintf(json_str," ('{\"class\":2,\"jsonversion\":\"0.1.6\",\"module\":{\"id\":2,\"name\":\"queclink\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":\"%s\"},\"locations\":{\"location1\":{\"tstamp\":\"%s\",\"latitude\":\"%s\",\"longitude\":\"%s\",\"altitude\":\"%s\",\"quality\":\"%s\"}}}');",tstamp,imei,s_sendtime,s_gpstime,lat,lon,alt,gpsdop);
	sprintf(str," ('%s',to_timestamp(%d),%d,%d,%d,%s,2);",imei,strtotime(s_gpstime),(int)((float)600000*atof(lat)),(int)((float)600000*atof(lon)),(int)atof(speed),gpsdop);
	strcat(json_query,json_str);
	strcat(str_query,str);
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	syslog(LOG_DEBUG,"Выполняем запрос");
	json_res=PQexec(conn,json_query);
	res=PQexec(conn,str_query);
	if(PQresultStatus(json_res)==PGRES_COMMAND_OK&&PQresultStatus(res)==PGRES_COMMAND_OK){
		syslog(LOG_DEBUG,"Данные по  %s успешно записаны в DB",imei);
		//syslog(LOG_DEBUG,"%s",json_query);
		//syslog(LOG_DEBUG,"%s",str_query);
		sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
		PQclear(json_res);
		PQclear(res);
		PQfinish(conn);
	}
	else {
		syslog(LOG_DEBUG,"Ошибка при записи данных в базу по трекеру %s",imei);
		syslog(LOG_DEBUG,"%s",json_query);
		syslog(LOG_DEBUG,"%s",str_query);
		PQclear(json_res);
		PQclear(res);
		PQfinish(conn);
	}
}

extern void psql_gtdog(char *in){

	unsigned char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;

	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){

			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				num=19;
			}
			else if(num==19){
				num=20;
			}
			else if(num==20){
				num=21;
			}
			else if(num==21){
				num=22;
			}
			else if(num==22){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=23;
			}
			start=i+1;
		}
		i++;
	}

	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));

}


extern void psql_gtinf(char *in){
	char count[5],rx[5],battery[5],volt[5],sendtime[15],imei[15];
	char to[15],json_str[1024],json_query[2048];
	char s_sendtime[19];
	memset(json_query,'\0',2048);
	strcpy(json_query,"INSERT INTO sensors.vdatain (json_data) values");
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	tstamp = time(NULL);
	conn=PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);
	if(PQstatus(conn)==CONNECTION_BAD){
		syslog(LOG_DEBUG,"Ошибка подключения к DB");
		exit(0);
	}
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				memset(&imei,'\0',15);
				memcpy(&imei,&in[start],i-start);
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				memset(&rx,'\0',5);
				memcpy(&rx,&in[start],i-start);
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				memset(&volt,'\0',5);
				memcpy(&volt,&in[start],i-start);
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				num=19;
			}
			else if(num==19){
				memset(&battery,'\0',5);
				memcpy(&battery,&in[start],i-start);
				num=20;
			}
			else if(num==20){
				num=21;
			}
			else if(num==21){
				num=22;
			}
			else if(num==22){
				num=23;
			}
			else if(num==23){
				num=24;
			}
			else if(num==24){
				memset(&sendtime,'\0',15);
				memcpy(&sendtime,&in[start],i-start);
				num=25;
			}
			else if(num==25){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=26;
			}
			start=i+1;
		}
		i++;
	}
	sprintf(s_sendtime,"%c%c%c%c-%c%c-%c%c %c%c:%c%c:%c%c",sendtime[0],sendtime[1],sendtime[2],sendtime[3],sendtime[4],sendtime[5],sendtime[6],sendtime[7],sendtime[8],sendtime[9],sendtime[10],sendtime[11],sendtime[12],sendtime[13]);
	memset(json_str,'\0',1024);
	sprintf(json_str," ('{\"class\":3,\"jsonversion\":\"0.1.6\",\"module\":{\"id\":2,\"name\":\"queclink\",\"version\":\"0.0.1\",\"tstamp\":%d},\"tracker\":{\"imei\":\"%s\",\"tstamp\":\"%s\"},\"gsm\":{\"rx-level\":\"%s\"},\"sensors\":{\"power\":{\"internal\":{\"int1\":{\"volt\":\"%s\",\"percent\":\"%s\"}}}}}');",tstamp,imei,s_sendtime,rx,volt,battery);
	strcat(json_query,json_str);
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	syslog(LOG_DEBUG,"Выполняем запрос");
	json_res=PQexec(conn,json_query);
	if(PQresultStatus(json_res)==PGRES_COMMAND_OK){
		syslog(LOG_DEBUG,"Данные по  %s успешно записаны в DB",imei);
		sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
		PQclear(json_res);
		PQfinish(conn);
	}
	else {
		syslog(LOG_DEBUG,"Ошибка при записи данных в базу по трекеру %s",imei);
		syslog(LOG_DEBUG,"%s",json_query);
		PQclear(json_res);
		PQfinish(conn);
	}

}

extern void psql_gtstt(char *in){
	unsigned char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				num=19;
			}
			else if(num==19){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=20;
			}
			start=i+1;
		}
		i++;
	}
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
}

extern void psql_gtbtc(char *in){
	unsigned char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=19;
			}
			start=i+1;
		}
		i++;
	}
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
}

extern void psql_gtepn(char *in){
	unsigned char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=19;
			}
			start=i+1;
		}
		i++;
	}
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
}


extern void psql_gtstc(char *in){
	unsigned char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				num=19;
			}
			else if(num==19){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=20;
			}
			start=i+1;
		}
		i++;
	}
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
}


extern void psql_gtlbc(char *in){
	unsigned char count[5];
	char to[15];
	int start;
	int i=0,n,x;
	unsigned char num=1;
	start=0;
	while(in[i]!='\0'){
		if(in[i]==','||in[i]=='$'){
			if(num==1){
				num=2;
			}
			else if(num==2){
				num=3;
			}
			else if(num==3){
				num=4;
			}
			else if(num==4){
				num=5;
			}
			else if(num==5){
				num=6;
			}
			else if(num==6){
				num=7;
			}
			else if(num==7){
				num=8;
			}
			else if(num==8){
				num=9;
			}
			else if(num==9){
				num=10;
			}
			else if(num==10){
				num=11;
			}
			else if(num==11){
				num=12;
			}
			else if(num==12){
				num=13;
			}
			else if(num==13){
				num=14;
			}
			else if(num==14){
				num=15;
			}
			else if(num==15){
				num=16;
			}
			else if(num==16){
				num=17;
			}
			else if(num==17){
				num=18;
			}
			else if(num==18){
				num=19;
			}
			else if(num==19){
				num=20;
			}
			else if(num==20){
				memset(&count,'\0',5);
				memcpy(&count,&in[start],4);
				num=21;
			}
			start=i+1;
		}
		i++;
	}
	memset(&to,'\0',15);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
}


extern void unknow(char *in){
	char count[5],to[15];
	memset(count,'\0',5);
	memset(to,'\0',15);
	memcpy(&count,&in[strlen(in)-5],4);
	sprintf(to,"+SACK:%s$",count);
	sendto(sock,to,11,0,(struct sockaddr *)&clientaddr, sizeof(clientaddr));
}


