#include "stdio.h"
#include "libfunc.h"
#include <stdlib.h>
#define N 30
typedef unsigned long int	uint64_t;
typedef unsigned int		uint32_t;
static inline uint64_t rdtscp64() {
  uint32_t low, high;
  asm volatile ("rdtscp": "=a" (low), "=d" (high) :: "ecx");
  return (((uint64_t)high) << 32) | low;
}
int main(){
	int round=0;	
	//int a=2,b=4;
	/*int k[N]={0,1,1,0,0,
				1,0,1,0,1,
				1,0,0,0,1,
				1,1,0,1,0,
				1,1,1,1,1,
			0,0,0,0,0};*/
//printf("bit value:/ compute value:\n");	
while(1){
	/*
	for(int i=0;i<N;i++){
		if(k[i]==0){
			myADD(a,b);
			a=a+0;
			a=a+0;
			}
		else if(k[i]==1){
			myMUL(a,b);
			a=a+0;
			a=a+0;
			}
		}
		*/
	uint64_t prev_time = rdtscp64();
	for(int i=0;i<1000;i++){
	test();
	}
	uint64_t forw_time = rdtscp64();
	uint64_t sub=forw_time-prev_time;
	printf("----------this is %d round-------- sub=%d\n",round,sub);
	round++;
	//sleep(1);
}

return 0;
	}		
