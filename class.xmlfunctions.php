<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	DOM
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 *
 */
 
 
	/**
	 * A library for manipulating XML objects and/or strings
	 *
	 * @category	String
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/xmlfunctions
	 */
	class XMLFunctions
	{
		
		/**
		 *
		 *
		 */		 		 		
		private function __construct()
		{
			// this class cannot be instantiated
			
		} // __construct()
		

		/**
		 * Receives a string of XML and uses the DOM extension to properly format the XML. This
		 * includes breaking the elements onto new lines and properly indenting. 
		 * 
		 * This method requires the DOM extension and DOMDocument class. If this class does not
		 * exist, PrettyPrint will throw an exception.						      
		 *
		 * @argument string The XML string to format
		 * @returns string A properly indented, pretty version of the passed XML
		 */
		public static function PrettyPrint( $sXML )
		{
			if( !class_exists( "DOMDocument" ) )
			{
				throw new ExtensionNotInstalledException( "Class DomDocument does not exist" );
			}
		
			$oDOM = new DOMDocument( "1.0" );
			$oDOM->formatOutput = true;
			
			$oDOM->loadXML( $sXML );
			
			return( $oDOM->saveXML() );
			
		} // PrettyPrint()
	
	
	}; // XMLFunctions()

?>
