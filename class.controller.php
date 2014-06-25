<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	Dispatcher
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 */
 
 
	/**
	 * A default controller class to be extended
	 *
	 * @category	Controller
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/controller
	 */
	class Controller
	{		
		public $aData = array();
		public $sView = "";
		
		public $b404Error = false;
		
		
		/**
		 * Constructor. Currently does not do anything.		 		 		 		 		 		 		 
		 * 
		 * @returns void 
		 */
		public function __construct()
		{
		
		} // __construct()
				
		
		/**
		 * Every controller must have an index() method defined for default requests to the 
		 * controller that do not define a method. Since it is a requirement for this method to 
		 * exist, it is defined in the parent controller class.	 		 		 		 		 		 		 
		 * 
		 * @returns void 
		 */
		public function index()
		{
			$this->b404Error = true;
				
		} // index()
		
		
		/**
		 * Returns the internal 404 status to determine if a 404 error flag was triggered
		 *
		 * @returns bool True if a 404 error was encountered, false otherwise
		 */		 		 		 		
		public function Is404Error()
		{
			return( $this->b404Error );
			
		} // Is404Error()
		
		
		/**
		 * Sets or clears the internal 404 error status flag. 
		 * 
		 * @argument bool True to trigger a 404 error, false to clear the 404 flag, default: true
		 * @returns void
		 */	
		public function Set404Error( $bIs404Error = true )
		{
			$this->b404Error = $bIs404Error;
			
		} // Set404Error()
		
		
		/**
		 * Determines whether or not the current HTTP request came via AJAX.	 		 		 		 		 		 
		 * 
		 * @returns boolean True of the request is via AJAX, false otherwise 
		 */
		public function IsAjaxRequest()
		{
			return( Dispatcher::IsAjaxRequest() );
		
		} // IsAjaxRequest()
		
		
		/**
		 * Sets the HTTP status code header. This method will only work if no output or headers
		 * have already been sent.
		 * 		 
		 * @argument int The HTTP status code
		 * @returns bool True if the operation was successful, false on failure
		 */
		public function SetHTTPStatus( $iCode )
		{
			if( !headers_sent() )
			{
				header( " ", true, $iCode );
				
				return( true );
			}
			
			return( false );
			
		} // SetHTTPStatus()
		
		
		/**
		 * This specialized method does two things: it attempts to set the HTTP status code,
		 * 400 by default, to inform the web browser that there was an error, and second, 
		 * echoes the supplied error message to the browser, which could be a simple string or
		 * a JSON object.         		 
		 *
		 * @argument string The error message to output
		 * @argument int The response code to send to the browser, default: 400
		 * @returns void                  		 
		 */		 		 		
		public function AjaxError( $sError, $iResponseCode = 400 )
		{
			$this->SetHTTPStatus( $iResponseCode );
			
			echo $sError;
			
		} // AjaxError()
		
		
		/**
		 * This method redirects the browser to the specified URL. The second argument controls
		 * whether or not the 301 HTTP response code is used to signal a permanent redirect. Using
		 * this response code enable the user to hit refresh afterwards without resubmitting any
		 * form data from the original request.
		 * 
		 * If headers have already been sent to the browser, this method will return false and will
		 * not call the redirect. Otherwise this method will always return true.                                            		 
		 *
		 * @argument string The URL to redirect to 
		 * @argument bool True to signal a permanent redirect, false to not set the HTTP response code		 
		 * @returns bool True if the redirect was sucessfull, false otherwise		 
		 */	
		public function RedirectTo( $sURL, $bPermanentRedirect = true )
		{
            if( !headers_sent() )
            {
                header( "Location: {$sURL}", true, $bPermanentRedirect ? 301 : null );
                
                return( true );
            }
            
            return( false );
            
		} // RedirectTo()
		
		
		/**
		 * Sets the view file that should be loaded at the end of the request. This method does not
		 * check to ensure that the file specified actually exists. It is up to the code that loads
		 * the view file to do this (normally the Dispatcher class).         		 
		 * 		 
		 * @argument string The file name of the view file that should be loaded.
		 * @returns void
		 */	
		public function SetView( $sView )
		{
			$this->sView = $sView;
		
		} // SetView()
		
		
		/**
		 * Sets a data variable that can be used by the view file. Supplying the name and value
		 * of the variable, before loading the view file, these variables will be extracted and
		 * available in the view file for processing and/or display.
		 * 
		 * If the supplied variable already exists, it will be overwritten.                           		 
		 *
		 * @argument string The name of the variable to set
		 * @argument mixed The value of the variable to set         		 
		 * @returns void
		 */	
		public function SetData( $sName, $sValue )
		{
			$this->aData[ $sName ] = $sValue;
			
		} // SetData()
		
		
		/**
		 * Sets a session variable called flash with the supplied message. This can be used on a
		 * redirect to display a success message (in conjunction with the RedirectTo() method).		 
		 *
		 * If a flash message is already set, it will be overwritten on subsequent calls.
		 *         		 
		 * @argument string The message to set in the flash session variable
		 * @returns void		 
		 */	
		public function SetFlash( $sMessage )
		{
			$_SESSION[ "flash" ] = $sMessage;
			
		} // SetFlash()
		
		
		/**
		 * Retrieves any flash message stored in the flash session variable, if any. See the
		 * SetFlash() method.		 
		 *
		 * @returns string The flash message, if any, stored in the session
		 */	
		public function GetFlash()
		{
			$sFlash = isset( $_SESSION[ "flash" ] ) ? $_SESSION[ "flash" ] : "";
			
			unset( $_SESSION[ "flash" ] );
			
			return( $sFlash );
			
		} // GetFlash()
	
	} // Controller()

?>
