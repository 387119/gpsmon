#include "queclink.h"

extern void queclinkd(void){
	openlog("INFO",LOG_PID,LOG_LOCAL2);
	createSocket();						//Создаем сокет и слушаем 9014 UDP порт
	syslog(LOG_INFO,"Модуль Queclink запушен на порту %d",PORT);
	struct sigaction sa;
	sa.sa_handler = kill_zombies;
	sigaction(SIGCHLD, &sa, 0);
	size_clientaddr = sizeof(clientaddr);
	while(1){
		sleep(1);
		memset(&rxbuf,'\0',2048);			//Очищаем буфер приема данных
		memset(&clientaddr,'\0',size_clientaddr);
		byte_read = recvfrom(sock,rxbuf,2048,0,(struct sockaddr *)&clientaddr,&size_clientaddr);
		pid2=fork();
		switch(pid2){
			case 0:
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
						syslog(LOG_WARNING,"Неопознанный тип HEAD '%s'",head);
						unknow(rxbuf);
						break;
				}
				exit(0);
			case -1:
				syslog(LOG_WARNING,"Функция форк верснулась ошибкой");
				break;
			default:
				
				break;
			
		}
	}
	
}