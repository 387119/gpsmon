#include "queclink.h"


int main (int argc, char *argv[]){
//queclinkd();

	pid=fork();
	switch(pid){
		case 0:
			setsid();
			chdir("/");
			close(STDIN_FILENO);
			close(STDOUT_FILENO);
			close(STDERR_FILENO);
			queclinkd();
			_exit(0);
		case -1:
			printf("Error starting program\n");
			break;
		default:
			pidfile=fopen("/var/run/queclink.pid","w");
			fprintf(pidfile,"%d",pid);
			printf("Programm started with pid %d\n",pid);
			fclose(pidfile);
		}

return 0;
}