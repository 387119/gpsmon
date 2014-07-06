all: main.o queclink.o socket.o functions.o gt200.o
	gcc main.o queclink.o socket.o functions.o gt200.o -o queclink -lpq
static: main.o queclink.o socket.o functions.o gt200.o
	gcc main.o queclink.o socket.o functions.o gt200.o -o queclink -lpq
install:
	cp ./queclink /usr/lib/gpsmod/queclink

main.o: main.c
	gcc -c main.c

socket.o: socket.c
	gcc -c socket.c

queclink.o: queclink.c
	gcc -c queclink.c

functions.o: functions.c
	gcc -c functions.c
	
gt200: gt200.c
	gcc -c gt200.c

clean:
	rm -f ./*.o
	rm -f ./queclink
