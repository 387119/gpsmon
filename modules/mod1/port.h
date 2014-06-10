getPort()
{
FILE* qwe;
char *p,*end;
char port[5];
char o[10];
int i;
char y[5]="PORT";
qwe=fopen("/etc/gpsmod/mega-gps/mega-gps-3.55/mega-gps.conf","r");
do{
	fgets(o,10,qwe);
	p=strstr(o,y);
	}
	
while(p==NULL);
memset(&port,'\0',5);
for(i=0;i<4;i++){
	port[i]=p[i+5];
	}
	
fclose(qwe);
return strtol(port,&end,10);
}