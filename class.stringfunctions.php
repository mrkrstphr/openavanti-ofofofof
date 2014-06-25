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
	 * A library for manipulating strings
	 *
	 * @category	String
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/stringfunctions
	 */
	class StringFunctions
	{

		/**
		 * Attempts to turn a supplied string, preferably an English, singular word, into the
		 * plural version of the string.
		 *
		 * @argument string the singular word to attempt to make plural
		 * @returns string the result of attempting to make the word plural
		 */
		public static function ToSingular( $sString )
		{
			if( strtolower( $sString ) == "people" )
			{
				return( "person" );
			}
		
			if( substr( $sString, strlen( $sString ) - 3, 3 ) == "ies" )
			{
				$sString = substr( $sString, 0, strlen( $sString ) - 3 ) . "y";
			}
			else if( substr( $sString, strlen( $sString ) - 2, 2 ) == "es" )
			{
				$sString = substr( $sString, 0, strlen( $sString ) - 2 );
			}
			else if( substr( $sString, strlen( $sString ) - 1, 1 ) == "s" )
			{
				$sString = substr( $sString, 0, strlen( $sString ) - 1 );
			}
			
			return( $sString );
		
		} // ToSingular()
		
		
		/**
		 * Attempts to turn a supplied string, preferably an English, plural word, into the
		 * singular version of the string.
		 *
		 * @argument string the plural word to attempt to make singular
		 * @returns string the result of attempting to make the word singular
		 */
		public static function ToPlural( $sString )
		{		
			if( strtolower( $sString ) == "person" )
			{
				return( "people" );
			}
		
			if( substr( $sString, strlen( $sString ) - 1, 1 ) == "y" )
			{
				$sString = substr( $sString, 0, strlen( $sString ) - 1 ) . "ies";
			}
			else if( substr( $sString, strlen( $sString ) - 1, 1 ) != "s" )
			{
				$sString .= "s";
			}
			
			return( $sString );
		
		} // ToSingular()
	
	
		/**
		 * Takes a string of PHP code and uses highlight_string() to syntax highlight the code,
		 * then explodes n each line and returns the code wrapped in a div with an ordered list,
		 * each line of code being a line in the ordered list to provide line numbers. It is
		 * up to the user to style this returned HTML.				      
		 *
		 * @argument string The string of PHP code to format
		 * @returns string The formatted PHP code
		 */
		public static function FormatPHPCode( $sCode ) 
		{
			$sCode = highlight_string( trim( $sCode ), true );
			$aCode = explode( "<br />", $sCode );
			
			$sCode = "";
			
			
			foreach( $aCode as $sLine )
			{
				$sCode .= "\t\t<li><code>{$sLine}</code></li>\n";
			}
			
			$sCode = "<div>\n\t<ol>\n{$sCode}</ol>\n</div>";
			
			return( $sCode );
		
		} // FormatPHPCode()
		
		
		/**
		 * Returns a substring of the supplied string, starting after the last occurrence	of the
		 * supplied delimiter.	      
		 *
		 * @argument string The string we're generating a substring from
		 * @argument string The delimiter that we're searching for
		 * @returns string The generated substring
		 */
		public static function AfterLastOccurrenceOf( $sString, $sDelim )
		{
			return( substr( $sString, strrpos( $sString, $sDelim ) + 1 ) );
		
		} // AfterLastOccurrenceOf()
	
	}; // SringFunctions()

?>
