#include "queclink.h"



extern void GetHead(char *in, char *out){
	int i=0;
	while(in[i]!=','){
		out[i]=in[i];
		i++;
	}
	
}



extern int GetNumericHead(char *in){
	int out=0;
	
	if(!strcmp(in,"+ACK:GTHBD"))out=1;
	else if(!strcmp(in,"+RESP:GTFRI")||!strcmp(in,"+BUFF:GTFRI"))out=2;
	else if(!strcmp(in,"+RESP:GTPNL")||!strcmp(in,"+BUFF:GTPNL"))out=2;
	else if(!strcmp(in,"+RESP:GTSOS")||!strcmp(in,"+BUFF:GTSOS"))out=2;
	else if(!strcmp(in,"+RESP:GTINF")||!strcmp(in,"+BUFF:GTINF"))out=3;
	else if(!strcmp(in,"+RESP:GTPDP"))out=4;
	else if(!strcmp(in,"+RESP:GTSTT")||!strcmp(in,"+BUFF:GTSTT"))out=5;
	else if(!strcmp(in,"+RESP:GTBTC")||!strcmp(in,"+BUFF:GTBTC"))out=6;
	else if(!strcmp(in,"+RESP:GTEPN")||!strcmp(in,"+BUFF:GTEPN"))out=7;
	else if(!strcmp(in,"+RESP:GTEPF")||!strcmp(in,"+BUFF:GTEPF"))out=7;
	else if(!strcmp(in,"+RESP:GTSTC")||!strcmp(in,"+BUFF:GTSTC"))out=8;
	else if(!strcmp(in,"+RESP:GTDOG")||!strcmp(in,"+BUFF:GTDOG"))out=9;
	else if(!strcmp(in,"+RESP:GTLBC")||!strcmp(in,"+BUFF:GTLBC"))out=10;
	
	
	else if(!strcmp(in,"+RESP:GTPNA")||!strcmp(in,"+BUFF:GTPNA"))out=100;
	else if(!strcmp(in,"+RESP:GTPFA")||!strcmp(in,"+BUFF:GTPFA"))out=100;
	
return out;
}

