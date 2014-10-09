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

#define PORT 9015



//Создание сокета и прослушивание UDP порта
extern void createSocket(void);

//Kill zombies process
extern void kill_zombies(int i);

//The main function for child after fork()
extern void AndroidLogger(void);

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

