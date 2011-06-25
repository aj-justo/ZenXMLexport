<?php
/*ini_set('display_errors', true);
error_reporting(E_ALL);*/


require_once 'fileCache.class.php';
require_once 'export.class.php';
require_once 'xml.class.php';



// the ID is used to identify the cache file, so it should be unique for the xml feed

$nextag = new xml($root='lanauticaProducts', $id='nextag',  zenExport::getLanguageId(), zenExport::getCurrencyCode() );

$nextag->xmlFieldToProductField('ID', 'products_id');
$nextag->xmlFieldToProductField('Name', 'products_name');
$nextag->xmlFieldToProductField('Description', 'products_description');				
$nextag->xmlFieldToProductField('Price', 'products_price');				
$nextag->xmlFieldToProductField('Url', 'products_url');				
$nextag->xmlFieldToProductField('Category', 'categories_id');				
$nextag->xmlFieldToProductField('Image', 'products_image');				

$nextag->xmlFieldToFixedValue('Manufacturer', '');
$nextag->xmlFieldToFixedValue('PartNumber', '');
$nextag->xmlFieldToFixedValue('Stock', 'Yes');
$nextag->xmlFieldToFixedValue('Condition', 'New');

$nextag->setXmlRequireField('ID');
$nextag->setXmlRequireField('Name');

//$nextag->setLimit(100);
$productsNumber = array('numberOfProducts'=>zenExport::getNumberOfProducts());
$serial = serialize(array_merge($nextag->getConfiguration(), $productsNumber));
$id = sha1( $serial);

$cache = fileCache::getCache($id);
if( $cache and !empty($cache) ) echo $cache;
else {
	//zenExport::activateDebug();
	// AJTODO: comprobar xml->setLimit y ajustar limite acorde
	// es la forma para que el xml cambie y la cache sea distinta dependiendo de limite

	$nextag->composeXml( zenExport::getProducts( null, Array(100094) ), 
	// zenExport::getProducts(limiteNumProducts, filterCategoriesNotToInclude)
 

						 $productItemRoot = 'Product' );
	echo $nextag->getXml();
	fileCache::saveCache($id, $nextag->getXml());
	
}
