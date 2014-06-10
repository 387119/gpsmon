int r_st,c_st;


void get_raw_path(void){
char s[80];
char *p;
FILE* qwe;
int i,z=0;
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
char s1[16]="PATH_RAW_DATA=";
if(qwe){
	do{
		fgets(s,80,qwe);
		p=strstr(s,s1);
		}
	while(p==NULL);
		while(p[z]!='\0'){
		z++;
		}
	for(i=14;i<z;i++){
		gg.rawd[i-14]=p[i];
		if(p[i]=='\n'){
			gg.rawd[i-14]='\0';
			}
		}
	fclose(qwe);
	}
}

void get_copy_path(void){
FILE* qwe;
char s[80],*p,s2[17]="PATH_COPY_DATA=";
int i,z1=0;
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
if(qwe){
	do{
		fgets(s,80,qwe);
		p=strstr(s,s2);
		}
	while(p==NULL);
	while(p[z1]!='\0')z1++;
	for(i=15;i<z1;i++){
		gg.copyd[i-15]=p[i];
		if(p[i]=='\n')gg.copyd[i-15]='\0';
		}
	fclose(qwe);
	}
}

void get_data_path(void){
FILE* qwe;
char s3[11]="PATH_DATA=";
int i,z2=0;
char s[80],*p;
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");

if(qwe){
	do{
		fgets(s,80,qwe);
		p=strstr(s,s3);
	}
	while(p==NULL);

	while(p[z2]!='\0')z2++;
	for(i=10;i<z2;i++){
		gg.d[i-10]=p[i];
		if(p[i]=='\n')gg.d[i-10]='\0';
		}
	fclose(qwe);
	}
}

int m4(void){
FILE* qwe;
char s[20],*p,s4[5]="RAW=";
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
int z4=0;
if(qwe){
	do{
		fgets(s,20,qwe);
		p=strstr(s,s4);
		}
	while(p==NULL);
	while(p[z4]!='\0'){
		if(p[z4]=='\n'){p[z4]='\0';continue;}
		z4++;
		}
	fclose(qwe);
	}
if(z4==7)return cr.rt=1;
else if(z4==6)return cr.rt=0;
}

int m5(void){
FILE* qwe;
char s[20],*p,s5[6]="COPY=";
int z5=0;
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
if(qwe){
	do{
		fgets(s,10,qwe);
		p=strstr(s,s5);
		}
	while(p==NULL);
	while(p[z5]!='\0'){
		if(p[z5]=='\n'){p[z5]='\0';continue;}
		z5++;
		}
	fclose(qwe);
	}
if(z5==8)return cr.ct=1;
else if(z5==7)return cr.ct=0;
}

char get_debug_level(){
FILE* qwe;
int i;
char s[80],*p;
char s1[15]="DEBUG_LEVEL=";
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
if(qwe){
	do{
		fgets(s,80,qwe);
		p=strstr(s,s1);
		}
	while(p==NULL);
	for(i=0;i<80;i++){
		if(s[i]=='\n')s[i]='\0';
		}
	if(!strcmp(s,"DEBUG_LEVEL=1"))return 1;
	else if(!strcmp(s,"DEBUG_LEVEL=2"))return 2;
	else if(!strcmp(s,"DEBUG_LEVEL=3"))return 3;
	else if(!strcmp(s,"DEBUG_LEVEL=4"))return 4;
	else return 0;
	fclose(qwe);
	}
}

char get_type_save(){
FILE* qwe;
char s[80],*p;
int i;
char s1[15]="TYPE_SAVE_DATA=";
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
if(qwe){
	do{
		fgets(s,80,qwe);
		p=strstr(s,s1);
		}
	while(p==NULL);
	for(i=0;i<80;i++){
		if(s[i]=='\n')s[i]='\0';
		}
	if(!strcmp(s,"TYPE_SAVE_DATA=psql")){
		openlog("INFO",LOG_PID,LOG_LOCAL1);
		syslog(LOG_INFO,"Тип записи данных 'PSQL'");
		closelog();
		return 1;
	}
	else if(!strcmp(s,"TYPE_SAVE_DATA=file")){
		openlog("INFO",LOG_PID,LOG_LOCAL1);
		syslog(LOG_INFO,"Тип записи данных 'FILE'");
		closelog();
		return 2;
	}
	else {
		openlog("ERROR",LOG_PID,LOG_LOCAL1);
		syslog(LOG_ERR,"Неверный тип сохранения данных он может быть 'psql' или 'file' проверьте Mega-gps-355.conf");
		closelog();
		exit(1);
		return 0;}
	fclose(qwe);
	}
else if(qwe==NULL){
	openlog("ERROR",LOG_PID,LOG_LOCAL1);
	syslog(LOG_ERR,"Ошибка при чтении конфига");
	closelog();
	exit(1);
	}
}

extern void WriteRaw(char *status, char path[100], char buf[1000],int *size){
    if(*status==1){
	FILE* raw;
	if((raw=fopen(path,"a"))==NULL){
		openlog("WARNING",LOG_PID,LOG_LOCAL1);
		syslog(LOG_WARNING,"Невозможно открыть файл %s", path);
		closelog();
		}
	else {
		fwrite(buf,*size,1,raw);
		fclose(raw);
		}
	}
}

extern void WriteCopy(char *status, char path[100],char* format, ...){
    if(*status==1){
	FILE* copy;
	if((copy=fopen(path,"a"))==NULL){
		openlog("WARNING",LOG_PID,LOG_LOCAL1);
		syslog(LOG_WARNING,"Невозможно открыть файл %s",path);
		closelog();
		}
	else {
		va_list data;
		va_start(data,format);
		vfprintf(copy,format,data);
		va_end(data);
		fclose(copy);
		}
	}
}
