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
	 * A library of form field generation helpers, mainly useful automate data preservation on
	 * form errors	 
	 *
	 * @category	Forms
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/form
	 */
	class Form 
	{
		public static $aFields = array();

		
		/**
		 * Loads the specified array or object into the classes aFields array. These values are 
		 * later used by the field generation helpers for setting the value of the form field. This 
		 * method will recursively iterate through a multidimensional array, or an object with 
		 * member objects to load all data within the array or object.		 
		 * 
		 * @argument mixed An array or object of keys and values to load into the forms data array
		 * @returns void
		 */
		public static function Load( $oObject, &$aTarget = null )
		{
			is_null( $aTarget ) ? $aTarget = &self::$aFields : $aTarget = $aTarget;
		
			foreach( $oObject as $sKey => $sValue )
			{
				if( !is_object( $sValue ) && !is_array( $sValue ) )
				{
					$aTarget[ $sKey ] = $sValue;
				}
				else
				{
					if( !isset( $aTarget[ $sKey ] ) )
					{
						$aTarget[ $sKey ] = array();
					}
					
					self::Load( $sValue, $aTarget[ $sKey ] );
				}
			}
						
		} // Load()		
		
		
		/**
		 * Generate a label for the form. Note that the supplied attributes are not validated to be
		 * valid attributes for the element. Each element provided is added to the XHTML tag. The
		 * "label" element of aAttributes specifies the text of the label.		 	  
		 * 
		 * @argument array An array of attributes for the HTML element
		 * @argument bool Controls whether or not to return the HTML, otherwise echo it, default false
		 * @returns void/string If bReturn is true, returns a string with the XHTML, otherwise void
		 */
		public static function Label( $aAttributes, $bReturn = false )
		{
            if( !isset( $aAttributes[ "label" ] ) )
            {
                return;
            }
            
			$sLabel = $aAttributes[ "label" ];
			unset( $aAttributes[ "label" ] );
				
				
			if( class_exists( "Validation" ) && isset( $aAttributes[ "for" ] ) && 
				Validation::FieldHasErrors( $aAttributes[ "for" ] ) )
			{
				$aAttributes[ "class" ] = isset( $aAttributes[ "class" ] ) ? 
					$aAttributes[ "class" ] . " error" : "error";	
			}
			
			$sInput = "<label ";
			
			foreach( $aAttributes as $sKey => $sValue )
			{
				$sInput .= "{$sKey}=\"{$sValue}\" ";
			}
			
			$sInput .= ">{$sLabel}</label>";
			
			
			if( $bReturn )
			{
				return( $sInput );
			}
			else
			{
				echo $sInput;
			}
			
		} // Label()
		
		
		/**
		 * Generate an input element for the form. Note that the supplied attributes are not 
		 * validated to be valid attributes for the element. Each element provided is added to the 
		 * XHTML tag.		  
		 * 
		 * @argument array An array of attributes for the HTML element
		 * @argument bool Controls whether or not to return the HTML, otherwise echo it, default false
		 * @returns void/string If bReturn is true, returns a string with the XHTML, otherwise void
		 */
		public static function Input( $aAttributes, $bReturn = false )
		{
            if( !isset( $aAttributes[ "type" ] ) )
            {
                return;
            }
            
			if( strtolower( $aAttributes[ "type" ] ) == "checkbox" ||
                strtolower( $aAttributes[ "type" ] ) == "radio" )
			{
				$sValue = self::TranslatePathForValue( $aAttributes[ "name" ] );

				if( isset( $aAttributes[ "value" ] ) && $aAttributes[ "value" ] == $sValue )
				{
					$aAttributes[ "checked" ] = "checked";
				}
			}
			else if( strtolower( $aAttributes[ "type" ] ) != "password" )
			{
				$sValue = self::TranslatePathForValue( $aAttributes[ "name" ] );
				
				$aAttributes[ "value" ] = $sValue !== false ? $sValue : "";
			}
		
			$sInput = "<input ";
			
			foreach( $aAttributes as $sKey => $sValue )
			{
				$sValue = htmlentities( $sValue );
				$sInput .= "{$sKey}=\"{$sValue}\" ";
			}
			
			$sInput .= " />";
			
			
			if( $bReturn )
			{
				return( $sInput );
			}
			else
			{
				echo $sInput;
			}
			
		} // Input()
		
		
		/**
		 * Generate a select element for the form. Note that the supplied attributes are not 
		 * validated to be valid attributes for the element. Each element provided is added to the 
		 * XHTML tag.
		 * 
		 * The options are specified by aAttributes[ options ] as an array of key => values to
		 * display in the select		 
		 *		 		 
		 * The default (selected) attribute is controlled by aAttributes[ default ], which should
		 * match a valid key in aAttributes[ options ]		 		 		   
		 * 
		 * @argument array An array of attributes for the HTML element
		 * @argument bool Controls whether or not to return the HTML, otherwise echo it, default false
		 * @returns void/string If bReturn is true, returns a string with the XHTML, otherwise void
		 */
		public static function Select( $aAttributes, $bReturn = false )
		{
            if( !isset( $aAttributes[ "options" ] ) || !is_array( $aAttributes[ "options" ] ) )
            {
                return;
            }
            
			$sDefault = "";
			
			$sValue = self::TranslatePathForValue( $aAttributes[ "name" ] );
			
			if( $sValue !== false )
			{
				$sDefault = $sValue;
			}
			else if( isset( $aAttributes[ "default" ] ) )
			{
				$sDefault = $aAttributes[ "default" ];
				unset( $aAttributes[ "default" ] );
			}
		
			$sSelect = "<select ";
			
			foreach( $aAttributes as $sKey => $sValue )
			{
				if( $sKey == "options" )
				{
					continue;
				}
				
				$sSelect .= "{$sKey}=\"{$sValue}\" ";
			}
			
			$sSelect .= ">\n";
			
			foreach( $aAttributes[ "options" ] as $sKey => $sValue )
			{
				$sSelected = $sKey == $sDefault ? 
					" selected=\"selected\" " : "";
					
				$sSelect .= "\t<option value=\"{$sKey}\"{$sSelected}>{$sValue}</option>\n";
			}
			
			$sSelect .= "\n</select>\n";
			
			if( $bReturn )
			{
				return( $sSelect );
			}
			else
			{
				echo $sSelect;
			}
		
		} // Select()
		
		
		/**
		 * Generate a textarea element for the form. Note that the supplied attributes are not 
		 * validated to be valid attributes for the element. Each element provided is added to the 
		 * XHTML tag.		  
		 * 
		 * @argument array An array of attributes for the HTML element
		 * @argument bool Controls whether or not to return the HTML, otherwise echo it, default false
		 * @returns void/string If bReturn is true, returns a string with the XHTML, otherwise void
		 */
		public static function TextArea( $aAttributes, $bReturn = false )
		{		
			$sInput = "<textarea ";
			
			foreach( $aAttributes as $sKey => $sValue )
			{
				$sInput .= "{$sKey}=\"{$sValue}\" ";
			}
			
			$sInput .= ">";

			$sValue = self::TranslatePathForValue( $aAttributes[ "name" ] );
			$sInput .= $sValue !== false ? $sValue : "";
			
			$sInput .= "</textarea>";
			
			
			if( $bReturn )
			{
				return( $sInput );
			}
			else
			{
				echo $sInput;
			}
			
		} // TextArea()
		
		
		/**
		 *
		 */		 		
		private static function TranslatePathForValue( $sName )
		{
			$sValue = false;
			
			$sPath = str_replace( "[", "/", $sName );
			$sPath = str_replace( "]", "", $sPath );
			
			$aKeys = explode( "/", $sPath );
			
			$aData = self::$aFields;
			
			foreach( $aKeys as $sKey )
			{
				if( isset( $aData[ $sKey ] ) )
				{
					$aData = $aData[ $sKey ];
				}
				else
				{
					return( false );
				}
			}
			
			if( !is_array( $aData ) )
			{
				$sValue = $aData;
			}
			
			if( $sValue === true )
			{
				$sValue = "t";
			}
			else if( $sValue === false )
			{
				$sValue = "f";
			}
			
			return( $sValue );
		
		} // TranslatePathForValue()

	}; // Form()

?>
