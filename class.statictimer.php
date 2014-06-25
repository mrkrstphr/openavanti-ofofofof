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
	 * A simple timer for various purposes
	 *
	 * @category	Controller
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/statictimer
	 */
	class StaticTimer
	{
		private static $iStart = 0;
		private static $iEnd = 0;
		
		
		/**
		 * Starts the timer -- if a timer was already previously started, this action
		 * will overwrite the start time		 	 		 		 		 		 		 		 
		 * 
		 * @returns void 
		 */
		public static function Start()
		{
			self::Update( self::$iStart );
			
		} // Start()
		
		
		/**
		 * Return the amount of time elapsed since starting the timer. If the timer was never
		 * started, this will return 0. This does not actually stop the timer. For timing a series 
		 * of events, Stop() can be called multiple time to get increments in between various steps		 		 		  	 		 		 		 		 		 		 
		 * 
		 * @returns double The amount of time that has passed since starting 
		 */
		public static function Stop()
		{
			self::Update( self::$iEnd );
			
			return( self::$iStart == 0 ? self::$iStart : ( self::$iEnd - self::$iStart ) );
			
		} // Stop()
		
		
		/**
		 * Internally used to update the supplied iVar with the current micro time.		 		 		  	 		 		 		 		 		 		 
		 * 
		 * @returns void
		 */
		protected static function Update( &$iVar )
		{			
			$iVar = microtime( true );
			
		} // Update()
	
	}; // StaticTimer()

?>
