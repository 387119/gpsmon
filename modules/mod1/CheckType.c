#include "kiev.h"

unsigned int getpktsz(unsigned int pktnbr)
{
	switch(pktnbr)
	{
		case 1:
			return 17;
			break;
		case 19:
			return 19;
			break;
		case 2:
			return 22;
			break;
		case 22:
			return 24;
			break;
		case 3:
			return 14;
			break;
		case 4:
			return 12;
			break;
		case 5:
			return 10;
			break;
		case 6:
			return 12;
			break;
		case 7:
			return 40;
			break;
		case 8:
			return 40;
			break;
		case 9:
			return 14;
			break;
		case 10:
			return 20;
			break;
		case 11:
			return 42;
			break;
		case 12:
			return 12;
			break;
		case 13:
			return 12;
			break;
		case 14:
			return 9;
			break;
		case 15:
			return 9;
			break;
		case 16:
			return 3;
			break;
		case 17:
			return 6;
			break;
		case 18:
			return 9;
			break;
		case 20:
			return 10;
			break;
		case 21:
			return 317;
			break;
		case 23:
			return 246;
			break;
		case 24:
			return 12;
			break;
		case 25:
			return 248;
			break;
		default:
			return 0;
	}
}

int recvTimeout(int nSocket,char *buffer){
	int iRC = 0;
	int byte = 0;
	struct timeval ReceiveTimeout;
	fd_set fds;
	FD_ZERO(&fds);
	FD_SET(nSocket,&fds);
	ReceiveTimeout.tv_sec = 5;
	iRC = select(100,&fds,NULL,NULL,&ReceiveTimeout);
	if(!iRC)return -3;
	if(iRC<0)return -2;
	byte = recv(nSocket,buffer,2048,0);
	return byte;
}


void *CheckType(void *arg){
	char	session=0;
	int i,y=0;
	char	txbuf[5]="\nC01\n";
	unsigned char imei[15];
	int	byte_read;
	unsigned short	pos;
	char	rxbuf[2048];
	PGresult	*res;
	
	
	//char	query22[4096]	= "INSERT INTO data.gps_from_trackers (imei,tstamp,lat,lon,speed,dut,azimut,gsmsignal,gpsdop,signal_restart,volt) values";
	char	query22[4096]	= "INSERT INTO test_tty (imei,tstamp,lat,lon,speed,direction) values";
	char	query12[1024];
	char	query18[512]	= "INSERT INTO event_tty (imei,tstamp,event) values";
	unsigned short	lenq22=strlen(query22);
	unsigned short	lenq18=strlen(query18);
	thread_par	par = *(thread_par *)arg;
	while(par.sock){
		//printf("Begin\n");
		pos=0;
		memset(&rxbuf,'\0',2048);
		if(session==1)byte_read = recv(par.sock,rxbuf,2048,0);
		else if(session==0) byte_read = recvTimeout(par.sock,rxbuf);
		
		if(byte_read == -3){
			close(par.sock);
			par.sock='\0';
		}
		else if(byte_read == -2){
			close(par.sock);
			par.sock='\0';
		}
		else if(byte_read == -1){
			close(par.sock);
			par.sock='\0';
		}
		else if(byte_read == 0){
			close(par.sock);
			par.sock='\0';
		}
		else if(byte_read >0){
			memset(&query18[lenq18],'\0',512-lenq18);
			memset(&query22[lenq22],'\0',4096-lenq22);
			while(pos<byte_read){
				switch(rxbuf[pos]){
					case 1:
						printf("%d - %d\n",rxbuf[pos],pos);
						printf("%s\n",rxbuf);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 2:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 3:
						for(i=0;i<getpktsz(rxbuf[pos]);i++)printf("%d ",rxbuf[pos+i]);
						printf("\n");
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 4:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 5:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 6:
						for(i=0;i<getpktsz(rxbuf[pos]);i++)printf("%d ",rxbuf[pos+i]);
						printf("\n");
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 7:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 8:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 9:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 10:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 11:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 12:
						type12(rxbuf,imei,&pos,query12);
						break;
					case 13:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 14:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 15:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 16:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 17:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 18:	
						type18(rxbuf,imei,&pos,query18);
						break;
					case 19:
						init(rxbuf,par.sock,imei,par.conn,&session);
						pos+=19;
						break;
					case 20:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 21:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 22:
						type22(rxbuf,imei,&pos,query22);
						break;
					case 23:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					case 24:
						type24(rxbuf,imei,&pos,query22);
						break;
					case 25:
						printf("%d\n",rxbuf[pos]);
						pos+=getpktsz(rxbuf[pos]);
						break;
					default:
						printf("Default = %d\n",rxbuf[pos]);
						pos=byte_read;
						//close(acceptor);
						//acceptor='\0';
						break;
				}
			}
		if(strlen(query22)>lenq22){
			//printf("%s\n",query22);
			query22[strlen(query22)-1]='\0';
			
			strcat(query22,";");
			//printf("%s\n",query22);
			res = PQexec(par.conn,query22);
			if(PQresultStatus(res)==PGRES_COMMAND_OK){
				printf("Query22: Данные успешно записаны в БД\n");
			}
			else{
				printf("Query22: Ошибка записи данных в БД\n");
			}
			
		}
		
		if(strlen(query18)>lenq18){
			//printf("%s\n",query18);
			query18[strlen(query18)-1]='\0';
			strcat(query18,";");
			//printf("%s\n",query18);
			res = PQexec(par.conn,query18);
			if(PQresultStatus(res)==PGRES_COMMAND_OK){
				printf("Query18: Данные успешно записаны в БД\n");
			}
			else{
				printf("Query18: Ошибка записи данных в БД\n");
			}
			
		}
		
		//printf("Потверждаем прием пакета\n");
		//if(rxbuf[0]!=19)send(par.sock,txbuf,5,0);
		
		}
	}
	
	printf("Exit\n");
}
