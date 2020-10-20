/*
 * Copyright 2016 CSIRO
 *
 * This file is part of Mastik.
 *
 * Mastik is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Mastik is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Mastik.  If not, see <http://www.gnu.org/licenses/>.
 */

#include <stdio.h>
#include <stdint.h>
#include <stdlib.h>

#include <util.h>
#include <l1.h>

#define MAX_SAMPLES 100000

void usage(const char *prog) {
  fprintf(stderr, "Usage: %s <samples>\n", prog);
  exit(1);
}

int main(int ac, char **av) {
  int samples = 0;

  if (av[1] == NULL)
    usage(av[0]);
  samples = atoi(av[1]);
  if (samples < 0)
    usage(av[0]);
  if (samples > MAX_SAMPLES)
    samples = MAX_SAMPLES;
  l1pp_t l1 = l1_prepare();

  int nsets = l1_getmonitoredset(l1, NULL, 0);

  int *map = calloc(nsets, sizeof(int));
  l1_getmonitoredset(l1, map, nsets);//map的每一项的值等于l1.monitor[i]的值，这里l1.monitor[i]的值经过随机化，已经和索引不相等了。

  int rmap[L1_SETS];
  for (int i = 0; i < L1_SETS; i++)
    rmap[i] = -1;
  for (int i = 0; i < nsets; i++){
    rmap[map[i]] = i;
    //printf("map : %d is %d \n",i ,map[i]);
    //printf("rmap: %d  is  %d\n",i,rmap[map[i]]);
  }

  uint16_t *res = calloc(samples * nsets, sizeof(uint16_t));//samples从输入中来，或者最大为10万，分配一个结果数组
  for (int i = 0; i < samples * nsets; i+= 4096/sizeof(uint16_t))//以页为单位递增
    res[i] = 1;
  
  delayloop(3000000000U);
  l1_repeatedprobe(l1, samples, res, 0);



  for (int i = 0; i < samples; i++) {
    for (int j = 0; j < L1_SETS; j++) {
      if (rmap[j] == -1)
	      printf(" 0 ");
      else
      	printf("%d ", res[i*nsets + rmap[j]]);//输出：对于每个样本，输出每个set的bprobe的结果,
        //printf(" ");
    }
    putchar('\n');
  }

  free(map);
  free(res);
  l1_release(l1);
}
