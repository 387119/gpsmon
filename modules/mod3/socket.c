#include "gpsmon.h"

extern void createSocket(void)
{
	sock=socket(AF_INET,SOCK_DGRAM,0);
	if(sock<0){
		openlog("ERROR",LOG_PID,LOG_LOCAL2);
		syslog(LOG_ERR,"Ошибка при создание сокета");
		closelog();
		exit(EXIT_FAILURE);
		}
	servaddr.sin_family=AF_INET;
	servaddr.sin_port=htons(PORT);
	servaddr.sin_addr.s_addr=htonl(INADDR_ANY);

	bind_sock=bind(sock, (struct sockaddr *)&servaddr, sizeof(servaddr));
	if(bind_sock<0){
		openlog("ERROR",LOG_PID,LOG_LOCAL2);
		syslog(LOG_ERR,"Ошибка при привязки сокета");
		closelog();
		exit(EXIT_FAILURE);
		}
}
