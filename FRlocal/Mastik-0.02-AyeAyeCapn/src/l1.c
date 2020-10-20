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

#include "config.h"
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <sys/mman.h>
#include <assert.h>
#include <strings.h>

#include "low.h"
#include "l1.h"

#define L1_ASSOCIATIVITY 8
#define L1_CACHELINE 64
#define L1_STRIDE (L1_CACHELINE * L1_SETS)


#define PTR(set, way, ptr) (void *)(((uintptr_t)l1->memory) + ((set) * L1_CACHELINE) + ((way) * L1_STRIDE) + ((ptr)*sizeof(void *)))
#define LNEXT(p) (*(void **)(p))





struct l1pp{
  void *memory;
  void *fwdlist;
  void *bkwlist;
  uint8_t monitored[L1_SETS];
  int nsets;
};


static void rebuild(l1pp_t l1) {
  if (l1->nsets == 0) {
    l1->fwdlist = l1->bkwlist = NULL;
    return;
  }
  for (int i = 0; i < l1->nsets - 1; i++) {
    LNEXT(PTR(l1->monitored[i], L1_ASSOCIATIVITY - 1, 0)) = PTR(l1->monitored[i+1], 0, 0);
    LNEXT(PTR(l1->monitored[i], 0, 1)) = PTR(l1->monitored[i+1], L1_ASSOCIATIVITY - 1, 1);
  }
  l1->fwdlist = LNEXT(PTR(l1->monitored[l1->nsets - 1], L1_ASSOCIATIVITY - 1, 0)) = PTR(l1->monitored[0], 0, 0);
  l1->bkwlist = LNEXT(PTR(l1->monitored[l1->nsets - 1], 0, 1)) = PTR(l1->monitored[0], L1_ASSOCIATIVITY - 1, 1);
}



static void probelist(void *p, int segments, int seglen, uint16_t *results) {//p：fwdlist，segments=64（64个set）,seglen=8(8路组相联)
  while (segments--) {//对于每一个set，测量一个访问时间，存在results里。
    uint32_t s = rdtscp();
    for (int i = seglen; i--; ) {//对于每一路
      asm volatile (""::"r" (p):);
      p = LNEXT(p);
    }
    uint32_t res = rdtscp() - s;
    *results = res > UINT16_MAX ? UINT16_MAX : res;
    results++;
  }
}

l1pp_t l1_prepare(void) {
  l1pp_t l1 = (l1pp_t)malloc(sizeof(struct l1pp));
  l1->memory = mmap(0, PAGE_SIZE * L1_ASSOCIATIVITY, PROT_READ|PROT_WRITE, MAP_PRIVATE|MAP_ANON, -1, 0);
  l1->fwdlist = NULL;
  l1->bkwlist = NULL;
  for (int set = 0; set < L1_SETS; set++) {
    for (int way = 0; way < L1_ASSOCIATIVITY - 1; way++) {
      LNEXT(PTR(set, way, 0)) = PTR(set, way+1, 0);
      LNEXT(PTR(set, way+1, 1)) = PTR(set, way, 1);
      /*printf("the value of l1.memory is \t");
      printf("%x",l1->memory);
      printf("\n");*/
    }
  }
  l1_monitorall(l1);//将所有可能的指针添加到描述符监视的指针集。仅支持L1攻击。
  return l1;
}

void l1_release(l1pp_t l1) {
  munmap(l1->memory, PAGE_SIZE * L1_ASSOCIATIVITY);
  bzero(l1, sizeof(struct l1pp));
  free(l1);
}


int l1_monitor(l1pp_t l1, int line) {
  for (int i = 0;  i < l1->nsets; i++) 
    if (l1->monitored[i] == line) 
      return 0;
  l1->monitored[l1->nsets++] = line;
  rebuild(l1);
  return 1;
}

int l1_unmonitor(l1pp_t l1, int line) {
  for (int i = 0;  i < l1->nsets; i++) 
    if (l1->monitored[i] == line) {
      l1->monitored[i] = l1->monitored[l1->nsets--];
      rebuild(l1);
      return 1;
    }
  return 0;
}

void l1_monitorall(l1pp_t l1) {
  for (int i = 0; i < L1_SETS; i++)
    l1->monitored[i] = i;
  l1->nsets = L1_SETS;
  l1_randomise(l1);//使用非安全的伪随机数生成器对受监视指针(set)进行重新排序。初始化L1攻击描述符会按随机顺序监视所有指针(缓存集)。初始化其他描述符以不监视任何指针。
}

void l1_unmonitorall(l1pp_t l1) {
  l1->nsets = 0;
  rebuild(l1);
}


int l1_getmonitoredset(l1pp_t l1, int *lines, int nlines) {
  if (lines != NULL) {
    if (nlines > l1->nsets)
      nlines = l1->nsets;
    for (int i = 0; i < nlines; i++)
      lines[i] = l1->monitored[i];
  }
  return l1->nsets;
}


int l1_nsets(l1pp_t l1) {
  return l1->nsets;
}


void l1_randomise(l1pp_t l1) {
  for (int i = 0; i < l1->nsets; i++) {
    int p = random() % (l1->nsets - i) + i;
    uint8_t t = l1->monitored[p];
    l1->monitored[p] = l1->monitored[i];
    l1->monitored[i] = t;
  }
  rebuild(l1);
}

void l1_probe(l1pp_t l1, uint16_t *results) {
  probelist(l1->fwdlist, l1->nsets, L1_ASSOCIATIVITY, results);
}

void l1_bprobe(l1pp_t l1, uint16_t *results) {
  probelist(l1->bkwlist, l1->nsets, L1_ASSOCIATIVITY, results);
}

/*通常，单次探测能提供的信息太少。函数 XX_repeatedprobe() 执行一系列的探测，如果环境支持XX_bprobe()，
该函数会交替使用 XX_probe() 和 XX_bprobe()，否则，只是用XX_probe() 。该函数的 slot 参数可管理探测行为，
使其在每 slot 个周期内执行一次探测。如果错过了一个 slot，则该 slot 中各点的计时结果将设置为0。
(对于probecount版本 ，其结果将设置为〜0)*/
int l1_repeatedprobe(l1pp_t l1, int nrecords, uint16_t *results, int slot) {
  assert(l1 != NULL);
  assert(results != NULL);

  if (nrecords == 0)
    return 0;

  int len = l1->nsets;

  for (int i = 0; i < nrecords; /* Increment inside */) {
    l1_probe(l1, results);
    results += len;
    i++;
    l1_bprobe(l1, results);
    results += len;
    i++;
  }
  return nrecords;
}

