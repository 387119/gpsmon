#define	MEGA_GPS_CONFIG_FILE	"gg300.conf"
#define	MEGA_GPS_DEFAULT_IP	"0.0.0.0"
#define MEGA_GPS_DEFAULT_PORT	9012
#define MEGA_GPS_DEFAULT_PATH	"/var/lib/gg"
#define MEGA_GPS_DEFAULT_MYHOST	"localhost"
#define MEGA_GPS_DEFAULT_MYLOGIN	"gps"
#define MEGA_GPS_DEFAULT_MYPASSWORD	"password"
#define MEGA_GPS_DEFAULT_MYDB	"gps"

/****************
Firmware version 355 to 359 adds sequence number into each UDP packet (the first WORD in packet)
For compatibility with older firmware versions,
server must check if the sequence number WORD included into received UDP packet, like below

seqn = *(short *) pointer_to_received_udp_buffer;
if(seqn & MEGA_GPS_SEQN)	// If seqn WORD included into incoming packet
	add_to_udp_answer(seqn | MEGA_GPS_ACK);	// Add the seqn to UDP answer
****************/
#define MEGA_GPS_SEQN	0x0080	// this bit is set if UDP packet contains SEQN WORD
#define MEGA_GPS_SEQN_MASK	0xFF0F
#define MEGA_GPS_SEQN_ACK	0x00C0
#define MEGA_GPS_SEQN_NOACK	0x00A0
//================ UDP parser bit mask
#define MEGA_GPS_INIT	0x0001
#define MEGA_GPS_IMEI	0x0002
#define MEGA_GPS_SW	0x0004
#define MEGA_GPS_HW	0x0008
#define MEGA_GPS_NUM	0x0010
#define MEGA_GPS_ICCID	0x0020
#define MEGA_GPS_BOOTINFO	0x0040

#define	MEGA_GPS_DATA	0x0100
#define	MEGA_GPS_LAST	0x0200
#define MEGA_GPS_VALID	0x0400
#define	MEGA_GPS_HIST	0x0800

#define	MEGA_GPS_USSDREQ	0x1000
#define	MEGA_GPS_USSDANS	0x2000
#define MEGA_GPS_BOOT		0x4000
//================ Data align = 1 Byte
#pragma pack(1)
//================ NEW track data structure (firmware 355 or later)
typedef struct{
	unsigned char	type;		// 0x3C or 0x3D
	unsigned char	len;		// always 30 Bytes

	long	t;			// timestamp
	unsigned long	lng:27;		// longitude * 600000
	unsigned long	west:1;		// WEST flag
	unsigned long	reserved:2;
	unsigned long	nogps:1;	// GPS receiver OFF or not present
	unsigned long	valid:1;	// GPS data valid

	unsigned long	lat:26;		// latitude * 600000
	unsigned long	south:1;	// SOUTH flag
	unsigned long	sat:5;		// available satellites count

	unsigned long	speed:12;	// speed in km/h
	unsigned long	alt:12;		// altitude in meters
	unsigned long	dir:8;		// azimuth / 2

	unsigned long	dop:8;		// DOP * 10 (GPS dilution of precision)
	unsigned long	v1:12;		// Power voltage * 100, Volt
	unsigned long	v2:12;		// Internal modem power or li-ion accu Voltage * 100, Volt

	unsigned short	lac;		// GSM Location Area Code
	unsigned short	ci;		// GSM Cell Index
	unsigned short	mcc;		// GSM MCC (255 - Ukraine)
	unsigned char	mnc;		// GSM MNC
	unsigned char	rx:5;		// GSM rx level (0 - none, 31 - maximum)
	unsigned char	net:3;		// GSM modem status (temporary unused)

	unsigned char	boot_reason : 2;	 // Причина загрузки трекера
	unsigned char	boot_flag : 1;	 // Флаг загрузки трекера
	unsigned char	output1 : 1;	 // Состояние выхода
	unsigned char	res2 : 2;	 // Зарезервировано
	unsigned char	input1 : 1;	 // Состояние входа 1
	unsigned char	input1_changed : 1;	 // Флаг изменения входа 1
	char	temp;			// Modem temperature, degrees C
} MEGA_GPS_355;
//================ OLD track data structure
typedef struct{
	unsigned char	type;
	unsigned char	len;
	long	t;
	long	lat;
	long	lng;
	unsigned char	dir;
	unsigned char	speed_hi : 4;
	unsigned char	alt_hi : 4;
	unsigned char	speed;
	unsigned char	alt;
	unsigned char	dop;
	unsigned char	v1_hi : 4;
	unsigned char	v2_hi : 4;
	unsigned char	v1;
	unsigned char	v2;
	unsigned short	lac;
	unsigned short	ci;
	unsigned short	mcc;
	unsigned char	mnc;
	unsigned char	rx;
	unsigned char	boot_reason : 2;	 // Причина загрузки трекера
	unsigned char	boot_flag : 1;	 // Флаг загрузки трекера
	unsigned char	output1 : 1;	 // Состояние выхода
	unsigned char	res2 : 2;	 // Зарезервировано
	unsigned char	input1 : 1;	 // Состояние входа 1
	unsigned char	input1_changed : 1;	 // Флаг изменения входа 1
	char	temp;
} MEGA_GPS_300;
//================
typedef struct{
	unsigned long	hw;	// hardware version
	unsigned long	sw;	// software version
	unsigned long	pos;	// current packet position
	unsigned long	len;	// current packet length / total length if sw == 0
} MEGA_GPS_UPDATE_HEADER;

MEGA_GPS_UPDATE_HEADER	swHeader;
//================ Tracker boot counters. Used for Diagnostics only
typedef struct {
	unsigned char	on;
	unsigned char	res1;
	unsigned char	res2;
	unsigned char	res3;
	unsigned long	total;
	unsigned long	normal;
	unsigned long	critical;
	unsigned long	power;
} BOOTINFO;
//================ System default data align
#pragma pack()
//================
typedef	struct{
	unsigned long	ip;	// tracker ip address
	int	port;		// tracker port
	time_t	t;		// last received UDP packet timestamp
	int	dbid;		// tracker id from mysql database, if need
	char	imei[16];	// tracker IMEI, used for identification
	time_t	tvalid;		// last valid GPS data timestamp
	time_t	thist;		// last stored to file archive data timestamp, if need
	time_t	tlast;		// last GPS data timestamp
	// You can add any more fields if need
} DEV_INFO;
//================
typedef struct{
// server settings
char		serverIp[128];		// default is 127.0.0.1
unsigned long	serverPort;		// default is 2268
char		historyPath[256];	// default is /var/lib/gg

// file achive path strings
char		iddir[256];
char		fname[256];

// timestamp values
time_t		timesec;
time_t		timeold;

// socket vars
int		mainSock;
struct		sockaddr_in mainSa;	// Server IP address and port
struct		sockaddr_in devSa;	// Tracker's IP address and port
int		devSaLen;
fd_set		rd, wr;
char		addr_port[32];	// tracker "ip_address:port" string

// UDP recvfrom() and sendto() buffers
int		rxlen;
int		txlen;
unsigned char	rxbuf[2048];
unsigned char	txbuf[2048];

// our server determines the values below by ip:port of incoming UDP packet
int		id;	// tracker index in local DEV_INFO[] array
int		dbid;	// tracker ID in mysql database, if need
DEV_INFO	* pdi;	// current tracker DEV_INFO pointer

int		maxid;	// Max used index

//==== received data flags
int	flags;
//==== received data
int	sv;	// tracker software version
int	hv;	// tracker hardware version
char	own_number[64];	// tracker SIM own number
char	iccid[32];	// tracker SIM ICCID (serial number)
char	imei[32];	// tracker IMEI
BOOTINFO	bi;	// diagnostics info
unsigned long	crc;	// received crc


#ifdef	USE_MYSQL
char	myHost[128];
char	myLogin[128];
char	myPassword[128];
char	myDb[128];
MYSQL	mysql;
MYSQL_RES	*res;
MYSQL_ROW	row;
unsigned long	*lengths;
char	myQuery[1024];
#endif

DEV_INFO	di[MAX_DEV];	// all trackers device info array

} MEGA_GPS_VARS;

MEGA_GPS_VARS	gg;
//================ FIRMWARE BUFFERS FOR EACH HARDWARE VERSION
#define	MAX_SW	8

typedef struct{
	long	hw;		// hardware version
	long	sw;		// software version
	long	len;		// firmware update length
	unsigned long	crc;	// firmware update CRC32 value
	unsigned char	buf[32768];	// firmware update buffer
} SOFTWARE_DATA;

SOFTWARE_DATA	swData[MAX_SW];
int		swCount;
//================
