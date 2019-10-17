#!/usr/bin/env bash
php /screener/ethla orderbook:get --symbol=XBTUSD \
    --f=xbtusd \
    --pf=500 \
    --pt=20000 \
    --s=50 --limit=50 \
    --l1=250 --l2=350 --l3=500 \
    --schema=discrete_levels \
    --floating=2 &

php /screener/ethla orderbook:get --symbol=XBTUSD \
    --f=xbtusd_zoom \
    --pf=7000 \
    --pt=8900 \
    --s=25 --limit=20 \
    --l1=200 --l2=300 --l3=500 \
    --schema=discrete_levels \
    --floating=2 &

php /screener/ethla orderbook:get --symbol=ETHUSD \
    --f=ethusd \
    --pf=100 \
    --pt=300 \
    --s=5 --limit=50 \
    --l1=1000 --l2=1500 --l3=2500 \
    --schema=discrete_levels \
    --floating=2 &

php /screener/ethla orderbook:get --symbol=ETHUSD \
    --f=ethusd_zoom \
    --pf=140 \
    --pt=220 \
    --s=1 --limit=20 \
    --l1=1500 --l2=1750 --l3=2000 \
    --schema=discrete_levels \
    --floating=2 &

php /screener/ethla orderbook:get --symbol=XRPZ19 \
    --f=xrpbtc_zoom \
    --pf=0.00001000 \
    --pt=0.00010000 \
    --s=5 --limit=20 \
    --l1=10000000000 --l2=20000000000 --l3=35000000000 \
    --schema=discrete_levels \
    --floating=2 &

#php /screener/ethla orderbook:get --symbol=ADAZ19 \
#    --f=adabtc_zoom \
#    --pf=0.0000001 \
#    --pt=1 \
#    --s=5 --limit=30 \
#    --l1=300000000000 --l2=400000000000 --l3=600000000000 \
#    --schema=discrete_levels \
#    --floating=5 &

php /screener/ethla orderbook:get --symbol=TRXZ19 \
    --f=trxbtc_zoom \
    --pf=0.00001 \
    --pt=1 \
    --s=5 --limit=20 \
    --l1=300000000000 --l2=400000000000 --l3=600000000000 \
    --schema=discrete_levels \
    --floating=5 &

php /screener/ethla orderbook:get --symbol=BCHZ19 \
    --f=bchbtc_zoom \
    --pf=0.0001 \
    --pt=1 \
    --s=5 --limit=20 \
    --l1=300000000 --l2=400000000 --l3=600000000 \
    --schema=discrete_levels \
    --floating=2 &

php /screener/ethla orderbook:get --symbol=LTCZ19 \
    --f=ltcbtc_zoom \
    --pf=0.00000050 \
    --pt=1 \
    --s=5 --limit=20 \
    --l1=3000000 --l2=4000000 --l3=6000000 \
    --schema=discrete_levels \
    --floating=2 &
