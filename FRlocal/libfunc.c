#include "stdio.h"
#include "libfunc.h"
void myADD(int a,int b){
	int c;
	c=a+b;
	a=a+b;
	a=a+b;
	for(int i=0;i<800;i++){
		c++;
		a++;
	}
}
void noc(){
        int a=1;
        a=a+9;
        a=a+1;
        a=a+2;
        a=a+3;
        a=a+4;
        a=a+5;
		int c=0;
		c=c+a;
		c=c*a*a*a;
        }
void myMUL(int a,int b){
	int c;
	c=a*b;
	b=a+c;
	a=a*b;
	for(int i=0;i<800;i++){
		c++;
		a++;
	}
	c=a+b;
}
void test(){
	int a=1;
	int b=2;
	int k[30]={0,1,1,0,0,
				1,0,1,0,1,
				1,0,0,0,1,
				1,1,0,1,0,
				0,0,0,0,0,
				1,1,1,1,1};
	
	for(int i=0;i<30;i++){
		if(k[i]==0){
			myADD(a,b);
			a=a+0;
			a=a+0;
			}
		else {
			myMUL(a,b);
			a=a+0;
			a=a+0;
			}
	//	for (int j=0;j<1000;j++){
	//		int l=0;
	//	}
			
		}
}
