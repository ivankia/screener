#!/usr/bin/env bash
php /root/ethla/ethla orderbook:get --symbol=ADAZ19 \
    --f=adaz19 \
    --pf=0.0000001 \
    --pt=1 \
    --s=5 --limit=50 \
    --l1=300000000000 --l2=400000000000 --l3=600000000000 \
    --schema=discrete_levels \
    --floating=5