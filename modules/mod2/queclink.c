#include "queclink.h"

extern void queclinkd(void){
	openlog("INFO",LOG_PID,LOG_LOCAL2);
	createSocket();						//Создаем сокет и слушаем 9014 UDP порт
	conn=PQsetdbLogin("172.16.17.4","5432",NULL,NULL,"gpsmon","gpsmon",NULL);
	if(PQstatus(conn)==CONNECTION_BAD){
		syslog(LOG_DEBUG,"Ошибка подключения к DB");
		exit(0);
	}
	syslog(LOG_INFO,"Модуль Queclink запушен на порту %d",PORT);
	size_clientaddr = sizeof(clientaddr);
	while(1){
		memset(&rxbuf,'\0',2048);			//Очищаем буфер приема данных
		memset(&clientaddr,'\0',size_clientaddr);
		byte_read = recvfrom(sock,rxbuf,2048,0,(struct sockaddr *)&clientaddr,&size_clientaddr);
		printf("%s\n",rxbuf);
		memset(head,'\0',20);
		GetHead(rxbuf,head);				//Получаем Тип пакета
		switch(GetNumericHead(head)){
			case 1:
				psql_gthbd(rxbuf);
				break;
			case 2:
				psql_gtfri(rxbuf);
				break;
			case 3:
				psql_gtinf(rxbuf);
				break;
			case 4:
				psql_gtpdp(rxbuf);
				break;
			case 5:
				psql_gtstt(rxbuf);
				break;
			case 6:
				psql_gtbtc(rxbuf);
				break;
			case 7:
				psql_gtepn(rxbuf);
				break;
			case 8:
				psql_gtstc(rxbuf);
				break;
			case 9:
				psql_gtdog(rxbuf);
				break;
			case 10:
				psql_gtlbc(rxbuf);
				break;
			case 100:
				unknow(rxbuf);
				break;
			default:
				printf("This head '%s' is not supported\n",head);
				//openlog("WARNING",LOG_PID,LOG_LOCAL2);
				syslog(LOG_WARNING,"Неопознанный тип HEAD '%s'",head);
				//closelog();
				unknow(rxbuf);
				break;
		}
	}
	
}