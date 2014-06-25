<?php

	/**
	 *
	 *
	 */	 	 	
	class FileNotFoundException extends Exception{};
	
	
	/**
	 *
	 *
	 */
	class ExtensionNotInstalledException extends Exception{};
	
	
	/**
	 *
	 *
	 */
	class QueryFailedException extends Exception{};
	
	
	/**
	 *
	 *
	 */
	class DatabaseConnectionException extends Exception{};

	/**
	 *
	 *
	 */
	interface Throwable
	{
		// nothing. implementing Throwable is simply a means of loading the exception classes
		
		// something may be added here eventually
	
	}; // Throwable()

?>
