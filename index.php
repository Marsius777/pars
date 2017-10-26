<?php
error_reporting(-1);
ini_set('display_errors', 'On');
ini_set('memory_limit', '256M');
mb_internal_encoding("UTF-8");

require __DIR__  . '/vendor/autoload.php';

$start = false;
$debug = true;


$siteUrl = 'https://xn--80aaxitdbjk.xn--p1ai';
$eJuices = [];
$json = '';
$eJuicePreset = ['name' => '', 'img' => '', 'price' => '', 'amounts' => ['nic' => [], 'ob' => []], 'rate' => []];


if ($start) {
	$instance = new simple_html_dom();
	

	// Все поставщики жиж
	$html = file_get_html($siteUrl . '/category/zhidkosti-dlya-elektronnykh-sigaret/');
	$firms = $html->find('ul.submenu a');
	// Парсим каждого поставщика
	foreach ($firms as $firm) {
		$firmName = str_replace('Жидкости для электронных сигарет ', '', $firm->title);
		
		// Один поставщик
		$categoryHtml = file_get_html($siteUrl . $firm->href);
		$items = $categoryHtml->find('div.pr-title a');
		//Парсим каждую жижу
		foreach ($items as $itemUrl) {
			$itemHtml = file_get_html($siteUrl . $itemUrl->href);
			$eJuice = $eJuicePreset;
			//Берем данные
			$name = $itemHtml->find('.card-desc span', 0);
			if (count($name) > 0)
				$eJuice['name'] = str_replace(' - ' . $firmName, '', $name->innertext); //Название
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
							$eJuice['amounts']['ob'][] = $amount->innertext; //Объемы
				} elseif (count($optionsCount) == 2) {
					$nic = $itemHtml->find('div.options .sku-feature', 0)->children;
					if (count($nic) > 0)
						foreach ($nic as $n)
							$eJuice['amounts']['nic'][] = $n->innertext; //Никотин
					
					$ob = $itemHtml->find('div.options .sku-feature', 1)->children;
					if (count($ob) > 0)
						foreach ($ob as $o)
							$eJuice['amounts']['ob'][] = $o->innertext; //Объемы
				}
			}
			
			$rateCount = $itemHtml->find('div.rate-amount', 0);
			if (count($rateCount) > 0)
				$eJuice['rate']['count'] = str_replace(['(', ')'], '', trim($rateCount->innertext)); //Рейтинг кол-во участников
			$rateValue = $itemHtml->find('meta[itemprop=ratingValue]', 0);
			if (count($rateValue) > 0)
				$eJuice['rate']['value'] = $rateValue->content; //Рейтинг звезд
			
			$eJuices[$firmName][] = $eJuice;
			
			echo 'processed: ' . $eJuice['name'] . "\n";
			
			if ($debug) break;
		}
		
		echo '------ FIRM DONE: ' . $firm->href . " ------- \n";
		
		if ($debug) break;
	}
}



//$content = json_encode($eJuices, JSON_UNESCAPED_UNICODE);
//$fp = fopen(__DIR__  . "/papiroska_result.txt","wb");
//fwrite($fp, $content);
//fclose($fp);

$out = file_get_contents(__DIR__  . "/papiroska_result.txt","wb");
d(json_decode($out, true));


d(substr('//asdasas', 2));
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