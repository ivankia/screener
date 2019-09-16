#!/usr/bin/env bash
./ethla orderbook:get \
  --symbol=ETHUSD \
  --pf=150 --pt=250 \
  --s=100 --limit=50 \
  --l1=1000 --l2=1500 --l3=3000 \
  --schema=discrete_levels