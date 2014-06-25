<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 *
 */

	/**
	 * The request object stores information about the web request and how it was routed, as well
     * as stores data setup by the controller, including view file and loaded data.
	 *
	 * @category	Database
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/request
     */
    class Request
	{
		public $sURI = null;
		public $sRewrittenURI = null;
		
		public $sControllerName = null;
		public $oController = null;
		
		public $sAction = null;
		
		public $aArguments = array();
		
		public $aLoadedData = array();
		
		public $sView = null;
		
		public $sRequestType = "";
		
		public $bSecureConnection = false;
		
		
		/**
		 * Constructor. Determines information about the request type and connection type and 
		 * stores it within the class.		 
		 *
		 */
		public function __construct()
		{
			$this->sRequestType = $_SERVER[ "REQUEST_METHOD" ];
			$this->bSecureConnection = isset( $_SERVER[ "HTTPS" ] ) && !empty( $_SERVER[ "HTTPS" ] );
		
		} // __construct()
		
		
		/**
		 * Returns true if the current request came via a secure connection, or false otherwise.
		 *
		 * @returns bool True if the current request is a secure connection, false otherwise
		 */		 		 		 		
		public function IsSecureConnection()
		{
			return( $this->bSecureConnection );
			
		} // IsSecureConnection()
		
		
		/**
		 * Returns true if the current request is a POST request, or false otherwise.
		 *
		 * @returns bool True if the current request is a POST request, false otherwise
		 */		 		 		 		
		public function IsPostRequest()
		{
			return( strtolower( $sRequestType ) == "post" );
			
		} // IsSecureConnection()
		
		
		/**
		 * Returns true if the current request is a GET request, or false otherwise.
		 *
		 * @returns bool True if the current request is a GET request, false otherwise
		 */		 		 		 		
		public function IsGetRequest()
		{
			return( strtolower( $sRequestType ) == "get" );
			
		} // IsSecureConnection()
		
		
	} // Request()

?>
