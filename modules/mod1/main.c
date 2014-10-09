#include "mega-gps.h"
#include "port.h"
#include "functions.h"
#include "func_psql.h"
#include "functions_v344.h"
#include "functions_v355.h"

void startModule(void)
{
int t_ip;
openlog("INFO",LOG_PID,LOG_LOCAL1);
gg.debug_level=get_debug_level();							//Получаем дебаг левел
//gg.type_save=get_type_save();
get_data_path ();									
get_copy_path ();
get_raw_path ();
m4 ();m5 ();
ex = 1;
gstime = 0;
gg.port = getPort ();									//Читаем порт
createSocket (&gg.port);								//Создаем сокет
syslog(LOG_INFO,"Модуль Mega-gps-v355 запушен на порту %d",gg.port);
syslog(LOG_INFO,"DEBUG_LEVEL=%d",gg.debug_level);
gg.type_save=get_type_save();
gg.size_clientaddr = sizeof (gg.clientaddr);			
gg.fork_counter=0;									//Счетчик для функции Fork(), для обработки <defunct>
gg.counter=0;										//Счетчик для структуры трекеров
gg.packet_counter=0;
gg.CRC_NO[0]=0xFF;gg.CRC_NO[1]=0;
gg.CRC_OK[0]=0x01;gg.CRC_OK[1]=0;
gg.CRC_OKK[0]=0x03;gg.CRC_OKK[1]=2;
key = ftok(FTOK_FILE, 1);
if(key==-1){
	syslog(LOG_INFO,"Функция ftok() вернулась ошибкой");
	exit(0);
}
shmid = shmget(key,sizeof(struct memory_block),0666 | IPC_CREAT);
if(shmid==-1){
	syslog(LOG_INFO,"Функция shmget() вернулась ошибкой");
	//exit(0);
}
mblock = (struct memory_block *)shmat(shmid, 0 ,0);
struct sigaction sa;
sa.sa_handler = prog_ex;
sigaction(SIGUSR1, &sa, 0);

while(ex==1){
	checkOnlineCars(); 			//Чистим структур авторизованных авто
	memset (&gg.rxbuf,'\0',2048);							//Чистим буфер
	memset (&gg.clientaddr,'\0',sizeof(gg.clientaddr));				//Чистим структуру данных от трекеров
	gg.byte_read = recvfrom (gg.sock,gg.rxbuf,2048,0,(struct sockaddr *)&gg.clientaddr,&gg.size_clientaddr);	//Принимаем данные
	//printf("%p\n",gg.rxbuf[0]);
	if(gg.debug_level>3&&gg.debug_level<5){
		openlog("Mega-gps-v355",LOG_PID,LOG_LOCAL0);
		syslog(LOG_DEBUG,"Приняли %d байт",gg.byte_read);
		closelog();
		}
	if (gg.rxbuf[0]==0x02){								//Если первая ячейка буфера равна 0x02 то выполняем следующий блок
		switch (gg.rxbuf[4]) {
			case 0x30:
				//printf("INIT\n");
				init_355();						//Вызываем функцию обработки запроса init
				break;
			case 0x31:
				session_355();					//Вызываем функцию авторизации трекера на сервере
				//printf("Session_355()\n");
				break;
			case 0x3a:
				//printf("0x3a\n");
				break;
			case 0x36:
				//printf("0x36\n");
				gg.CRC_OKK[2]=gg.rxbuf[2];
				gg.CRC_OKK[3]=gg.rxbuf[3];
				sendto(gg.sock,gg.CRC_OKK,4,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				break;
			case 0x4:
				openlog("WARNING",LOG_PID,LOG_LOCAL1);
				syslog(LOG_WARNING,"Неопознанный тип тега '%p', версия ПО трекера 355",gg.rxbuf[4]);
				closelog();
				sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				break;
			case 0x3d:
				gg.packet_counter++;
				gg.pid1 = fork();					//Раздваиваем процесс
				switch(gg.pid1){
					case 0:
						switch(gg.type_save){
							case 0:
								break;
							case 1:
								parsdata_355_psql();
								break;
							case 2:
								
								parsdata_355();
								break;
							default:
								break;
							}
						exit(0);
						break;
					case -1:
						openlog("ERROR",LOG_PID,LOG_LOCAL1);
						syslog(LOG_DEBUG,"Ошибка вызова функции fork()");			//Fork вернул ошибку
						closelog();
						break;
					default:
						gg.fork_counter++;			//Процесс родитель
						if(gg.fork_counter>29){			//проверяем если счетчик fork>29
							while(wait(&gg.status)>0){}	//Убиваем процессы <defunct>
							gg.fork_counter=0;		//сбрасываем счетчик
							}
					}
				break;
			default:
				openlog("WARNING",LOG_PID,LOG_LOCAL1);
				syslog(LOG_WARNING,"Неопознанный тип тега '%p', версия ПО трекера 355",gg.rxbuf[4]);
				closelog();
				//sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				break;
			}
		}
	else {									//Если первая ячейка буфера не равна 0х02 то выполняем следующий блок
		switch(gg.rxbuf[0]){
			case 0x30:
				//if(gg.debug_level>0&&gg.debug_level<5)syslog(LOG_DEBUG,"Запрос init, Вызываем функцию init_344()");
				init_344();
				break;
			case 0x31:
				//if(gg.debug_level>0&&gg.debug_level<5)syslog(LOG_DEBUG,"Запрос авторизации, вызываем функцию session_344()");
				session_344();
				break;
			case 0x3a:
				break;
			case 0x36:
				sendto(gg.sock,gg.CRC_OK,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(gg.clientaddr));
				break;
			case 0x3f:
				gg.packet_counter++;
				gg.pid1=fork();
				switch(gg.pid1){
					case 0:
						switch(gg.type_save){
							case 0:
								break;
							case 1:
								
								parsdata_344_psql();
								break;
							case 2:
								
								parsdata_344();
								break;
							default:
								break;
						}
						exit(0);
						break;
					case -1:
						syslog(LOG_DEBUG,"Ошибка вызова функции fork()");
						break;
					default:
						//printf("Data\n");
						gg.fork_counter++;
						if(gg.fork_counter>29){
							while(wait(&gg.status)>0){}
							gg.fork_counter=0;
							}
					}
				break;
			default:
				t_ip=htonl(gg.clientaddr.sin_addr.s_addr);
				ipp=&t_ip;
				ip4=*ipp;
				openlog("WARNING",LOG_PID,LOG_LOCAL1);
				syslog(LOG_WARNING,"Неопознанный тип тега '%p', версия ПО трекера 344  %d.%d.%d.%d:%d",gg.rxbuf[0],ip4.i4,ip4.i3,ip4.i2,ip4.i1,htons(gg.clientaddr.sin_port));
				//sendto(gg.sock,gg.CRC_NO,2,0,(struct sockaddr *)&gg.clientaddr,sizeof(&gg.clientaddr));
				closelog();
				break;
			}
		}

	memset(&gg.rxbuf,'\0',2048);
	memset(&gg.byte_read,'\0',4);
	}
	shmdt((void *) mblock);
	shmctl(shmid,IPC_RMID, 0);
	syslog(LOG_INFO,"Нормальнное завершение программы");
}


int main (int argc, char *argv[])
{
//startModule();

	FILE* pidfile;
	gg.pid=fork();
	switch(gg.pid){
		case 0:
			setsid();
			chdir("/");
			close(STDIN_FILENO);
			close(STDOUT_FILENO);
			close(STDERR_FILENO);
			startModule();
			_exit(0);
		case -1:
			printf("Error starting program\n");
			break;
		default:
			pidfile=fopen("/var/run/mega-gps-v355.pid","w");
			fprintf(pidfile,"%d",gg.pid);
			printf("Programm started with pid %d\n",gg.pid);
			fclose(pidfile);
		}

return 0;
}