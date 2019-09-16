<?php

return [
    'name' => 'ethla',

    'version' => app('git.version'),

    'production' => false,

    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],

    'html_path'  => '/root/ethla/app/html',

    'orderbook_params' => [
            'column'       => 'size_price', // default column
            'sortL'        => SORT_DESC,
            'sortR'        => SORT_DESC,
            'headers'      => ['Buy', 'Price', '  ', 'Price', 'Sell'], // table heads
            'price_limits' => [50, 300],
            'min_size'     => 1,
            'limit'        => 50,
            'floating'     => 12,
            'filename'     => 'xbtusd',

//            'display'      => 'console',
            'display'      => 'html',

            'exchange'     => 'bitmex',

            'bitmex' => [
                'host' => 'http://localhost',
                'port' => 4444,

                'pt_orderbook'  => 'orderBookL2?symbol={symbol}&depth={depth}',
                'pt_instrument' => 'instrument?symbol={symbol}',
                'symbol'        => 'ETHUSD',
                'depth'         => 0,

                'api_url'            => 'http://localhost:4444/orderBookL2?symbol={symbol}&depth={depth}',
                'api_url_instrument' => 'http://localhost:4444/instrument?symbol=',
            ],

            'schema'       => 'discrete_levels', // 'quoter_average',
            'discrete_levels' => [
                'low'  => 10,
                'mid'  => 50,
                'high' => 100
            ],
    ],
];
