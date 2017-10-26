<?php
error_reporting(-1);
ini_set('display_errors', 'On');
ini_set('memory_limit', '256M');

require __DIR__  . '/vendor/autoload.php';

DB::$user = 'root';
DB::$password = 'root';
DB::$dbName = 'vape_parse';
DB::$encoding = 'utf8';

// 30, 60, 90, 100, 120 , 200
/**
 * @todo span no-buy
 */

$start = 1;
$debug = 0;

$siteUrl = 'https://xn--80aaxitdbjk.xn--p1ai';
$eJuices = [];
$json = '';
$eJuicePreset = ['name' => '', 'img' => '', 'price' => '', 'amounts' => ['nic' => [], 'ob' => []], 'rate' => []];

$arrContextOptions = [
	"ssl" => [
		"verify_peer"      => false,
		"verify_peer_name" => false,
	],
];

$i = $j = 0;

if ($start) {
	$instance = new simple_html_dom();
	
	// Все поставщики жиж
	$html = file_get_html($siteUrl . '/category/zhidkosti-dlya-elektronnykh-sigaret/', false, stream_context_create($arrContextOptions));
	$firms = $html->find('ul.submenu a');
	// Парсим каждого поставщика
	foreach ($firms as $firm) {
		
		$j = 0;
		$i++;
		if ($debug && $i > 15) break;
		$firmName = trim(str_replace(['Жидкости для электронных сигарет ', 'Жидкость для электронных сигарет '], '', $firm->title));
		
		// Один поставщик
		$categoryHtml = file_get_html($siteUrl . $firm->href, false, stream_context_create($arrContextOptions));
		$items = $categoryHtml->find('div.pr-title a');
		//Парсим каждую жижу
		foreach ($items as $itemUrl) {
			$j++;
			if ($debug && $j > 3) break;
			$itemHtml = file_get_html($siteUrl . $itemUrl->href, false, stream_context_create($arrContextOptions));
			$eJuice = $eJuicePreset;
			//Берем данные
			$name = $itemHtml->find('.card-desc span', 0);
			$excludeStrings = [
				$firmName,
				'жидкость',
				'Жидкость',
				' для электронных сигарет',
				' - ',
				'(',
				')',
				'"',
				'.'
			];
			
			if (count($name) > 0)
				$eJuice['name'] = trim(str_replace($excludeStrings, '', $name->innertext)); //Название
			else {
				$name = $itemHtml->find('.card-left h1.title', 0);
				if (count($name) > 0)
					$eJuice['name'] = trim(str_replace($excludeStrings, '', $name->innertext)); //Название
			}
			
			$img = $itemHtml->find('.product-card a.prd_image img', 0);
			if (count($img) > 0)
				$eJuice['img'] = substr($img->src, 2); //Картинка url
			$price = $itemHtml->find('.product-price-value', 0);
			if (count($price) > 0)
				$eJuice['price'] = $price->innertext; //Цена
			
			$optionsCount = $itemHtml->find('div.options .sku-feature');
			
			if (count($optionsCount) > 0) {
				if (count($optionsCount) == 1) {
					$amounts = $itemHtml->find('div.options .sku-feature option');
					if (count($amounts) > 0)
						foreach ($amounts as $amount)
							$eJuice['amounts']['ob'][] = str_replace('мл', '', $amount->innertext); //Объемы
				} elseif (count($optionsCount) == 2) {
					$nic = $itemHtml->find('div.options .sku-feature', 0)->children;
					if (count($nic) > 0)
						foreach ($nic as $n)
							$eJuice['amounts']['nic'][] = str_replace('мг', '', $n->innertext); //Никотин
					
					$ob = $itemHtml->find('div.options .sku-feature', 1)->children;
					if (count($ob) > 0)
						foreach ($ob as $o)
							$eJuice['amounts']['ob'][] = str_replace('мл', '', $o->innertext); //Объемы
				}
			}
			
			$rateCount = $itemHtml->find('div.rate-amount', 0);
			if (count($rateCount) > 0)
				$eJuice['rate']['count'] = str_replace(['(', ')'], '', trim($rateCount->innertext)); //Рейтинг кол-во участников
			$rateValue = $itemHtml->find('meta[itemprop=ratingValue]', 0);
			if (count($rateValue) > 0)
				$eJuice['rate']['value'] = $rateValue->content; //Рейтинг звезд
			
			$eJuices[$firmName][] = $eJuice;
			
			
			/*
					firmName   VARCHAR(50)       NULL,
					juiceName  VARCHAR(50)       NULL,
					price      FLOAT DEFAULT '0' NULL,
					image      VARCHAR(100)      NULL,
					sizes      VARCHAR(200)      NULL,
					nicotines  VARCHAR(200)      NULL,
					rate_value FLOAT DEFAULT '0' NULL,
					rate_count INT DEFAULT '0'   NULL,
			 */
			$insertData = [
				'firmName'   => mb_strtolower($firmName),
				'juiceName'  => mb_strtolower($eJuice['name']),
				'price'      => (float) $eJuice['price'],
				'image'      => $eJuice['img'],
				'url'        => $siteUrl . $itemUrl->href
			];
			
			if (isset($eJuice['amounts']['ob']) && $eJuice['amounts']['ob'])
				$insertData['sizes'] = json_encode($eJuice['amounts']['ob']);
			if (isset($eJuice['amounts']['nic']) && $eJuice['amounts']['nic'])
				$insertData['nicotines'] = json_encode($eJuice['amounts']['nic']);
			if (isset($eJuice['rate']['count']) && $eJuice['rate']['count'])
				$insertData['rate_count'] = $eJuice['rate']['count'];
			if (isset($eJuice['rate']['value']) && $eJuice['rate']['value'])
				$insertData['rate_value'] = (float) $eJuice['rate']['value'];
			
			DB::insert('papiroska', $insertData);
			
			echo 'processed: ' . $eJuice['name'] . "\n";
			
			//if ($debug) break;
		}
		
		echo '------ FIRM DONE: ' . $firmName . " ------- \n";
		
		//if ($debug) break;
	}
	
	$content = json_encode($eJuices, JSON_UNESCAPED_UNICODE);
	$fp = fopen(__DIR__  . "/papiroska_result.txt","wb");
	fwrite($fp, $content);
	fclose($fp);
}

$out = file_get_contents(__DIR__  . "/papiroska_result.txt","wb");
d(json_decode($out, true));

/*


// Одна жижка


$html = file_get_html('https://xn--80aaxitdbjk.xn--p1ai/product/anisimus-dr-vapers-elixir/');
  //Берем данные
$name = $html->find('.card-desc span', 0);
if (count($name) > 0)
	$eJuice['name'] = $name->innertext; //Название
$img = $html->find('.product-card a.prd_image img', 0);
if (count($img) > 0)
	$eJuice['img'] = $img->src; //Картинка
$price = $html->find('.product-price-value', 0);
if (count($price) > 0)
	$eJuice['price'] = $price->innertext; //Цена

$optionsCount = $html->find('div.options .sku-feature');

if (count($optionsCount) > 0) {
	if (count($optionsCount) == 1) {
		$amounts = $html->find('div.options .sku-feature option');
		if (count($amounts) > 0)
			foreach ($amounts as $amount)
				$eJuice['amounts']['ob'][] = $amount->innertext; //Объемы
	} elseif (count($optionsCount) == 2) {
		$nic = $html->find('div.options .sku-feature', 0)->children;
		if (count($nic) > 0)
			foreach ($nic as $n)
				$eJuice['amounts']['nic'][] = $n->innertext; //Никотин
		
		$ob = $html->find('div.options .sku-feature', 1)->children;
		if (count($ob) > 0)
			foreach ($ob as $o)
				$eJuice['amounts']['ob'][] = $o->innertext; //Объемы
	}
}


$rateCount = $html->find('div.rate-amount', 0);
if (count($rateCount) > 0)
	$eJuice['rate']['count'] = str_replace(['(', ')'], '', trim($rateCount->innertext)); //Рейтинг кол-во участников
$rateValue = $html->find('meta[itemprop=ratingValue]', 0);
if (count($rateValue) > 0)
	$eJuice['rate']['value'] = $rateValue->content; //Рейтинг звезд

//!d($eJuice);

?>

<html lang="ru-RU">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv = "content-type" content = "text/html; charset = UTF-8">
	</head>
	<body>
	<?php
	echo $eJuice['name'];
	!d($eJuice);
	?>
	</body>
</html>
*/