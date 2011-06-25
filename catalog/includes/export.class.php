<?php

/*
ini_set('display_errors', true);
error_reporting(E_ALL);*/



/** 
 * @author AJ, ajweb.es
 * @version feb2011
 * 
 * recoje productos en un array usable para componer RSS, XML, CSVs, etc
 * clase estatica, ya que instanciando tendriamos copias identicas
 * solo debe exportar el array
 */
		
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/application_top.php';

 interface export {
/*	private $categories=Array();
	private $products=Array();
	private $DEFAULT_LANGUAGE;
	public static function getProducts();
	private function __construct();
	private static function db();
	private static function execute();
	
	private static function getCategories();
	private static function getLanguage();*/
}

class zenExport implements export {
	private static $db=false;
	private static $language_id;
	private static $language_code;
	private static $currency;
	private static $DEBUG=false;
	
	private static $categories=Array();
	private static $products=Array();
	
	private static $categoriesToExclude=Array(''); // we define one empty element so the SQL doesn't give an error
	
	private function __construct() {	
	}
	
	public static function activateDebug() {
		self::$DEBUG = true;
	}
	
	private static function debug($msg, $caller) {
		if( self::$DEBUG ):
			print('-------------------------<br/>');
			print($caller);
			print('<br/>');
			print_r($msg);
			print('<br/>--------------------<br/>');
		endif;
	}
	
	private static function setLocalization() {
		if( isset($_GET['lang']) and !empty($_GET['lang']) ) {
			$langs = new language($_GET['lang']);
			self::$language_id = $langs->language['id'];
			self::$language_code = $langs->language['code'];
			// necessary for zencart zen_generate_category_path to get the language
			$_SESSION['languages_id'] = $langs->language['id'];
			$_SESSION['languages_code'] = $langs->language['code'];
		} 
		elseif( isset($_SESSION['languages_id']) ) {
			self::$language_id = $_SESSION['languages_id'];
			self::$language_code = $_SESSION['languages_code'];
		}
		else {
			self::$language_id = 2;
			self::$language_code = 'es';
		}
		
		// adjust currency as session var that is read by zencart
		// so it returns prices with the proper currency
		if( isset($_GET['cur']) and !empty($_GET['cur']) ) {
			$cur = new currencies();
			if( in_array( strtoupper($_GET['cur']), array_keys($cur->currencies) ) ) {
				$_SESSION['currency'] =  strtoupper($_GET['cur']);
			} 
			self::$currency = $_SESSION['currency'];
		}
	}

	public static function getLanguageId() {
		if( empty(self::$language_id)) self::setLocalization();
		return self::$language_id;
	}
	
	public static function getCurrencyCode() {
		if( empty(self::$currency) ) self::setLocalization();
		return self::$currency;
	}
	
	public static function getProducts( $limit=null, $categoriesToExclude=Array() ) {		
		if( !empty(self::$products) ) return self::$products;
		if(empty(self::$language_id) )  self::setLocalization();			
		//self::getCategories();	
		
		if( !empty($categoriesToExclude) ) {
			foreach($categoriesToExclude as $k=>$v) self::addCategoryToExclude($v);			 
		}
		
		$products =  self::execute(self::getProductsSQL(), $limit);	
			
		self::debug(self::$categories, 'zenExport::getProducts');	
		self::debug($products, 'zenExport::getProducts db object:');		
		self::debug(self::getProductsSQL(), 'zenExport::getProductsSQL:');
		
		$i=0;
		while (!$products->EOF):
			self::$products[$products->fields['products_id']] = Array(
				'products_price'=>zen_get_products_display_price($products->fields['products_id']),
				'products_image'=>DIR_WS_IMAGES . $products->fields['products_image'],
				'categories_id'=>self::getCategoryPath($products->fields['products_id']),
				'products_name'=>$products->fields['products_name'],
				'products_description'=>$products->fields['products_description'],
				'products_id'=>$products->fields['products_id'],
				'products_url'=>zen_href_link(zen_get_info_page($products->fields['products_id']), 'products_id=' . $products->fields['products_id'] )
			); 

			$i++;
			$products->MoveNext();
		endwhile;
		
		self::debug(self::$products, 'export::getProducts() products array:');
		self::debug($i, 'export::getProducts() number of products in array:');
		
		return self::$products;		
	}

	public static function getNumberOfProducts() {
		$products =  self::execute(self::getProductsSQL(true), $limit);
		return $products->fields['count(*)'];
	}
	
	private static function getProductsSQL($countOnly=false) {
		$sql =  "SELECT ";
		if($countOnly)  $sql .= ' count(*) ';
		else $sql .= 'p.products_price, p.products_image, p.products_id, ptc.categories_id, pd.products_name, 
			pd.products_description ';
			
		$sql .=	"FROM ".TABLE_PRODUCTS." p LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." ptc ON p.products_id=ptc.products_id, 
			".TABLE_PRODUCTS_DESCRIPTION." pd, ".TABLE_CATEGORIES_DESCRIPTION." cd 
			WHERE p.products_id=pd.products_id 
			AND ptc.categories_id NOT IN ( " . self::getCategoriesToExclude() ." ) 
			AND pd.language_id=".self::$language_id." 
			AND cd.language_id=".self::$language_id." 
			AND cd.categories_id=ptc.categories_id 
			AND p.products_quantity>0 
			AND p.products_status=1 
			order by p.products_id asc";
		return $sql;
	}
	
	
	private static function db() {
		include_once($_SERVER['DOCUMENT_ROOT'].'/gestion/includes/configure.php');		
		include_once($_SERVER['DOCUMENT_ROOT'].'/includes/classes/class.base.php');
		include_once($_SERVER['DOCUMENT_ROOT'].'/includes/classes/db/mysql/query_factory.php');	
		include_once $_SERVER['DOCUMENT_ROOT'].'/includes/database_tables.php';
		
		self::$db = new queryFactory();
		self::$db->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
		return self::$db;
	}
	
	private static function execute($zf_sql, $zf_limit = false) {
		if( !self::$db ) self::db();
		return self::$db->Execute($zf_sql, $zf_limit, $zf_cache=false, $zf_cachetime=0);
	}
	
	private static function addCategoryToExclude($cat) {
		self::$categoriesToExclude[] = $cat;
		$children = self::getCategoryChildren($cat);
		if( !empty($children) ) {
			foreach($children as $k=>$v) {
				self::addCategoryToExclude($v);
			}
		}
	}
	
	private static function getCategoriesToExclude() {
		$mysqlInString = '';
		foreach(self::$categoriesToExclude as $cat) {			
			$mysqlInString .= " '".$cat."', ";
		}
		$mysqlInString = substr($mysqlInString, 0, strlen($mysqlInString)-2);
		return $mysqlInString;
	}
	
	private static function getCategoryChildren($catId) {
		$cats = self::execute(self::categoryChildrenSQL($catId));
		while (!$cats->EOF){
			self::$categoriesToExclude[] = $cats->fields['categories_id'];
			$cats->MoveNext();
		}
	}
	
	private static function categoryChildrenSQL($categoryId) {
		$sql = 'SELECT categories_id
		 FROM categories
		 WHERE parent_id ='.$categoryId;
		 return $sql;
	}
	
	private static function getCategoryPath($products_id) {
		$categoriesArray = zen_generate_category_path($products_id);
		$catPath = '';
		while( ($cat = array_pop($categoriesArray[0])) !== null ) {
		$catPath .= $cat['text'] . ' > ';
		}
		$catPath = substr($catPath, 0, (strlen($catPath)-3) );
		return $catPath;
	}
	
}


?>