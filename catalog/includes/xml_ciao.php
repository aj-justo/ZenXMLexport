<?php

require_once 'fileCache.class.php';
require_once 'export.class.php';
require_once 'xml.class.php';

// the ID is used to identify the cache file, so it should be unique for the xml feed


$ciao = new xml($root='offers', $id='ciao', zenExport::getLanguageId(), zenExport::getCurrencyCode() );

$ciao->xmlFieldToProductField('offer-id', 'products_id');
$ciao->xmlFieldToProductField('name', 'products_name');
$ciao->xmlFieldToProductField('description', 'products_description');				
$ciao->xmlFieldToProductField('price', 'products_price');				
$ciao->xmlFieldToProductField('deeplink', 'products_url');				
$ciao->xmlFieldToProductField('category', 'products_all_categories');				
$ciao->xmlFieldToProductField('imageurl', 'products_image');				

$ciao->xmlFieldToFixedValue('brand', '');
$ciao->xmlFieldToFixedValue('ean', '');

$ciao->xmlFieldToFixedValue('delivery-charge', '');
$ciao->xmlFieldToFixedValue('availability', 'En stock');

$ciao->setXmlRequireField('offer-id');
$ciao->setXmlRequireField('name');

//$ciao->setLimit(100);

$productsNumber = array('numberOfProducts'=>zenExport::getNumberOfProducts());
$serial = serialize(array_merge($ciao->getConfiguration(), $productsNumber));
$id = sha1( $serial);

$cache = fileCache::getCache($id);

if( $cache and !empty($cache) ) echo $cache;
else {
	//zenExport::activateDebug();
	// AJTODO: comprobar xml->setLimit y ajustar limite acorde
	// es la forma para que el xml cambie y la cache sea distinta dependiendo de limite
	$ciao->composeXml( zenExport::getProducts( null, Array(100094) ), 
						 $productItemRoot = 'offer' );
	echo $ciao->getXml();
	fileCache::saveCache($id, $ciao->getXml());
}
