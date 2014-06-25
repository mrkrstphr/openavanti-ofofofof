<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 *
 */
 
 
	/**
	 * A class to handle manipulation of cache files (or any file, really).
	 *
	 * @category	Controller
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/cache
	 */
	class Cache
	{
		private $sFileName = null;
		private $iCreatedTimestamp = null;
		private $iModifiedTimestamp = null;
		
		private $sCacheFile = null;
		
		
		/**
		 * The constructor for the cache class. Loads the supplied cache file, if one was specified.
		 * 
		 * @argument string The absolute path to the cache file to load		 		 
		 */
		public function __construct( $sCacheFileName = null )
		{
			if( !is_null( $sCacheFileName ) )
			{
				$this->Open( $sCacheFileName );
			}
			
		} // __construct()	
		

		/**
		 * Simply returns whether or not the supplied file path exists. There is no difference 
		 * between calling this method and calling file_exists().
		 * 
		 * @argument string The absolute path to the cache file we're checking the existence of
		 * @returns boolean True if the file exists, false if not
		 */
		public static function Exists( $sCacheFileName )
		{
			return( file_exists( $sCacheFileName ) );
			
		} // Exists()
		
		
		/**
		 * Attempts to open a cache file. If the file does not exist, a FileNotFoundException is
		 * thrown. If the file does exist, it's contents are loaded, as well as the created and
		 * modified time for the file. This method returns the contents of the cache file.		 		 		 
		 * 		 		 
		 * @argument string The name of the cache file to load		 
		 * @returns string The contents of the cache file
		 * @throws FileNotFoundException		 
		 */
		public function Open( $sCacheFileName )
		{
			if( !file_exists( $sCacheFileName ) )
			{
				throw new FileNotFoundException( "Cache file {$sCacheFileName} does not exist" );
			}
			
			$this->sFileName = $sCacheFileName;
			
			$this->sCacheFile = file_get_contents( $sCacheFileName );
			$this->iCreatedTimestamp = filectime( $sCacheFileName );
			$this->iModifiedTimestamp = filemtime( $sCacheFileName );
			
			return( $this->sCacheFile );
		
		} // Open()
		
		
		/**
		 * Attempts to save a cache file with the specified contents. If the directory part of
		 * the supplied file name does not exist, a FileNotFoundException is thrown. If this method
		 * fails to write to the supplied file, an Exception is thrown.
		 * 
		 * On a sucessful save, this method loads information about the cache file and stores
		 * the cache contents. 
		 *		 
		 * @argument string The name of the file to save the cache contents to
		 * @argument string The content to be cached in the supplied file		 
		 * @returns void
		 * @throws FileNotFoundException
		 * @throws Exception		 		 
		 */
		public function Save( $sCacheFileName, $sCacheContents )
		{
			$sDirectory = dirname( $sCacheFileName );
			
			if( !file_exists( $sDirectory ) )
			{
				throw new FileNotFoundException( "Directory path {$sDirectory} does not exist" );
			}
			
			if( @file_put_contents( $sCacheFileName, $sCacheContents ) === false )
			{
				throw new Exception( "Failed to write to file {$sCacheFileName}" );
			}
		
			$this->sFileName = $sCacheFileName;
			
			$this->sCacheFile = $sCacheContents;
			$this->iCreatedTimestamp = filectime( $sCacheFileName );
			$this->iModifiedTimestamp = filemtime( $sCacheFileName );
			
		} // Create()		
	
	
		/**
		 * This method actually does not close anything as we do not keep an active connection
		 * to the file. Instead, this method simply clears all file variables and stored contents.		 
		 * 	 
		 * @returns void
		 */
		public function Close()
		{
			$this->sFileName = null;
			
			$this->sCacheFile = null;
			$this->iCreatedTimestamp = null;
			$this->iModifiedTimestamp = null;
			
		} // Close()
	
	
		/**
		 * Returns the created time for the current cache file.
		 * 	 
		 * @returns integer The timestamp for when the current file was created
		 */
		public function GetCreatedTime()
		{
			return( $this->iCreatedTimestamp );
		
		} // GetCreatedTime()
		
		
		/**
		 * Returns the last created time for the current cache file.
		 * 	 
		 * @returns integer The timestamp for when the current file was last modified
		 */
		public function GetModifiedTime()
		{
			return( $this->iModifiedTimestamp );
		
		} // GetModifiedTime()
		
		
		/**
		 * The __toString() method returns the contents of the cache file
		 * 	 
		 * @returns string The contents of the cache file
		 */
		public function __toString()
		{
			return( $this->sCacheFile );
			
		} // __toString()
		
	} // Cache()

?>
