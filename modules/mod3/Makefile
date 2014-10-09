all: main.o gpsmon.o  socket.o 
	gcc main.o gpsmon.o socket.o -o AndroidLogger -lpq

main.o: main.c
	gcc -c main.c

gpsmon.o: gpsmon.c
	gcc -c gpsmon.c

socket.o: socket.c
	gcc -c socket.c

clean:
	rm -f ./*.o
	rm -f ./AndroidLogger
