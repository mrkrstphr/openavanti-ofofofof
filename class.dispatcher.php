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
	 * Dispatcher to route URI request to appropriate controller / method, and loads view files
	 * based on instructions from the controller, passing data setup by the controller from the
	 * controller to the view file.     	 
	 *
	 * @category	Database
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/dispatcher
	 */
	class Dispatcher
	{
		private $aRoutes = array();
		private $bRequireViewFiles = true;
		
		private $s404ViewFile = "404.php";
		
		private $x404Callback = null;
		
		private $sHeaderFile = "header.php";
		private $sFooterFile = "footer.php";
		
		
		/**
		 * Toggles whether or not the controller should load view files (header, the view setup
		 * by the controller, and the footer view file.		 	 
		 * 
		 * @argument bool True if views should be required, false otherwise		 
		 * @returns void
		 */
		public function RequireViewFiles( $bRequireViewFiles )
		{
			$this->bRequireViewFiles = $bRequireViewFiles;
			
		} // RequireViewFiles()
		
		
		/**
		 * Sets the name of the view file to load in the event of a 404 error. By default, if no
		 * view file is specified, the file the dispatcher will attempt to load is "404.php". The
		 * include path will be used to search for this file.         		 
		 * 
		 * @argument string The file name of the view file to load in the event of a 404 error		 
		 * @returns void
		 */
		public function Set404View( $sView )
		{
			$this->s404ViewFile = $sView;
			
		} // Set404View()
		
		
		/**
		 * Sets the name of the header file to load before loading the controller-specified view
		 * file. The default file loaded for the header is "header.php." The purpose of this method 
		 * is to override the default.
		 * 
		 * Passing null or an empty string, or supplying no argument at all, to this method will 
		 * cause the dispatcher to not load any header view file at all.
		 * 
		 * @argument string The name of the file to load as the header, default ""
		 * @returns void
		 */
		public function SetHeaderView( $sView = "" )
		{
			$this->sHeaderFile = $sView;
			
		} // SetHeaderView()
		
		
		/**
		 * Sets the name of the footer file to load after loading the controller-specified view
		 * file. The default file loaded for the footer is "footer.php." The purpose of this method 
		 * is to override the default.
		 * 
		 * Passing null or an empty string, or supplying no argument at all, to this method will 
		 * cause the dispatcher to not load any footer view file at all.
		 * 
		 * @argument string The name of the file to load as the footer, default ""
		 * @returns void
		 */
		public function SetFooterView( $sView = "" )
		{
			$this->sFooterFile = $sView;
			
		} // SetFooterView()
		
		
		/**
		 * Allows the specification of a callback method to invoke upon a 404 error, instead of 
		 * requiring the 404 view file. If this callback is not callable, nothing will happen 
		 * on a 404 error.         	 
		 * 
		 * @argument callback The callback to invoke upon a 404 error		 
		 * @returns void
		 */
		public function Set404Handler( $xCallback )
		{
			$this->x404Callback = $xCallback;
			
		} // Set404Handler()


		/**
		 * Sets up a custom route to match a URI against when dispatching the URI. A custom route
		 * is simply run by running a preg_match against the URI, and rewriting it to the 
		 * replacement on a valid match using preg_replace. 
		 * 
		 * @argument string The pattern to match against
		 * @argument string The string to rewrite to
		 * @returns void
		 */
        public function AddRoute( $sPattern, $sReplacement )
        {
            $this->aRoutes[] = array(
                "pattern" => $sPattern,
                "replace" => $sReplacement
            );
        
        } // AddRoute()
	
	
		/**
		 * Routes the specified request to an associated controller and action (class and method). 
		 * Loads any specified view file stored in the controller and passes along any data stored
		 * in the controller. 
		 * 
		 * This method checks for custom routes first (see AddRoute()), before checking for standard
		 * routes. A standard route is a UI formed as follows /[controllername]/[methodname] where
		 * [controllername] is the name of the controller, without the word "controller"
		 * 
		 * A standard controller is named like: ExampleController. To invoke the index method of 
		 * this controller, one would navigate to /example/index.
		 * 
		 * The data loaded via the controller's SetData() method is exploded and available for the
		 * view file.                           		 
		 * 
		 * @argument string The current request URI
		 * @returns void
		 */
		public function Connect( $sRequest )
		{
			$oRequest = new Request();
			$oRequest->sURI = $sRequest;
			
			$sController = "";
			$sAction = "";
			$aArguments = array();
			
			// Load an empty controller. This may be replaced if we found a controller through a route.
			
			$oRequest->oController = new Controller();
			
			// Loop each stored route and attempt to find a match to the URI:
			
			foreach( $this->aRoutes as $aRoute )
			{				
				if( preg_match( $aRoute[ "pattern" ], $sRequest ) != 0 )
				{
					$sRequest = preg_replace( $aRoute[ "pattern" ], $aRoute[ "replace" ], $sRequest );
				}
			}
			
			if( substr( $sRequest, 0, 1 ) == "/" )
			{
				$sRequest = substr( $sRequest, 1 );
			}
			
			$oRequest->sRewrittenURI = $sRequest;
			
			
			// Explode the request on /
			$aRequest = explode( "/", $sRequest );
			
			// Store this as the last request:
			$_SESSION[ "last-request" ] = $aRequest;
			
			$oRequest->sControllerName = count( $aRequest ) > 0 ? 
				str_replace( "-", "_", array_shift( $aRequest ) ) . "Controller" : "";
			
			$oRequest->sAction = count( $aRequest ) > 0 ? array_shift( $aRequest ) : "";
			$oRequest->aArguments = !empty( $aRequest ) ? $aRequest : array();
				
			
			// If we've found a controller and the class exists:
			if( !empty( $oRequest->sControllerName ) && 
				class_exists( $oRequest->sControllerName, true ) )
			{
				// Replace our empty controller with the routed one:				
				$oRequest->oController = new $oRequest->sControllerName();
				
				// Attempt to invoke an action on this controller: 				
				$this->InvokeAction( $oRequest ); //->oController, $sAction, $aArguments );
			}
			else
			{
				// If we can't find the controller, we must throw a 404 error:
				$oRequest->oController->Set404Error();
			}		
			
			// Continue on with the view loader method which will put the appropriate presentation
			// on the screen:
			
			$this->LoadView( $oRequest );
		
			return( $oRequest );
		
		} // Connect()
		
		
		/**
		 * Determines whether or not the current HTTP request came via AJAX.	 		 		 		 		 		 
		 * 
		 * @returns boolean True of the request is via AJAX, false otherwise 
		 */
		public static function IsAjaxRequest()
		{
			return( isset( $_SERVER[ "HTTP_X_REQUESTED_WITH" ] ) );
			
		} // IsAjaxRequest()
		
		
		/**
		 * Called from Connect(), responsible for calling the method of the controller
		 * routed from the URI
		 * 
		 * @returns void
		 */
		protected function InvokeAction( Request &$oRequest )
		{
			// is_callable() is used over method_exists() in order to properly utilize __call()
			
			if( !empty( $oRequest->sAction ) && 
				is_callable( array( $oRequest->oController, $oRequest->sAction ) ) )
			{
				// Call $oController->$sAction() with arguments $aArguments:
				call_user_func_array( array( $oRequest->oController, $oRequest->sAction ), 
					$oRequest->aArguments );
			}
			else if( empty( $oRequest->sAction ) )
			{
				// Default to the index file:
				$oRequest->oController->index();
			}
			else
			{
				// Action is not callable, throw a 404 error:
				$oRequest->oController->Set404Error();
			}
			
			$oRequest->aLoadedData = &$oRequest->oController->aData;
		
		} // InvokeAction()
		
		
		/**
		 * Called from Connect(), responsible for loading any view file
		 * 
		 * @returns void
		 */
		protected function LoadView( Request &$oRequest )
		{				
			if( $oRequest->oController->Is404Error() )
			{
				$this->Invoke404Error();
			}
			else if( !empty( $oRequest->oController->sView ) )
			{
				if( $this->bRequireViewFiles )
				{
					extract( $oRequest->oController->aData );
			
					if( ( $sView = FileFunctions::FileExistsInPath( $oRequest->oController->sView ) ) === false )
					{
						return( $this->Invoke404Error() );
					}
			
					if( !self::IsAjaxRequest() && !empty( $this->sHeaderFile ) )
					{
						require( $this->sHeaderFile );
					}
				
					if( ( $sView = FileFunctions::FileExistsInPath( $oRequest->oController->sView ) ) !== false )
					{
                        $oRequest->sView = $sView;
						require( $sView );
					}
					else
					{
						$this->Invoke404Error();
					}
					
					if( !self::IsAjaxRequest() && !empty( $this->sFooterFile ) )
					{
						require( $this->sFooterFile );
					}
				}
			}
		
		} // LoadView()
		
		
		/**
		 * Called to handle a 404 error
		 * 
		 * @returns void
		 */
		protected function Invoke404Error()
		{
			if( isset( $this->x404Callback ) ) 
			{
				if( is_callable( $this->x404Callback ) )
				{					
					call_user_func_array( 
						$this->x404Callback, 
						array( 
							"/" . implode( "/", $_SESSION[ "last-request" ] ), 
							isset( $_SERVER[ "HTTP_REFERER" ] ) ? $_SERVER[ "HTTP_REFERER" ] : "" 
						) 
					);
				}
			}
			else if( isset( $this->s404ViewFile ) )
			{
				header( "HTTP/1.0 404 Not Found", true, 404 );
					
				if( ( $sView = FileFunctions::FileExistsInPath( $this->s404ViewFile ) ) !== false )
				{					
					if( !self::IsAjaxRequest() && isset( $this->sHeaderFile ) )
					{
						require( $this->sHeaderFile );
					}
					
					require( $sView );
					
					if( !self::IsAjaxRequest() && isset( $this->sFooterFile ) )
					{
						require( $this->sFooterFile );
					}
				}
			}
		
		} // Invoke404Error()
		
	} // Dispatcher()

?>
