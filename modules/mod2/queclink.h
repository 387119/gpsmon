#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/types.h>
#include <unistd.h>
#include <sys/syslog.h>
#include <libpq-fe.h>
#include <sys/time.h>
#include <time.h>
#include <wait.h>
#include <signal.h>

#define PORT 9014


//Функция демона модуля queclink
extern void queclinkd(void);

//Создание сокета и прослушивание UDP порта
extern void createSocket(void);

//Функция получения типа пакета
extern void GetHead(char *in, char *out);

//Получаем номер заголовка
extern int GetNumericHead(char *in);

//Функции обработки данных трекера GT200
extern void unknow(char *in);


//Heartbeat packet
extern void psql_gthbd(char *in);		//Пакет авторизации

//Device information reports
extern void psql_gtinf(char *in);		//Информационный пакет

//Event Reports
extern void psql_gtpdp(char *in);
extern void psql_gtbtc(char *in);
extern void psql_gtstc(char *in);
extern void psql_gtepn(char *in);

//Position Reports
extern void psql_gtfri(char *in);		//Пакет с данными
extern void psql_gtstt(char *in);		//Пакет с данными
extern void psql_gtdog(char *in);		//Пакет с данными

//Location by call report
extern void psql_gtlbc(char *in);		//Пакет с данными

//Kill zombies process
extern void kill_zombies(int i);

//Convertion str time into unix tstamp
unsigned int strtotime(char *time);

struct sockaddr_in	servaddr;
struct sockaddr_in	clientaddr;
int			size_clientaddr;



FILE	*pidfile;

time_t	tstamp;

int	pid,pid2;
int	sock, bind_sock;


int	byte_read;
char	rxbuf[2048], txbuf[2048];

char	head[20];

PGconn	*conn;
PGresult *json_res;
PGresult *res;

