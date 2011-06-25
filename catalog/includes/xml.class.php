<?php
/*
ini_set('display_errors', true);
error_reporting(E_ALL);*/


class xml {
	
	private $xml;
	private $use_cache = true;
	private $CACHE_EXPIRES_DAYS = 7;
	private $cache;
	private $ID=null;
	private $limit = null;
	private $xmlFields=array();
	public  $xmlFieldsToProductFields = array();
	private $xmlFieldsToFixedValues = array();
	private $xmlRequiredFields = array();
	private $language;
	private $currency;
	
	public function __construct( $root='root' , $id=null, $language=null, $currency=null) {
		if( $id ) $this->ID = $id;	
		if( $language ) $this->language = $language;
		if( $currency ) $this->currency = $currency;
		
		$root = "<?xml version=\"1.0\" encoding=\"utf-8\"?><{$root}/>";
		$this->xml = $this->makeXml($root);
		//return $this->xml;
	}
	
	public function getConfiguration() {
		return Array('limit'=>$this->limit, 'id'=>$this->ID, 'language'=>$this->language, 'currency'=>$this->currency);
	}
	
	
	public function xmlFieldToProductField( $xmlField=null, $productField=null ){
		if( $xmlField==null or $productField==null ) exit('xml::xmlFieldToProductField need two parameters');
		
		array_push($this->xmlFields, $xmlField);
		$this->xmlFieldsToProductFields[$xmlField] = $productField;
	}
	
	public function xmlFieldToFixedValue( $xmlField=null, $fixedValue=null ){
		if( $xmlField==null ) exit('xml::xmlFieldToProductField need xmlField parameter');
		
		array_push($this->xmlFields, $xmlField);
		$this->xmlFieldsToFixedValues[$xmlField] = $fixedValue;
	}
	
	// main function to compose the xml from the products array
	// receives an array with the products and the name of the root for the product xml parent item
	public function composeXml( $productsArray=null, $productItemName='Product' ) {	
		if( $productsArray==null or empty($productsArray) ) exit('xml::composeXml needs an array as parameter');
		
		if( $this->limit ) $i=0;
		foreach( $productsArray as $productData ):
			if( !$this->checkRequiredFieldsPresent($productData) ) continue;
			$xmlObj = $this->addXmlChild($this->xml, $productItemName);	
			$this->addProductXmlFields($xmlObj, $productData);		
			if( $this->limit ) {
				if( $i==$this->limit ) break;
				else $i++;
			}
		endforeach;
	}
	
	public function setLimit($limit) {
		$this->limit = $limit;
	}
	
	// if a required field is not present in the productData array don't include the product
	private function checkRequiredFieldsPresent($productData) {
		foreach( $this->xmlRequiredFields as $required ):
			if( empty($productData[$this->xmlFieldsToProductFields[$required]])
				or $productData[$this->xmlFieldsToProductFields[$required]]==null ):
				return false;
			endif;
		endforeach;
		return true;
	}

	// passed xml object for the product child into which we add the product fields
	// loop through the xml required fields	to set them up
	private function addProductXmlFields(SimpleXMLElement $xmlObj, $productData) {
		foreach( $this->xmlFields as $xmlFieldName ):
			$this->addProductXmlField($xmlObj, $productData, $xmlFieldName);
		endforeach;
	}

	// @args: xml object of the product on which to add the 
	private function addProductXmlField(SimpleXMLElement $xmlObj, $productData, $xmlFieldName) {
		if( array_key_exists($xmlFieldName, $this->xmlFieldsToProductFields) ):
				$this->addXmlChild($xmlObj, $xmlFieldName, $productData[$this->xmlFieldsToProductFields[$xmlFieldName]]);
		elseif( array_key_exists($xmlFieldName, $this->xmlFieldsToFixedValues) ):
			$this->addXmlChild($xmlObj, $xmlFieldName, $this->xmlFieldsToFixedValues[$xmlFieldName]);
		endif;
	}
	
//	wrapper for the xml library in use
// just add a child to the xml object passed
	private function addXmlChild(SimpleXMLElement $xmlObj, $name, $value=null) {
		$name = $this->cleanEntities( $this->utf8Encode($name) );
		$value = $this->cleanEntities( $this->utf8Encode($value) );
		
		if( $value==null ) return $xmlObj->addChild($name);
		else return $xmlObj->addChild($name, $value);
	}
	
	public function getXml() {
		return $this->xml->asXML();
	}
		
//  wrapper for the xml library in use	
	private function makeXml($root) {
		return new SimpleXMLElement($root);
	}	
	
	private function cleanEntities($string) {
		return htmlspecialchars( html_entity_decode($string, ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8');
	}
	
	private function utf8Encode($string) {
		if( !mb_detect_encoding($string, "UTF-8") == "UTF-8" ) return utf8_encode($string);	
		else return $string;
	}
	
	public function setXmlRequireField($fieldNamme) {
		array_push($this->xmlRequiredFields, $fieldNamme);
	}

}

?>