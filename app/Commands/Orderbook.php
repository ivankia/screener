<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Lloricode\LaravelHtmlTable\LaravelHtmlTableGenerator;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Orderbook extends Command
{
    /**
     * The signature of the command.
     * ./exilla orderbook:get --pf=8000 --pt=11500 --s=25 --l1=50 --l2=100 --l3=250 --floating=12
     *
     * @var string
     */
    protected $signature = 'orderbook:get {--symbol=ETHUSD} {--f=xbtusd.html} {--pf=50} {--pt=300} {--s=500} {--limit=50} {--schema=discrete_levels} {--l1=1000} {--l2=2000} {--l3=3000} {--floating=12} {--console=0}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get OrderBook';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var float
     */
    protected $lastPrice;

    /**
     * @var float
     */
    protected $prevLastPrice;

    protected $symbol = 'btc';

    protected $filename;

    protected $consoleOut = false;

    protected $instrument;

    /**
     * @var array
     */
    protected $notifications;

    protected $js;

    protected $styles;

    protected $processTime = 0;

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    /**
     * Orderbook constructor.
     */
    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Novosibirsk');
    }

    protected function init()
    {
        $this->loadParams();
        $this->processOptions();
        $this->loadInstrument($this->getParam('symbol'));
        $this->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

        if ($this->getParam('display') == 'console') {
            $this->setStyles();
        } else {
            $this->setStyles('html');
        }

        $this->getStyles();
        $this->getJS();
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $this->init();

        $looping = 100;
        $hng     = 1000000 / 5; // 0.12 milliseconds

        while ($looping) {
            $timeStart = microtime(true);

            $this->loadInstrument($this->getParam('symbol'));
            $this->observe();
            $looping--;

            $timeEnd = microtime(true);

            $this->processTime = round(($timeEnd - $timeStart) * $hng / 60000, 1);


            if (!$looping) {
                $this->handle();
            }

            usleep($hng);
        }
    }

    protected function processOptions()
    {
        if ($this->option('pf') && $this->option('pt')) {
            $this->setParam('price_limits', [$this->option('pf'), $this->option('pt')]);
        }

        if ($this->option('s')) {
            $this->setParam('min_size', $this->option('s'));
        }

        if ($this->option('f')) {
            $this->setParam('filename', $this->option('f'));
            $this->filename = $this->option('f');
        } else {
            $this->filename = $this->symbol;
        }

        if ($this->option('symbol')) {
            $this->setParam('symbol', $this->option('symbol'));
        }

        if ($this->option('limit')) {
            $this->setParam('limit', $this->option('limit'));
        }

        if ($this->option('schema')) {
            $this->setParam('schema', $this->option('schema'));
        }

        if ($this->option('floating')) {
            $this->setParam('floating', $this->option('floating'));
        }

        if ($this->option('console')) {
            $this->consoleOut = $this->option('console');
        }

        if ($this->option('l1') && $this->option('l2') && $this->option('l3')) {
            $this->setParam('discrete_levels', [
                'low'  => $this->option('l1'),
                'mid'  => $this->option('l2'),
                'high' => $this->option('l3'),
            ]);
        }
    }

    protected function observe()
    {
        $buys  = [];
        $sells = [];

        $data = json_decode(file_get_contents($this->getAPIUrl($this->getParam('symbol'))), JSON_OBJECT_AS_ARRAY, 2147483646);

        $this->prevLastPrice = $this->getLastPrice();

        $this->setLastPrice($this->getInstrument('lastPrice'));

        foreach ($data as $key => $val) {
            $order = [
                'price' => $val['price'],
            ];

            $amount = $val['size'];
            $size   = $amount / $order['price'];

            if ($order['price'] > $this->getParam('price_limits')[0]
                && $order['price'] < $this->getParam('price_limits')[1]
                && $size >= $this->getParam('min_size')
            ) {
                $order['size']  = round($val['size'] / $order['price'], 1);

                if ($val['side'] == 'Buy') {
                    $buys[] = $order;
                } else {
                    $sells[] = $order;
                }
            }
        }

//        if ($this->consoleOut) {
//            $this->pushNotification('<fg=white;bg=' . $this->getPriceBackgroundColor($this->prevLastPrice, $this->getLastPrice()) .'> $ ' . $this->getLastPrice() . ' </> [' . join('-', $this->getParam('price_limits'))  . '] | Lim ' . $this->getParam('min_size') . ' B/S S-P');
//        }
        $this->reportVr($buys, $sells);

        return true;
    }

    /**
     * @return array
     */
    protected function popNotification()
    {
        $notifications = $this->notifications;
        $this->notifications  = [];

        return $notifications;
    }

    /**
     * @param string $msg
     */
    protected function pushNotification($msg)
    {
        $this->notifications[] = $msg;
    }

    public function getPriceBackgroundColor($priceBefore, $priceNow)
    {
        $bgPrice = 'blue';

        if ($priceBefore < $priceNow) {
            $bgPrice = 'green';
        } elseif ($priceBefore > $priceNow) {
            $bgPrice = 'red';
        }

        return $bgPrice;
    }

    public function getPriceBackgroundColorHTML($priceBefore, $priceNow)
    {
        $bgPrice = 'table-primary';

        if ($priceBefore < $priceNow) {
            $bgPrice = 'table-success';
        } elseif ($priceBefore > $priceNow) {
            $bgPrice = 'table-danger';
        }

        return $bgPrice;
    }

    /**
     * @param float $price
     */
    protected function setLastPrice($price)
    {
        $this->lastPrice = $price;
    }

    /**
     * @return float
     */
    protected function getLastPrice()
    {
        return $this->lastPrice;
    }

    /**
     * @param string $key
     * @return array|float
     */
    protected function getInstrument($key = null)
    {
        return !$key ? $this->instrument : $this->instrument[$key];

    }

    protected function loadInstrument($symbol = 'ETHUSD')
    {
        $data = json_decode(file_get_contents($this->getParam('bitmex')['api_url_instrument'] . $symbol), JSON_OBJECT_AS_ARRAY, 2147483646);

//        $this->instrument = $data[0][0];
        $this->instrument = $data[0];
        $this->symbol = $symbol;
    }

    /**
     * @param array $orderbook
     * @param string $param
     * @param int $limit
     * @param int $sort
     * @param array $headers
     * @return void
     */
    protected function reportHr($orderbook, $param = 'size', $limit = 30, $sort = SORT_DESC, $headers = ['Price', 'Size'])
    {
        $limit--;

        if ($param == 'size' || $param == 'price') {
            array_multisort(array_column($orderbook, $param), $sort, $orderbook);
        }

        if ($param == 'size_price') {
            array_multisort(array_column($orderbook, 'size'), $sort, $orderbook);

            $orderbook = array_slice($orderbook, 0, $limit);

            usort($orderbook, function($a, $b) {
                return $b['price'] <=> $a['price'];
            });
        }

        if ($param == 'price_size') {
            array_multisort(array_column($orderbook, 'price'), $sort, $orderbook);

            $orderbook = array_slice($orderbook, 0, $limit);

            usort($orderbook, function($a, $b) {
                return $b['size'] <=> $a['size'];
            });
        }

        $this->table($headers, array_slice($orderbook, 0, $limit));
    }

    /**
     * @param array $orderbookL
     * @param array $orderbookR
     * @param array $params
     * @return void
     */
    protected function reportVr($orderbookL, $orderbookR, $params = [])
    {
        $params = array_merge($this->getParams(), $params);    

        $params['limit']--;

        if ($params['column'] == 'size' || $params['column'] == 'price') {
            array_multisort(array_column($orderbookL, $params['column']), $params['sortL'], $orderbookL);
            array_multisort(array_column($orderbookR, $params['column']), $params['sortR'], $orderbookR);

            $orderbookL = array_slice($orderbookL, 0, $params['limit']);
            $orderbookR = array_slice($orderbookR, 0, $params['limit']);
        }

        if ($params['column'] == 'size_price') {
            array_multisort(array_column($orderbookL, 'size'), $params['sortL'], $orderbookL);

            $orderbookL = array_slice($orderbookL, 0, $params['limit']);

            usort($orderbookL, function($a, $b) {
                return $b['price'] <=> $a['price'];
            });

            array_multisort(array_column($orderbookR, 'size'), $params['sortR'], $orderbookR);

            $orderbookR = array_slice($orderbookR, 0, $params['limit']);

            usort($orderbookR, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });

            if ($this->getParam('schema') == 'quoter_average') {
                $analyseL = $this->analyse($orderbookL);
                $analyseR = $this->analyse($orderbookR);

                $orderbookL = $this->colorFillQuoterAverage($orderbookL, 'size', $analyseL);
                $orderbookR = $this->colorFillQuoterAverage($orderbookR, 'size', $analyseR);
            } elseif ($this->getParam('schema') == 'discrete_levels') {
                $orderbookLHTML = $orderbookL;
                $orderbookRHTML = $orderbookR;

                $orderbookL = $this->colorFillDiscreteLevels($orderbookL, 'size');
                $orderbookR = $this->colorFillDiscreteLevels($orderbookR, 'size');

                $orderbookLHTML = $this->colorFillDiscreteLevelsHTML($orderbookLHTML, 'size');
                $orderbookRHTML = $this->colorFillDiscreteLevelsHTML($orderbookRHTML, 'size');
            }
        }

        if ($params['column'] == 'price_size') {
            array_multisort(array_column($orderbookL, 'price'), $params['sortL'], $orderbookL);

            $orderbookL = array_slice($orderbookL, 0, $params['limit']);

            usort($orderbookL, function($a, $b) {
                return $b['size'] <=> $a['size'];
            });

            array_multisort(array_column($orderbookR, 'price'), $params['sortR'], $orderbookR);

            $orderbookR = array_slice($orderbookR, 0, $params['limit']);

            usort($orderbookR, function($a, $b) {
                return $a['size'] <=> $b['size'];
            });

            if ($this->getParam('schema') == 'quoter_average') {
                $orderbookL = $this->colorFillQuoterAverage($orderbookL, 'price', $this->analyse($orderbookL));
                $orderbookR = $this->colorFillQuoterAverage($orderbookR, 'price', $this->analyse($orderbookR));
            } elseif ($this->getParam('schema') == 'discrete_levels') {
                $orderbookL = $this->colorFillDiscreteLevels($orderbookL, 'price');
                $orderbookR = $this->colorFillDiscreteLevels($orderbookR, 'price');
            }
        }


        $orderbook      = $this->mergeBuySellOrderbook($orderbookL, $orderbookR);
        $orderbookHTML  = $this->mergeBuySellOrderbookHTML($orderbookLHTML, $orderbookRHTML);

        if ($this->filename) {
            $file = '/var/www/html/' . strtolower($this->filename) . '.html';
        } else {
            $file = '/var/www/html/' . strtolower($this->symbol) . '.html';
        }

        if (!file_exists($file)) {
            system('touch ' . $file);
            system('chmod +rw ' . $file);
        }

        file_put_contents($file,
            $this->tableHTMLPage(
                [
                    'Buy',
                    'Price',
                    '<span id="currentPrice" class="p-1 px-2 '
                        . $this->getPriceBackgroundColorHTML($this->prevLastPrice, $this->getLastPrice()) . '">'
                        . $this->getLastPrice() .
                    '</span>',
                    'Price',
                    'Buy'
                ],
                $orderbookHTML
            )
        );

	if ($this->consoleOut) {
	    system('clear');
    
            $notifications = $this->popNotification();

            if (count($notifications)) {
                foreach ($notifications as $notification) {
                    $this->getOutput()->section($notification);
    	    }
	    }
    
            $this->table($this->getParam('headers'), $orderbook);
	}
    }

    /**
     * @param array $headers
     * @param array $data
     * @param integer $refresh
     *
     * @return string
     */
    public function tableHTMLPage($headers, $data, $refresh = 180)
    {
        $out = '
        <!doctype html><html lang="en">
            <head>
                <meta http-equiv="refresh" content="' . $refresh . ';url=http://dev.huemae.ru/' . strtolower($this->filename) . '.html" />
                <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                
                <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
                
                <title>' . $this->getLastPrice() . '</title>
                
                <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
                <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
                
                <script type="text/javascript">
                    ' . $this->js . '
                </script>
                
                <style>' . $this->styles . '</style>
            </head>
            <body>
                <div class="spinner-border text-secondary" id="spinner" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="container">
                  <div class="row">
                    <div class="col-12" id="mainFrame">                    
        ';

        $out .= $this->genTableHtml($headers, $data, true, $this->filename);
        $out .= '
                    </div>
              </div>
            </div>            
        ';
//        $out .= '
//                    </div>
//                <div class="col-4">' . $this->getLegend($this->getParam('discrete_levels')) . '</div>
//              </div>
//            </div>            
//        ';

        return $out . '
            </body></html>
        ';
    }

    public function genTableHtml($headers, $data, $cache = true, $cache_path = null) {
        $attributes = [
            'table'         => '<table class="table table-sm .table-striped">',
            'table_end'     => '</table>',
            'head'          => '<thead class="thead-light">',
            'head_end'      => '</thead>',
            'head_row'      => '<tr class="align-middle">',
            'head_row_end'  => '</tr>',
            'head_cell'     => '<th scope="col">',
            'head_cell_end' => '</th>',
            'body'          => '<tbody>',
            'body_end'      => '</tbody>',
            'body_row'      => '<tr>',
            'body_row_end'  => '</tr>',
            'body_cell'     => '<td>',
            'body_cell_end' => '</td>',
            'alt_body_row'      => '<tr>',
            'alt_body_row_end'  => '</tr>',
            'alt_body_cell'     => '<td>',
            'alt_body_cell_end' => '</td>'
        ];

        $htmlTable = new LaravelHtmlTableGenerator();
        $table = $htmlTable->generate($headers, $data, $attributes);

        if ($cache) {
            file_put_contents('/var/www/html/' . strtolower($this->filename) . '_cache.html', $table);
        }

        return $table;
    }

    /**
     * @param array $b
     * @param array $s
     * @return array
     */
    protected function mergeBuySellOrderbook($b, $s)
    {
        $bP = array_column($b, 'price');
        $sP = array_column($s, 'price');
        $bS = array_column($b, 'size');
        $sS = array_column($s, 'size');

        $orderbook = array_keys($bP);

        foreach ($orderbook as $key => $val) {
            $orderbook[$key] = [
                'BUY_SIZE' => isset($bS[$key]) ? $bS[$key] : 0,
                'BUY_PRICE' => isset($bP[$key]) ? $bP[$key] : 0,
                'DELIMITER' => '  ',
                'SELL_PRICE' => isset($sP[$key]) ? $sP[$key] : 0,
                'SELL_SIZE' => isset($sS[$key]) ? $sS[$key] : 0,
            ];
        }

        return $orderbook;
    }

    /**
     * @param array $b
     * @param array $s
     * @return array
     */
    protected function mergeBuySellOrderbookHTML($b, $s)
    {
        $bP = array_column($b, 'price');
        $sP = array_column($s, 'price');
        $bS = array_column($b, 'size');
        $sS = array_column($s, 'size');

        $orderbook = array_keys($bP);

        foreach ($orderbook as $key => $val) {
            $orderbook[$key] = [
                'b_size'  => isset($bS[$key]) ? $bS[$key] : 0,
                'b_price' => isset($bP[$key]) ? $bP[$key] : 0,
                'empty'   => '',
                's_price' => isset($sP[$key]) ? $sP[$key] : 0,
                's_size'  => isset($sS[$key]) ? $sS[$key] : 0,
            ];
        }

        return $orderbook;
    }

    /**
     * @param string $elem
     * @return string
     */
    protected function toHTML($elem)
    {
        switch ($elem) {
            case preg_match('/(100)/', $elem):
                $elem = strip_tags($elem);
                $elem = $this->h1($elem);
                break;
            case preg_match('/(200)/', $elem):
                $elem = strip_tags($elem);
                $elem = $this->h2($elem);
                break;
            case preg_match('/(300)/', $elem):
                $elem = strip_tags($elem);
                $elem = $this->h3($elem);
                break;
            case preg_match('/(101)/', $elem):
                $elem = strip_tags($elem);
                $elem = $this->h11($elem);
                break;
            case preg_match('/(201)/', $elem):
                $elem = strip_tags($elem);
                $elem = $this->h22($elem);
                break;
            case preg_match('/(301)/', $elem):
                $elem = strip_tags($elem);
                $elem = $this->h33($elem);
                break;
        }

        return $elem;
    }

    /**
     * @param array $orderbook
     * @return array
     */
    protected function analyse($orderbook)
    {
        $sizes  = array_column($orderbook, 'size');
        $prices = array_column($orderbook, 'price');

        $maxminS = ['max' => max($sizes), 'min' => min($sizes)];
        $maxminP = ['max' => max($prices), 'min' => min($prices)];

        $cnt    = count($sizes);
        $quater = $cnt / 4;

        $q1 = $quater - 1;
        $q2 = $cnt - $q1;

        $avgS = array_sum($sizes) / count($sizes);
        $avgP = array_sum($prices) / count($prices);

        sort($sizes, SORT_DESC);
        sort($prices, SORT_DESC);

        $q1AvgS = array_sum(array_slice($sizes, 0, $q1)) / $quater;
        $q2AvgS = array_sum(array_slice($sizes,  $q2, $cnt)) / $quater;
        $q1AvgP = array_sum(array_slice($prices, 0, $q1)) / $quater;
        $q2AvgP = array_sum(array_slice($prices,  $q2, $cnt)) / $quater;

        return [
            'size'   => $maxminS,
            'price'  => $maxminP,
            'avgp'   => $avgP,
            'avgs'   => $avgS,
            'q1avgs' => $q1AvgS,
            'q1avgp' => $q1AvgP,
            'q2avgs' => $q2AvgS,
            'q2avgp' => $q2AvgP,
        ];
    }

    /**
     * @param array $orderbook
     * @param string $param
     * @param array $deps
     * @return array
     */
    protected function colorFillQuoterAverage($orderbook, $param, $deps)
    {
        foreach ($orderbook as $key => $val) {
            if ($param == 'size' && $val['size'] >= $deps['q2avgs']) {
                $orderbook[$key]['size']  = $this->h1($val['size']);
                $orderbook[$key]['price'] = $this->h2($val['price']);
            }

            if ($param == 'price' && $val['price'] >= $deps['q2avgp']) {
                $orderbook[$key]['price'] = $this->h1($val['price']);
                $orderbook[$key]['size']  = $this->h2($val['size']);
            }
        }

        return $orderbook;
    }
    /**
     * @param array $orderbook
     * @param string $param
     * @param array $deps
     * @return array
     */
    protected function colorFillQuoterAverageHTML($orderbook, $param, $deps)
    {
        foreach ($orderbook as $key => $val) {
            if ($param == 'size' && $val['size'] >= $deps['q2avgs']) {
                $orderbook[$key]['size']  = $this->high($val['size']);
                $orderbook[$key]['price'] = $this->high($val['price']);
            }

            if ($param == 'price' && $val['price'] >= $deps['q2avgp']) {
                $orderbook[$key]['price'] = $this->high($val['price']);
                $orderbook[$key]['size']  = $this->high($val['size']);
            }
        }

        return $orderbook;
    }

    protected function colorFillDiscreteLevels($orderbook, $param)
    {
        $levels = $this->getParam('discrete_levels');

        foreach ($orderbook as $key => $val) {
            foreach ($levels as $levelCode => $level) {
                $size = number_format(round($val['size']), 0, '.', ' ');
                $price = number_format($val['price'], $this->getParam('floating'), '.', ' ');

                if ($param == 'size' && $val['size'] >= $level) {
                    if ($levelCode == 'low') {
                        $orderbook[$key]['size'] = $this->h100($size);
                        $orderbook[$key]['price'] = $this->h101($price);
                    } elseif ($levelCode == 'mid') {
                        $orderbook[$key]['size'] = $this->h200($size);
                        $orderbook[$key]['price'] = $this->h201($price);
                    } elseif ($levelCode == 'high') {
                        $orderbook[$key]['size'] = $this->h300($size);
                        $orderbook[$key]['price'] = $this->h301($price);
                    } else {
                        $orderbook[$key]['size']  = $size;
                        $orderbook[$key]['price']  = $price;
                    }
                }

                if ($param == 'price' && $val['price'] >= $level) {
                    if ($levelCode == 'low') {
                        $orderbook[$key]['price'] = $this->h100($price);
                        $orderbook[$key]['size'] = $this->h101($size);
                    } elseif ($levelCode == 'mid') {
                        $orderbook[$key]['price'] = $this->h200($price);
                        $orderbook[$key]['size'] = $this->h201($size);
                    } elseif ($levelCode == 'high') {
                        $orderbook[$key]['price'] = $this->h300($price);
                        $orderbook[$key]['size'] = $this->h301($size);
                    } else {
                        $orderbook[$key]['size']  = $size;
                        $orderbook[$key]['price']  = $price;
                    }
                }
            }
        }

        return $orderbook;
    }

    protected function colorFillDiscreteLevelsHTML($orderbook, $param)
    {
        $levels = $this->getParam('discrete_levels');

        foreach ($orderbook as $key => $val) {
            foreach ($levels as $levelCode => $level) {
                if ($param == 'size' && $val['size'] >= $level) {
                    if ($levelCode == 'high') {
                        $orderbook[$key]['size'] = $this->high($val['size']);
                        $orderbook[$key]['price'] = $this->high($val['price']);
                    } elseif ($levelCode == 'mid') {
                        $orderbook[$key]['size'] = $this->norm($val['size']);
                        $orderbook[$key]['price'] = $this->norm($val['price']);
                    } elseif ($levelCode == 'low') {
                        $orderbook[$key]['size'] = $this->low($val['size']);
                        $orderbook[$key]['price'] = $this->low($val['price']);
                    }
                }

                if ($param == 'price' && $val['price'] >= $level) {
                    if ($levelCode == 'high') {
                        $orderbook[$key]['price'] = $this->high($val['price']);
                        $orderbook[$key]['size'] = $this->high($val['size']);
                    } elseif ($levelCode == 'mid') {
                        $orderbook[$key]['price'] = $this->norm($val['price']);
                        $orderbook[$key]['size'] = $this->norm($val['size']);
                    } elseif ($levelCode == 'low') {
                        $orderbook[$key]['price'] = $this->low($val['price']);
                        $orderbook[$key]['size'] = $this->low($val['size']);
                    }
                }
            }
        }

        return $orderbook;
    }

    /**
     * @return array $params
     */
    protected function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function getParam($name)
    {
        return $this->params[$name];
    }

    /**
     * @param array $params
     */
    protected function setParams($params)
    {
        $this->params = $params;
    }

    protected function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    protected function loadParams()
    {
        $this->setParams(config('app.orderbook_params'));
    }

    protected function getAPIUrl($symbol = 'ETHUSD') {
        $exchange = $this->getParam('bitmex');

        return str_replace(['{symbol}', '{depth}'], [$symbol, $exchange['depth']], $exchange['api_url']);
    }

    /**
     * @param string $type
     * @return void
     */
    protected function setStyles($type = 'console')
    {
        $this->getOutput()->getFormatter()->setStyle(
            'h1',
            new OutputFormatterStyle('red', 'white', ['bold'])
        );
        $this->getOutput()->getFormatter()->setStyle(
            'h2',
            new OutputFormatterStyle('red', 'white')
        );

        // low
        $this->getOutput()->getFormatter()->setStyle(
            'h100',
            new OutputFormatterStyle('default', 'default', ['bold'])
        );
        $this->getOutput()->getFormatter()->setStyle(
            'h101',
            new OutputFormatterStyle('default', 'default', ['bold'])
        );
        //mid
        $this->getOutput()->getFormatter()->setStyle(
            'h200',
            new OutputFormatterStyle('red', 'default', ['bold'])
        );
        $this->getOutput()->getFormatter()->setStyle(
            'h201',
            new OutputFormatterStyle('red', 'default')
        );
        // high
        $this->getOutput()->getFormatter()->setStyle(
            'h300',
            new OutputFormatterStyle('white', 'red', ['bold'])
        );
        $this->getOutput()->getFormatter()->setStyle(
            'h301',
            new OutputFormatterStyle('white', 'red')
        );
    }

    /**
     * @param string $string
     * @return string
     */
    protected function high($string)
    {
        return '<span class="align-middle bg-danger px-1">' . $string . '</span>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function norm($string)
    {
        return '<span class="align-middle bg-warning px-1">' . $string . '</span>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function low($string)
    {
        return '<span class="align-middle bg-secondary px-1">' . $string . '</span>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h1($string)
    {
        if ($this->getParam('display') == 'html') {
            return '<span class="bg-danger">' . $string . '</span>';
        }

        return '<h1>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h2($string)
    {
        if ($this->getParam('display') == 'html') {
            return '<span class="bg-warning">' . $string . '</span>';
        }

        return '<h2>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h3($string)
    {
        if ($this->getParam('display') == 'html') {
            return '<span class="bg-secondary">' . $string . '</span>';
        }

        return '<h3>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h11($string)
    {
        if ($this->getParam('display') == 'html') {
            return '<span class="text-danger px-2">' . $string . '</span>';
        }

        return '<h1>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h22($string)
    {
        if ($this->getParam('display') == 'html') {
            return '<span class="text-warning px-2">' . $string . '</span>';
        }

        return '<h2>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h33($string)
    {
        if ($this->getParam('display') == 'html') {
            return '<span class="text-secondary px-2">' . $string . '</span>';
        }

        return '<h3>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h100($string)
    {
        return '<h100>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h101($string)
    {
        return '<h101>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h200($string)
    {
        return '<h200>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h201($string)
    {
        return '<h201>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h300($string)
    {
        return '<h300>' . $string . '</>';
    }

    /**
     * @param string $string
     * @return string
     */
    protected function h301($string)
    {
        return '<h301>' . $string . '</>';
    }

    public function getLegend($levels) {
        $instrument = [];

        foreach ($this->getInstrument() as $key => $val) {
            if (!empty($val)) {
                $instrument[] = '<b>' . $key . '</b>: ' . $val;
            }
        }

        $instrument = join('<br />', $instrument);

        $html = file_get_contents(config('app.html_path') . '/orderbook.html');

        $search = [
            '{{LAST_PRICE}}',
            '{{PROCESS_TIME}}',
            '{{LEVEL_LOW}}',
            '{{LEVEL_MID}}',
            '{{LEVEL_HIGH}}',
            '{{PRICE_RANGE}}',
            '{{INSTRUMENT}}'
        ];

        $replace = [
            $this->getLastPrice(),
            $this->processTime . ' sec at ' . date('H:i:s'),
            $levels['low'],
            $levels['mid'],
            $levels['high'],
            join(' - ', $this->getParam('price_limits')),
            $instrument
        ];

        $out = str_replace($search, $replace, $html);

        return $out;
    }

    public function getStats() {
        $instrument = [];

        foreach ($this->getInstrument() as $key => $val) {
            if (!empty($val)) {
                $instrument[] = '<b>' . $key . '</b>: ' . $val;
            }
        }

        return '
            <div class="stats">
                <div class="alert alert-dark" role="alert">
                    <span>' . $this->getCurrencyCode($this->getInstrument('quoteCurrency')) . '&nbsp; </span>
                    <span id="statsCurrentPrice" class="'
                        . $this->getPriceBackgroundColorHTML($this->prevLastPrice, $this->getLastPrice()) . '">'
                        . $this->getLastPrice() .  '
                    </span>
                </div>
            </div>
        ';
//        return '
//            <div class="stats">
//                <div class="alert alert-dark" role="alert">
//                    <span>' . $this->getCurrencyCode($this->getInstrument('quoteCurrency')) . '&nbsp; </span>
//                    <span id="statsCurrentPrice" class="'
//                        . $this->getPriceBackgroundColorHTML($this->prevLastPrice, $this->getLastPrice()) . '">'
//                        . $this->getLastPrice() .  '
//                    </span>
//                </div>
//
//                <div class="card">
//                    <div class="card-body">
//                        <h6>Instrument info</h6>
//                        ' . join('<br />', $instrument) . '
//                    </div>
//                </div>
//            </div>
//        ';
    }

    public function getCurrencyCode($currency) {
        switch ($currency) {
            case 'USD': return '$';
                break;
            case 'XBT': return 'B';
                break;
        }
    }

    public function getStyles() {
        return $this->styles = file_get_contents(dirname(__FILE__) . '/../styles/orderbook.css');
    }

    public function getJS()
    {
        $out = file_get_contents(dirname(__FILE__) . '/../js/orderbook.js');
        $out = str_replace('{{file_cache}}', strtolower($this->filename) . '_cache', $out);

        return $this->js = $out;
    }
}
