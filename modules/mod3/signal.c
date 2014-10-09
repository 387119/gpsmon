#include "gpsmon.h"

extern void kill_zombies(int i){
	int status;
	wait(&status);
}