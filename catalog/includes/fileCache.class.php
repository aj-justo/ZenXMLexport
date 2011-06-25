<?php

/** 
 * @author AJ, ajweb.es
 * 
 * 
 */

// TODO: remove old caches from disk
// TODO: method to manually remove all caches from disk

class fileCache {
	
	const CACHE_DIR = DIR_FS_SQL_CACHE;
	const CACHE_DAYS_TO_LIVE = 7;
	private static $prefix = 'ajFileCache_';
	
	private function __construct() {		
	}
	
	private static function cacheExists($cacheID) {
		if( file_exists(self::cachePath($cacheID)) ) return true;
		else return false;
	}
	
	private static function cachePath($cacheID) {
		return self::CACHE_DIR.'/'.self::$prefix.$cacheID;
	}
	
// called before we save a cache, in case we already have it	
	private static function cacheIsOK($cacheID, $content) {
		if( self::cacheExists($cacheID) ) {
			if( ( sha1(self::getCache($cacheID)) == sha1($content) )
				and !self::isCacheExpired($cacheID) ) {
					return true;
			}
		}
		return false;
	}
	
	
	private static function isCacheExpired($cacheID) {
		$expireDate = new DateTime();
		$expireDate->modify('+'.self::CACHE_DAYS_TO_LIVE.' day');
		
		if( strtotime($expireDate->format('d-m-Y')) < filemtime(self::cachePath($cacheID)) ) {
			@unlink(self::cachePath($cacheID));
			return true;
		}
		else return false;
	}
	
	
	public static function saveCache($cacheID, $string) {
		if( self::cacheIsOK($cacheID, $string) ) return;		
		@file_put_contents(self::cachePath($cacheID), $string );
	}	

	
	public static function getCache($cacheID) {
		if( self::cacheExists($cacheID) and !self::isCacheExpired($cacheID) ) {
			$cache =  @file_get_contents(self::cachePath($cacheID));
			if( $cache and !empty($cache) ) return $cache;
		}
		return false;
	}
}

?>