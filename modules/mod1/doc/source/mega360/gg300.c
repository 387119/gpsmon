//#define	USE_MYSQL
#define	MAX_DEV	1000

#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <stdarg.h>
#include <netinet/in.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <dirent.h>
#include <time.h>
#include <fcntl.h>
#ifdef	USE_MYSQL
#include "mysql.h"
#endif
#include "mega_gps.h"
//================
void	debugf(char * f, ...)
{
	time_t t = time(0);
	struct tm * lt = localtime(&t);
	printf("%04d-%02d-%02d %02d:%02d:%02d ", lt->tm_year+1900, lt->tm_mon+1, lt->tm_mday, lt->tm_hour, lt->tm_min, lt->tm_sec);
	vprintf(f, (void *)((&f) + 1));
}
//======== mysql example
int	search_by_imei(char * imei)
{
	int	myid = -1;
#ifdef	USE_MYSQL
	sprintf(gg.myQuery, "SELECT id FROM my_table WHERE imei='%s'", imei);
	if(mysql_query(&gg.mysql, gg.myQuery)){
		debugf("%s\n", mysql_error(&gg.mysql));
		return 0;
	}
	gg.res = mysql_store_result(&gg.mysql);
	if(gg.row = mysql_fetch_row(gg.res)){
		if(gg.row[0])
			myid = atoi(gg.row[0]);
	}
	mysql_free_result(gg.res);
#endif
	return myid;
}
//================
unsigned long crc32_table[256];
unsigned long crc;
//================
void	crc32_init(void)
{
	int i, j;
	for (i = 0; i < 256; i++){
		crc = i;
		for (j = 0; j < 8; j++)
			crc = crc & 1 ? (crc >> 1) ^ 0xEDB88320UL : crc >> 1;
		crc32_table[i] = crc;
	}
}
//================
void	crc32_reset(void)
{
	crc = 0xFFFFFFFFUL;
}
//================
unsigned long crc32(unsigned char *buf, unsigned long len)
{
	while (len){
		crc = crc32_table[(crc ^ *buf++) & 0xFF] ^ (crc >> 8);
		len --;
	}
	return crc ^ 0xFFFFFFFFUL; //конец функции
};
//================
void error(char *msg)
{
	perror(msg);
	exit(1);
}
//================
void	debug_dev(char * format, ...)
{
	static char s[256];
	int f;
	if(gg.dbid < 0)
		sprintf(gg.fname, "%s/%s/log", gg.historyPath, gg.pdi->imei);
	else
		sprintf(gg.fname, "%s/%d/log", gg.historyPath, gg.dbid);
	if(f = open(gg.fname, O_WRONLY|O_CREAT|O_APPEND)){
		int len;
		time_t t;
		struct tm * lt;
		time(&t);
		lt = localtime(&t);
		len = sprintf(s, "%04d-%02d-%02d %02d:%02d:%02d,", lt->tm_year+1900, lt->tm_mon+1, lt->tm_mday, lt->tm_hour, lt->tm_min, lt->tm_sec);
		write(f, s, len);
		len = vsprintf(s, format, (void *)((& format) + 1));
		write(f, s, len);
		close(f);
		chmod(gg.fname, 0666);
	}
}
//================
void	load_software(void)
{
	static char soft_dir[] = "soft";
	static char soft_hw_dir[256];
	static char soft_hw_fname[256];
	DIR 	* sdir;
	DIR 	* hwdir;
	struct dirent   *dit;
	int	hw;
	int	sw;
	int	fh;
	SOFTWARE_DATA * sd = &swData[0];
	swCount = 0;

	if((sdir = opendir(soft_dir)) == 0)
		return;
	while(dit = readdir(sdir)){
		if((hw = atoi(dit->d_name)) <= 0)
			continue;
		sprintf(soft_hw_dir, "%s/%s", soft_dir, dit->d_name);
		if((hwdir = opendir(soft_hw_dir)) == 0)
			continue;
		while(dit = readdir(hwdir)){
			if((sw = atoi(dit->d_name)) <= 0)
				continue;
			sprintf(soft_hw_fname, "%s/%s", soft_hw_dir, dit->d_name);
			if((fh = open(soft_hw_fname, O_RDONLY)) > 0){
				if((sd->len = read(fh, sd->buf, sizeof(sd->buf))) > 0){
					sd->hw = hw;
					sd->sw = sw;
					crc32_reset();
					sd->crc = crc32(sd->buf, sd->len);
					sd ++;
					swCount ++;
				}
				close(fh);
			}
			break;	// One sotf ver only
		}
		closedir(hwdir);
		if(swCount >= MAX_SW)
			break;
	}
	closedir(sdir);
//	load_hwc();
}
//================
SOFTWARE_DATA *	search_software(int hw)
{
	int i;
	SOFTWARE_DATA * sd = &swData[0];

	for(i=0; i<swCount; i++){
		if(sd->hw == hw)
			return sd;
		sd ++;
	}
	return 0;
}
//================
void	load_config()
{
	char str[256];
	FILE * fd;

#ifdef USE_MYSQL
	strcpy(gg.myHost, MEGA_GPS_DEFAULT_MYHOST);
	strcpy(gg.myLogin, MEGA_GPS_DEFAULT_MYLOGIN);
	strcpy(gg.myPassword, MEGA_GPS_DEFAULT_MYPASSWORD);
	strcpy(gg.myDb, MEGA_GPS_DEFAULT_MYDB);
#endif
	strcpy(gg.serverIp, MEGA_GPS_DEFAULT_IP);
	gg.serverPort = MEGA_GPS_DEFAULT_PORT;
	strcpy(gg.historyPath, MEGA_GPS_DEFAULT_PATH);

	fd = fopen(MEGA_GPS_CONFIG_FILE, "r");
	if(! fd)
		error("No config file found");
	while(fgets(str, sizeof(str), fd)){
#ifdef USE_MYSQL
		sscanf(str, "mysql_host=%s", gg.myHost);
		sscanf(str, "mysql_login=%s", gg.myLogin);
		sscanf(str, "mysql_password=%s", gg.myPassword);
		sscanf(str, "mysql_db=%s", gg.myDb);
#endif
		sscanf(str, "server_ip=%s", gg.serverIp);
		sscanf(str, "server_port=%u", &gg.serverPort);
		sscanf(str, "history_path=%s", gg.historyPath);
	}
	fclose(fd);
}
//================
void	txtag(unsigned char type, void * p, unsigned long len)
{
	gg.txbuf[gg.txlen ++] = type;
	gg.txbuf[gg.txlen ++] = len;
	if(len)
		memcpy(gg.txbuf + gg.txlen, p, len);
	gg.txlen += len;
}
//================
void	search_id_by_ip(void)
{
	int	i;
	for(i=0, gg.pdi=&gg.di[0]; i<MAX_DEV; i++, gg.pdi++){
		if(gg.pdi->port == gg.devSa.sin_port && gg.pdi->ip == gg.devSa.sin_addr.s_addr){
			gg.id = i;
			gg.dbid = gg.pdi->dbid;
			return;
		}
	}
	gg.id = -1;
	gg.dbid = -1;
}
//================
int	reg1_proc(void)
{
	time(&gg.timesec);
	txtag(0x43, &gg.timesec, sizeof(gg.timesec));
	struct timeval ts;
	gettimeofday(&ts, 0);
	unsigned long	crc = crc32((unsigned char *) & ts, sizeof(ts));	// Random
	txtag(0x41, &crc, sizeof(crc));
	debugf("INIT from %s\n", gg.addr_port);
	return 1;
}
//================
int	reg2_proc(void)
{
	int	i;
	gg.pdi = &gg.di[0];
	gg.id = -1;
	for(i=0; i<=gg.maxid; i++, gg.pdi++){		// Search in registered devices
		if(! strcmp(gg.imei, gg.pdi->imei)){
			gg.id = i;
			gg.dbid = gg.pdi->dbid;
			gg.pdi->ip = gg.devSa.sin_addr.s_addr;
			gg.pdi->port = gg.devSa.sin_port;
			gg.pdi->t = gg.timesec;
			debug_dev("IP changed to %s\n", gg.addr_port);
			debugf("[%d,%s] IP changed to %s\n", gg.dbid, gg.pdi->imei, gg.addr_port);
			return 1;
		}
	}
	for(i=0, gg.pdi=&gg.di[0]; i<MAX_DEV; i++, gg.pdi++){	// Search for first free gg.id
		if(! gg.pdi->ip){
			gg.id = i;
			break;
		}
	}
	if(gg.id < 0)		// Can not register
		return 0;

	gg.pdi->ip = gg.devSa.sin_addr.s_addr;
	gg.pdi->t = gg.timesec;
	gg.pdi->port = gg.devSa.sin_port;
	strcpy(&gg.pdi->imei[0], gg.imei);


	gg.pdi->dbid = gg.dbid = search_by_imei(gg.imei);
//	gg.pdi->dbid = gg.dbid = -1;

	debug_dev("online from %s\n", gg.addr_port);
	debugf("[%d,%s] ONLINE from %s, Harware: %d, Software: %d, ICCID: %s, SIM num: %s\n", gg.dbid, gg.imei, gg.addr_port, gg.hv, gg.sv, gg.iccid, gg.own_number);

	if(gg.id > gg.maxid)
		gg.maxid = gg.id;

/*
we can add the code below if need

	txtag(0x04, &gg.id, sizeof(gg.id));
or
	txtag(0x04, &gg.dbid, sizeof(gg.dbid));
and / or
	char imeiFlag = 1;
	txtag(0x45, &imeiFlag, sizeof(imeiFlag));
*/
	return 1;
}
//================
void	data_convert(void * p)
{
	MEGA_GPS_355 * pnew = p;
	MEGA_GPS_300 * pold = p;

	if(pold->type == 0x3F) pnew->type = 0x3D;
	else if(pold->type == 0x3E) pnew->type = 0x3C;

// store changed field in temp vars
	long	lat = pold->lat;
	long	lng = pold->lng;
	unsigned short	speed = pold->speed + (pold->speed_hi << 8);
	unsigned short	alt = pold->alt + (pold->alt_hi << 8);
	unsigned char	dir = pold->dir;
	unsigned short	v1 = pold->v1 = (pold->v1 << 8);
	unsigned short	v2 = pold->v2 = (pold->v2 << 8);

// store temp vars back to data structure
	pnew->reserved = 0;
	pnew->sat = 0;
	pnew->nogps = 0;
	pnew->speed = speed;
	pnew->alt = alt;
	pnew->dir = dir;
	pnew->v1 = v1;
	pnew->v2 = v2;
	pnew->net = 0;

	if(lat == 0x80000000){
		pnew->valid = 0;
		if(lng == 0)
			pnew->nogps = 1;
	}
	else{
		pnew->valid = 1;
		if(lng < 0){
			pnew->west = 1;
			pnew->lng = -lng;
		}
		else{
			pnew->west = 0;
			pnew->lng = lng;
		}
		if(lat < 0){
			pnew->south = 1;
			pnew->lat = -lat;
		}
		else{
			pnew->south = 0;
			pnew->lat = lat;
		}
	}
}
//================
void	data_proc(MEGA_GPS_355 * p)
{
	unsigned char type = p->type;
	long	t = p->t;

debugf("[%d,%s] TRACK valid: %d, sat_n: %d, %d.%06d, %d.%06d, %d km/h, %d.%02d V, ...\n", gg.dbid, gg.pdi->imei, p->valid, p->sat, 
	p->lat/600000, p->lat%600000, p->lng/600000, p->lng%600000, p->speed, p->v1/100, p->v1%100);

	if(t > gg.pdi->tlast){
		gg.pdi->tlast = t;
		gg.flags |= MEGA_GPS_LAST;

		if(p->valid){
			gg.pdi->tvalid = t;
			gg.flags |= MEGA_GPS_VALID;
		}
	}

	if(t > gg.pdi->thist && type == 0x3D){
		gg.pdi->thist = t;
		gg.flags |= MEGA_GPS_HIST;
		if(gg.dbid < 0)
			sprintf(gg.iddir, "%s/%s", gg.historyPath, gg.pdi->imei);
		else
			sprintf(gg.iddir, "%s/%d", gg.historyPath, gg.dbid);

		if(! mkdir(gg.iddir, 0777))
			chmod(gg.iddir, 0777);

		struct tm * lt;
		lt = localtime((time_t *) &t);
		sprintf(gg.fname, "%s/%04u%02u%02u.dat", gg.iddir, lt->tm_year+1900, lt->tm_mon+1, lt->tm_mday);

		int	f;
		if(f = open(gg.fname, O_WRONLY|O_CREAT|O_APPEND)){
			write(f, p, sizeof(MEGA_GPS_355));
			close(f);
//			chmod(gg.fname, 0666);
		}
	}
}
//================
void	txtag_sw_info(int len)
{
	if(len == 0){
		swHeader.sw = 0;
		swHeader.len = 0;
	}
	else
		swHeader.len = len;
	swHeader.pos = 0x10000000;
	txtag(0x4A, &swHeader, sizeof(swHeader));
}
//================
void	sw_proc(void)
{
	SOFTWARE_DATA * sd;
	int	len;
	int	f = 0;

//====== Moscow Patch
//	int	hw = search_hwc();
//	if(hw == 0)
	int	hw = swHeader.hw;

	if((sd = search_software(hw)) == 0){
		txtag_sw_info(0);
		debugf("[%d,%s] hw: %d, sw not found\n", gg.dbid, gg.pdi->imei, hw);
		return;
	}

	if(swHeader.sw == 0 || (swHeader.sw != sd->sw && swHeader.sw < 10000) || swHeader.pos > sd->len){
		swHeader.sw = sd->sw;

		txtag_sw_info(sd->len);
		memcpy(&gg.txbuf[gg.txlen], &sd->crc, sizeof(sd->crc));
		gg.txlen += sizeof(sd->crc);
		return;
	}

	len = sd->len - swHeader.pos; // rest len
	swHeader.len = len = (len > swHeader.len) ? swHeader.len : len;
	txtag(0x4A, &swHeader, sizeof(swHeader));
	memcpy(&gg.txbuf[gg.txlen], &sd->buf[swHeader.pos], len);
	gg.txlen += len;
	if(swHeader.pos == 0)
		debugf("[%d,%s] sw download\n", gg.dbid, gg.pdi->imei);

	if(swHeader.pos + len == sd->len){
		debug_dev("sw,%d,%d\n",swHeader.hw,swHeader.sw);
		debugf("[%d,%s] sw download ok\n", gg.dbid, gg.pdi->imei);
//		sprintf(gg.myQuery, "UPDATE dev SET tsw=%d WHERE gg.id=%d", gg.timesec, gg.id);
//		if(mysql_query(&gg.mysql, gg.myQuery))
//			debugf("%s\n", mysql_error(&gg.mysql));
	}
}
//================
char	devUssdRequest[512];
char	devUssdAnswer[512];
//================
void	str_prepare(char * p)
{
	char c;

	while(c = * p){
		if(c == 0x27)	// '
			* p = 0x22;	// "
		p ++;
	}
}
//================
void	storeUssdAnswer(void)
{
	struct tm * lt;
	int f;

	debugf("[%d,%s] USSD %s => %s\n", gg.dbid, gg.pdi->imei, devUssdRequest, devUssdAnswer);

	if(gg.dbid < 0)
		sprintf(gg.iddir, "%s/%s", gg.historyPath, gg.pdi->imei);
	else
		sprintf(gg.iddir, "%s/%d", gg.historyPath, gg.dbid);

	if(! mkdir(gg.iddir, 0777))
		chmod(gg.iddir, 0777);

	lt = localtime((time_t *) &gg.timesec);
	sprintf(gg.fname, "%s/ussd.csv", gg.iddir);

	if(f = open(gg.fname, O_WRONLY|O_CREAT|O_APPEND)){
		static char sz[1024];
		write(f, sz, sprintf(sz, "%04u-%02u-%02u %02u:%02u:%02u %s %s\n", 
				lt->tm_year+1900, lt->tm_mon+1, lt->tm_mday, lt->tm_hour, lt->tm_min, lt->tm_sec,
				devUssdRequest, devUssdAnswer));
		close(f);
		chmod(gg.fname, 0666);
	}
}
//================
int	parse300(void)
{
	unsigned long	len;
	unsigned long	pos = 0;
	unsigned char	type;
	unsigned long	value;

	// UDP Sequence number patch, firmware version 355 to 359 only
	{
		unsigned short word0 = *(unsigned short *)(void *) &gg.rxbuf;
		if(word0 & MEGA_GPS_SEQN){	// If seqn present in UDP packet
			// Store seqn to UDP answer
			*(unsigned short *)(void *) &gg.txbuf = word0 | MEGA_GPS_SEQN_ACK;
			pos += sizeof(word0);
			gg.txlen += sizeof(word0);
		
		}
	}

	gg.flags = 0;

	while(pos < gg.rxlen){
		type = gg.rxbuf[pos ++];
		len = gg.rxbuf[pos ++];
		if(len == 4)
			value = * (unsigned long *)(& gg.rxbuf[pos]);
//		else if(len == 2)
//			value = * (unsigned short *)(& gg.rxbuf[pos]);
		switch(type){
			case 0x02:	// seqn accepted, firmware version 360 or later
				txtag(0x03, &gg.rxbuf[pos], len);
				break;

			case 0x04:	// tracker ID, firmware version 360 or later
				// gg.id = value;
				break;

			case 0x30:
				gg.flags |= MEGA_GPS_INIT;
				return reg1_proc();
			case 0x31:
				if(len == 15){
					memcpy(gg.imei, & gg.rxbuf[pos], len);
					gg.imei[len] = 0;
					gg.flags |= MEGA_GPS_IMEI;
				}
				break;
			case 0x32:
				gg.flags |= MEGA_GPS_SW;
				gg.sv = value;
				break;
			case 0x33:
				gg.crc = value;
				break;
			case 0x34:
				if(len){
					gg.flags |= MEGA_GPS_NUM;
					memcpy(gg.own_number, & gg.rxbuf[pos], len);
					gg.own_number[len] = 0;
				}
				break;
			case 0x35:
				gg.flags |= MEGA_GPS_HW;
				gg.hv = value;
				break;
			case 0x36:
				if(gg.id < 0){
debugf("0x%02X tag from UNKNOWN %s\n", type, gg.addr_port);
					return -1;
				}
				if(len)
					memcpy(devUssdRequest, &gg.rxbuf[pos], len);
				devUssdRequest[len] = 0;
				gg.flags |= MEGA_GPS_USSDREQ;
				break;

			case 0x37:
				if(gg.id < 0){
debugf("0x%02X tag from UNKNOWN %s\n", type, gg.addr_port);
					return -1;
				}
				if(len)
					memcpy(devUssdAnswer, &gg.rxbuf[pos], len);
				devUssdAnswer[len] = 0;
				gg.flags |= MEGA_GPS_USSDANS;
				break;

			case 0x38:
				bzero(&gg.bi, sizeof(gg.bi));
				if(len>=16){
					gg.flags |= MEGA_GPS_BOOTINFO;
					memcpy(&gg.bi, &gg.rxbuf[pos], len);
				}
				break;

			case 0x39:
				if(len == 19){
					gg.flags |= MEGA_GPS_ICCID;
					memcpy(&gg.iccid, &gg.rxbuf[pos], len);
					gg.iccid[len] = 0;
				}
				break;

			case 0x3A:
				if(len == 16){
					memcpy(&swHeader, & gg.rxbuf[pos], 16);
					sw_proc();
					return 1;
				}
				return 0;

			case 0x3E:	// Old version data tags
			case 0x3F:
				data_convert(& gg.rxbuf[pos - 2]);
			case 0x3C:
			case 0x3D:
				if(gg.id < 0){
debugf("0x%02X tag from UNKNOWN %s\n", type, gg.addr_port);
					return -1;
				}
				gg.flags |= MEGA_GPS_DATA;
				data_proc((MEGA_GPS_355 *) (& gg.rxbuf[pos - 2]));
				break;

			case 0x5F:
//				fuel_proc((void *) (& gg.rxbuf[pos - 2]));
				break;

		}
		pos += len;
	}

	if(gg.flags == MEGA_GPS_INIT)
		return	reg1_proc();

	if(gg.flags & MEGA_GPS_SW)
		return	reg2_proc();

	if(gg.flags & MEGA_GPS_USSDREQ && gg.flags & MEGA_GPS_USSDANS)
		storeUssdAnswer();

/*
	if(gg.flags & MEGA_GPS_DATA){
		if(gg.flags & MEGA_GPS_LAST){
			//
		}
		if(gg.flags & MEGA_GPS_VALID){
			// Valid GPS data has been changed
		}
	}
*/
	gg.di[gg.id].t = gg.timesec;	// Update online timestamp
	return 1;
}
//================
int	main(int argc, char * argv[])
{
	int i, n;
	struct timeval timeout;
	int	run = 1;

	setbuf(stdout,NULL);
	debugf("gpc300 started\n");

	gg.maxid = 0;
	crc32_init();
	load_software();
	load_config();
	if(! mkdir(gg.historyPath, 0777))
		chmod(gg.historyPath, 0777);

	bzero((char *) gg.di, sizeof(gg.di));

	if((gg.mainSock = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP)) < 0)
		error("ERROR: socket()");

	bzero((char *) &gg.mainSa, sizeof(gg.mainSa));
	gg.mainSa.sin_family = AF_INET;
	gg.mainSa.sin_port = htons(gg.serverPort);
	gg.mainSa.sin_addr.s_addr = inet_addr(gg.serverIp); // INADDR_ANY;

	if(bind(gg.mainSock, (struct sockaddr *) & gg.mainSa, sizeof(struct sockaddr)) < 0)
		error("ERROR: bind()");
	if(fcntl(gg.mainSock, F_SETFL, O_NONBLOCK))
		error("ERROR: fcntl()");
#ifdef USE_MYSQL
	mysql_init(&gg.mysql);
	{
	my_bool	opt = 1;
	mysql_options(&gg.mysql, MYSQL_OPT_RECONNECT, & opt);
	}
	if(! mysql_real_connect(&gg.mysql, gg.myHost, gg.myLogin, gg.myPassword, gg.myDb, 0, NULL, 0)){
		error((char *) mysql_error(&gg.mysql));
	}
#endif

	while(run){
		gg.timesec = time(0);
//printf("gg.timesec: %d\n", gg.timesec);
		timeout.tv_sec  = 1;
		timeout.tv_usec = 0;
		FD_ZERO(&gg.rd);
		FD_SET(gg.mainSock, &gg.rd);
		n = select(gg.mainSock + 1, &gg.rd, 0, 0, &timeout);
		if(n < 0)
			perror("ERROR: select()");
		else{
			int ret;
			gg.devSaLen = sizeof(gg.devSa);
			while((gg.rxlen = recvfrom(gg.mainSock, gg.rxbuf, sizeof(gg.rxbuf), 0, (struct sockaddr *) &gg.devSa, &gg.devSaLen)) > 0){


				sprintf(gg.addr_port, "%s:%u", inet_ntoa(gg.devSa.sin_addr.s_addr), gg.devSa.sin_port);
//sprintf(gg.addr_port, "%u.%u.%u.%u:%u", *(((unsigned char*)&gg.devSa.sin_addr.s_addr)+0), *(((unsigned char*)&gg.devSa.sin_addr.s_addr)+1), *(((unsigned char*)&gg.devSa.sin_addr.s_addr)+2), *(((unsigned char*)&gg.devSa.sin_addr.s_addr)+3), gg.devSa.sin_port);

				search_id_by_ip();
				gg.txlen = 0;
				ret = parse300();
				if(ret == -1){
					gg.txlen = 0;
					gg.txbuf[gg.txlen++] = 0xFF;
					gg.txbuf[gg.txlen++] = 0x00;
debugf("RESTART session => %s\n", gg.addr_port);
				}
				if(ret){
					sendto(gg.mainSock, gg.txbuf, gg.txlen, 0, (struct sockaddr *) &gg.devSa, sizeof(gg.devSa));
				}
			}
		}

		if(gg.timesec != gg.timeold){		// Each new second
			int	newmax = 0;
			gg.timeold = gg.timesec;
#ifdef USE_MYSQL
			mysql_ping(&gg.mysql);
#endif
			for(gg.id=0, gg.pdi=&gg.di[0]; gg.id<=gg.maxid; gg.id++, gg.pdi++){	// Unreg devices by timeout
				if(gg.pdi->ip)
					if(gg.timesec - gg.pdi->t > 90){
						debugf("[%d,%s] offline\n", gg.dbid, gg.pdi->imei);
						debug_dev("offline\n");
						bzero((char *) gg.pdi, sizeof(DEV_INFO));
				}
				if(gg.pdi->ip)
					if(gg.id > newmax)
						newmax = gg.id;
			}
			gg.maxid = newmax;
		}
	}
#ifdef USE_MYSQL
	mysql_close(&gg.mysql);
#endif
	close(gg.mainSock);
}
