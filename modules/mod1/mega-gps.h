#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <dirent.h>
#include <stdarg.h>
#include <unistd.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/syslog.h>
#include <sys/time.h>
#include <time.h>
#include <netinet/in.h>
#include <stdint.h>
#include <libpq-fe.h>
//#include <postgresql/libpq-fe.h>


//#define DEFAULT_LOG_PATH "/var/gpsmod/mega-gps/mega-gps-v355.log"
//#define INVALID_LATITUDE 0x80000000

//Новый протокол
typedef struct{
	//unsigned char	type;		//Тип тега 0x3c или 0x3d
	//unsigned char	len;		//длина тега, всегда 30
	
	int t;				//timestamp
	unsigned int	lng:27;		//longitude * 600000
	unsigned int	west:1;		//WEST flag
	unsigned int	reserved:2;
	unsigned int	nogps:1;	//GPS receiver OFF or not present
	unsigned int	valid:1;	//GPS data valid
	
	unsigned int	lat:26;		//Latitude * 600000
	unsigned int	south:1;	//SOUTH flag
	unsigned int	sat:5;		//avaliable satellites count
	
	unsigned int	speed:12;	//speed in km/h
	unsigned int	alt:12;		//altitude in meters
	unsigned int	dir:8;		//azimuth / 2
	
	unsigned int	dop:8;		//DOP * 10 (GPS dilution of precision)
	unsigned int	v1:12;		//Power lotage * 100, Volt
	unsigned int	v2:12;		//Interval modem power or li-ion accu Voltage * 100, Volt
}MEGA_355;
MEGA_355 d3,*p3;

typedef struct{
	unsigned char	type;
	unsigned char	len;
	
	unsigned char	tmp[20];
	unsigned short	lac;		//GSM Location Area Code
	unsigned short	ci;		//GSM Cell Index
	unsigned short	mcc;		//GSM MCC (255 - Ukraine)
	unsigned char	mnc;		//GSM MNC
	unsigned char	rx:5;		//GSM rx level (0 - none, 31 - max)
	unsigned char	net:3;		//GSM modem status (temporary unused)
	
	unsigned char	boot_reason:2;	//Причина загрузки трекера
	unsigned char	boot_flag:1;	//Флаг загрузки трекера
	unsigned char	output:1;
	unsigned char	res2:2;
	unsigned char	input1:1;
	unsigned char	input1_changed:1;
	char	temp;			//Modem temperature, degrees C
}MEGA_GPS_355;
MEGA_GPS_355	d355,*p355;

//Старый протокол
typedef struct {
	unsigned char	type;
	unsigned char	len;
	
	char	t[4];
	char	lat[4];
	char	lng[4];
	unsigned char	dir;
	unsigned char	speed_hi:4;
	unsigned char	alt_hi:4;
	unsigned char	speed;
	unsigned char	alt;
	unsigned char	dop;
	unsigned char	v1_hi:4;
	unsigned char	v2_hi:4;
	unsigned char	v1;
	unsigned char	v2;
	unsigned short	lac;
	unsigned short	ci;
	unsigned short	mcc;
	unsigned char	mnc;
	unsigned char	rx:5;
	unsigned char	res1:3;
	unsigned char	boot_reason:2;
	unsigned char	boot_flag:1;
	unsigned char	output:1;
	unsigned char	res2:2;
	unsigned char	input:1;
	unsigned char	input_changed:1;
	unsigned char	temp;
}MEGA_GPS_344;
MEGA_GPS_344	d344,*p344;

typedef struct {
	FILE*	file_data;
	FILE*	file_copy;
	FILE*	file_raw;
	int	i,y,f;
	char	imei[15];
	
	char	*p;
	
	int	pid;
	int	pid1;
	int	status;
	unsigned char	debug_level:3;

	char	rawd[60];
	char	copyd[60];
	char	d[60];
	short port;

	struct sockaddr_in servaddr;
	struct sockaddr_in clientaddr;
	int	sock;
	int	listener;
	int	bind_sock;
	int	size_clientaddr;
	
	char	CRC_OK[2];
	char	CRC_NO[2];
	char	CRC_OKK[4];
	char	rxbuf[2048];
	char	txbuf[2048];
	int	byte_read;
	int	counter;
	int	fork_counter;
	int	packet_counter;
	
	struct	tracker {
		unsigned char	imei[15];
		int	ip;
		unsigned short	port;
		int	time;
		unsigned int versionsw;
		unsigned int versionhw;
		}www,trackers[10000];
	
	time_t	tstamp;
	int	time_init;
	int	time_data;
	short	type_save;
}MEGA;
MEGA gg;

typedef struct {
	unsigned char	i1;
	unsigned char	i2;
	unsigned char	i3;
	unsigned char	i4;
}ADDR;
ADDR ip4,*ipp;

typedef struct {
	int	t;
	int	lat;
	int	lng;
}mega;
mega md,*mp;

typedef struct dyt{
	char	type;
	char	len;
	char	t[4];
	short	dyt1;
	short	dyt2;
}dyt;
dyt ddyt,*pdyt;

struct cr{
	unsigned char	ct;
	unsigned char	rt;
}cr;