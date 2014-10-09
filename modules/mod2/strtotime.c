#include <stdio.h>
unsigned int strtotime(char *time){
	unsigned int	tstamp=0;
	int year,month,day,hour,min,sec;
	sscanf(time,"%d-%d-%d %d:%d:%d",&year,&month,&day,&hour,&min,&sec);
	int x;
	for(x=1970;x<year;x++){
		if(x%4==0)tstamp+=366*3600*24;
		else tstamp+=365*3600*24;
	}
	
	for(x=1;x<month;x++){
		if(x==1||x==3||x==5||x==7||x==8||x==10||x==12){
			tstamp+=31*86400;
		}
		
		else if(x==4||x==6||x==9||x==11){
			tstamp+=30*86400;
		}
		
		else if(x==2){
			if((year)%4==0)tstamp+=29*3600;
			else tstamp+=28*86400;
		}
	}
	tstamp+=(day-1)*86400+hour*3600+min*60+sec;

return tstamp;
}
