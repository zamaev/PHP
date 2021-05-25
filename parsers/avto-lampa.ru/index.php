<?php
require_once('db.php');

$db = new DB();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: text/plain');

$doc = new DOMDocument();

$sleep = 3;
$nanosleep = 400000000;

// проход марки
$base_url = 'https://avto-lampa.ru';
$doc->loadHTML(file_get_contents($base_url));
$xpath = new DOMXPath($doc);
$elements = $xpath->query('/html/body/div[1]/div[3]/div/div[2]/div[1]/div[1]/div[2]/ul');
// time_nanosleep(1, $nanosleep);
if (!is_null($elements)) {
	$ul_childs = $elements->item(0)->childNodes;
	$markas = $db->getMarkas();
	foreach ($ul_childs as $li) {
		$marka = $li->childNodes->item(0)->textContent;
		if (in_array($marka, $markas)) {
			echo $marka . " - есть в базе.\n";
			continue;
		}
		$href = $li->childNodes->item(0)->getAttribute('href');
		// === 
		echo $marka . ' (' . $href . ")\n";


			// проход модели
			$url = $base_url.$href;
			$doc->loadHTML(file_get_contents($url));
			$xpath = new DOMXPath($doc);
			$elements = $xpath->query('/html/body/div[1]/div[3]/div/div[2]/div[1]/div[1]/div[2]/div/div/div[1]/ul');
			time_nanosleep(1, $nanosleep);
			if (!is_null($elements)) {
				$ul_childs = $elements->item(0)->childNodes;
				$models = $db->getModels($marka);
				foreach ($ul_childs as $li) {
					$model = $li->childNodes->item(0)->textContent;
					if (in_array($model, $models)) {
						echo "    |-" . $model . " - есть в базе.\n";
						continue;
					}
					$href = $li->childNodes->item(0)->getAttribute('href');
					// === 
					echo "    |-" . $model . ' (' . $href . ")\n";


						// проход выпуска
						$url = $base_url.$href;
						while (true) {
							$doc->loadHTML(file_get_contents($url));
							$xpath = new DOMXPath($doc);
							$robot = $xpath->query('//*[@id="hc"]/div[3]/div/div[2]/div/div/div[2]/div/div/div[2]/div/form/fieldset/div');
							if ($robot->length == 0) {
								$elements = $xpath->query('/html/body/div[1]/div[3]/div/div[2]/div[1]/div/div[2]/div/div');
								time_nanosleep(1, $nanosleep);
								break;
							}
							echo "Жду обхода проверки на робота.\n";
							sleep($sleep);
						}
						if (!is_null($elements)) {
							$row_childs = $elements->item(0)->childNodes;
							$vipusks = $db->getVipusks($marka, $model);
							foreach ($row_childs as $div) {
								$vipusk = trim($div->childNodes->item(1)->textContent);
								if (in_array($vipusk, $vipusks)) {
									echo "    |    |-" . $vipusk . " - есть в базе.\n";
									continue;
								}
								$year = trim($div->childNodes->item(3)->textContent);
								$href = $div->childNodes->item(1)->getAttribute('href');
								// === 
								echo "    |    |-" . $vipusk . ' - ' . $year . ' (' . $href . ")\n";


									// проход посадочных мест с типами
									$url = $base_url.$href;
									while (true) {
										$doc->loadHTML(file_get_contents($url));
										$xpath = new DOMXPath($doc);
										$robot = $xpath->query('//*[@id="hc"]/div[3]/div/div[2]/div/div/div[2]/div/div/div[2]/div/form/fieldset/div');
										if ($robot->length == 0) {
											$elements = $xpath->query('/html/body/div[1]/div[3]/div/div[2]/div[1]/div/div[2]/div/div/div');
											time_nanosleep(1, $nanosleep);
											break;
										}
										echo "Жду обхода проверки на робота.\n";
										sleep($sleep);
									}
									if (!is_null($elements)) {
										$row_childs = $elements->item(0)->childNodes;
										$posadkas = $db->getPosadkas($marka, $model, $vipusk);
										foreach ($row_childs as $div) {
											$posadka = $div->childNodes->item(1)->textContent;
											if (in_array($posadka, $posadkas)) {
												echo "    |    |    |-" . $posadka . " - есть в базе.\n";
												continue;
											}
											// === 
											echo "    |    |    |-" . $posadka . "\n";


											$types = $div->childNodes->item(2)->childNodes;
											foreach ($types as $item) {
												$type = trim($item->textContent);
												if (!$item->childNodes->item(0)->hasChildNodes()) {
													echo "    |    |    |    |-" . $type . " - тип не имеет ссылки\n";
													continue;
												}
												$href = $item->childNodes->item(0)->getAttribute('href');
												// == echo "    |    |    |    |-" . $type . ' (' . $href . ")\n";


													// проход товаров
													$url = $base_url.$href;
													while (true) {
														$doc->loadHTML(file_get_contents($url));
														$xpath = new DOMXPath($doc);
														$robot = $xpath->query('//*[@id="hc"]/div[3]/div/div[2]/div/div/div[2]/div/div/div[2]/div/form/fieldset/div');
														if ($robot->length == 0) {
															$elements = $xpath->query('//*[@class="offers"]');
															time_nanosleep(1, $nanosleep);
															break;
														}
														echo "Жду обхода проверки на робота.\n";
														sleep($sleep);
													}
													if (!is_null($elements)) {
														$offer_childs = $elements->item(0)->childNodes;
														foreach ($offer_childs as $div) {
															// var_dump($div);
															$product = $div->childNodes->item(0)->childNodes->item(0)->childNodes->item(0)->textContent;
															$href = $div->childNodes->item(0)->childNodes->item(0)->childNodes->item(0)->getAttribute('href');
															// == echo "    |    |    |    |    |-" . $product . ' (' . $href . ")\n";


															// проход характеристик, поиск цоколя
															$url = $base_url.$href;
															while (true) {
																$doc->loadHTML(file_get_contents($url));
																$xpath = new DOMXPath($doc);
																$robot = $xpath->query('//*[@id="hc"]/div[3]/div/div[2]/div/div/div[2]/div/div/div[2]/div/form/fieldset/div');
																if ($robot->length == 0) {
																	$elements = $xpath->query('//*[@class="list-unstyled  list-inline"]');
																	time_nanosleep(1, $nanosleep);
																	break;
																}
																echo "Жду обхода проверки на робота.\n";
																sleep($sleep);
															}
															if (!is_null($elements)) {
																$features_childs = $elements->item(0)->childNodes;
																foreach ($features_childs as $li) {
																	if ($li->childNodes->item(0)->textContent == 'Цоколь:') {
																		$cokol = trim($li->childNodes->item(1)->textContent);
																		// === echo "    |    |    |    |    |    |-" . $cokol . "\n";
																		//-without-/ 
																		echo "    |    |    |   |||-" . $cokol . "\n";

																		//-full-/ 
																		$log = $marka . ' --- ' . $model . ' --- ' . $vipusk . ' --- ' . $posadka . ' --- ' . $cokol . "\n";
																		// echo $log;

																		$db->insert($marka, $model, $vipusk, $posadka, $cokol);

																		break;
																	}
																}
															}
															// конец прохода характеристик, поиск цоколя


															break; //!! - нужен только один товар
														}
													}
													// конец прохода товаров


												break; //!! - на посадочное место нужен только один тип товара, чтобы узнать цоколь !!! НЕТ ОНИ РАЗНЫЕ ОКАЗЫВАЕТСЯ
											}
											// break; //! - получить только одно посадочное место
										}
									}
									// конец прохода посадочных мест с типами



								// break; //! - получить только один выпуск
							}
						}
						// конец прохода выпуска


					// break; //! - получить только одну модель
				}
			}
			// конец прохода модели


		// break; //! - получить только одну марку
	}
}
// конец прохода марки
