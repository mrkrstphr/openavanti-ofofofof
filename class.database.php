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
	 * Database interaction abstract class definition
	 *
	 * @category	Database
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/database
	 */
    abstract class Database
    {
        /** 
         * Class constants for query join types (used for CRUD operations)
         */                          
        const JoinTypeInner = "inner";
        const JoinTypeLeft = "left";
        
        /**
         * Defines the syntax for the join type constants
         */                 
        public static $aJoinTypes = array( 
            self::JoinTypeInner => "INNER JOIN",
            self::JoinTypeLeft => "LEFT JOIN"
        );
        
        /**
         * Protected variables for storing database profiles and connections
         */                 
		protected static $aProfiles = array();
		protected static $aDefaultProfile = array();
		
		protected static $aConnections = array();
		
		
		/**
		 * Adds a database profile to the list of known database profiles. These profiles contain
		 * connection information for the database, including driver, host, name, user and password.		  		 		 		 		 		 
		 * 
		 * @argument array The profile array with database connection information		 
		 * @returns void 
		 */	
		final public static function AddProfile( $aProfile )
		{
			self::ValidateProfile( $aProfile );
			
			if( !isset( $aProfile[ "host" ] ) )
			{
				$aProfile[ "host" ] = "localhost";
			}
			
			self::$aProfiles[ $aProfile[ "driver" ] . "_" . $aProfile[ "name" ] ] = $aProfile;
			
		} // AddProfile()
		
		
		/**
		 * Sets the default database connection profile to the one specified in the first argument. 
		 * The default profile is used to create or return a database connection by GetConnection() 
		 * when no connection is specified to that method.
		 * 
		 * @argument string The name of the profile to be used as the default database profile
		 * @returns void 
		 */	
		final public static function SetDefaultProfile( $sProfile )
		{
			if( !isset( self::$aProfiles[ $sProfile ] ) )
			{
				throw new DatabaseConnectionException( "Unknown database profile: {$sProfile}" );
			}
		
			self::$aDefaultProfile = self::$aProfiles[ $sProfile ];
		
		} // SetDefaultProfile()
		
		
		/**
		 * As the constructor of the Database class and all derived database drivers is protected,
		 * the database class cannot be instantiated directly. Instead, the GetConnection() method
		 * must be called, afterwhich a database driver object is returned. 

		 *	A database profile array may be specified to control which database is connected to,
		 * and with what driver. If no profile is passed to this method, it first checks to see
		 * if there is a default database profile set up. If so, it uses that, if not, it then
		 * checks to see if there is only one profile stored. If so, that profile is used. If none
		 * of these conditions are met, an exception is thrown. 		 		 		 		 		 
		 * 
		 * @argument array The profile array with database connection information. If not supplied,
		 * 		 and a profile is already loaded, that profile will be used. If no profile is 
		 * 		 supplied and more than one profile has been loaded, an exception is thrown.		 		 	 
		 * @returns Database A database object; the type depends on the database driver being used. 
		 * 		 This object contains an active connection to the database.		 
		 */	
		final public static function GetConnection( $aProfile = array() )
		{
			if( !empty( $aProfile ) )
			{
				self::ValidateProfile( $aProfile );
				
				self::AddProfile( $aProfile );
			}
			else if( !empty( self::$aDefaultProfile ) )
			{
				$aProfile = self::$aDefaultProfile;
			}
			else if( empty( $aProfile ) && count( self::$aProfiles ) != 1 )
			{
				throw new Exception( "No profile specified for database connection" );
			}
			else
			{
				$aProfile = current( self::$aProfiles );
			}
			
			$sProfile = $aProfile[ "driver" ] . "_" . $aProfile[ "name" ];
			
			if( !isset( self::$aConnections[ $sProfile ] ) )
			{
				$sDatabaseDriver = $aProfile[ "driver" ] . "Database";
				
				self::$aConnections[ $sProfile ] = new $sDatabaseDriver( $aProfile );
			}
			
			
			return( self::$aConnections[ $sProfile ] );			
			
		} // GetConnection()
		
		
		/**
		 * Validates a database connection profile:
		 * 	1. Must have a driver specified
		 * 		a. Driver must reference a valid class [DriverName]Database
		 * 		b. [DriverName]Database must be a subclass of Database
		 * 	2. Must contain a database name.		 		 		 		 	 		 		 		 		 
		 * 
		 * Exceptions are thrown when any of the above criteria are not met describing the
		 * nature of the failed validation		 
		 *		 		 
		 * @argument array The profile array with database connection information to validate	 		 	 
		 * @returns Void	 
		 */
		private static function ValidateProfile( $aProfile )
		{
			if( !isset( $aProfile[ "driver" ] ) )
			{
				throw new Exception( "No database driver specified in database profile" );
			}
			
			if( !isset( $aProfile[ "name" ] ) )
			{
				throw new Exception( "No database name specified in database profile" );
			}
			
			$sDriver = $aProfile[ "driver" ];
			
			if( !class_exists( "{$sDriver}Database", true ) )
			{
				throw new Exception( "Unknown database driver specified: " . $aProfile[ "driver" ] );
			}
			
			if( !is_subclass_of( "{$sDriver}Database", "Database" ) )
			{
				throw new Exception( "Database driver does not properly extend the Database class." );
			}
			
		} // ValidateProfile()
		

		/**
		 * Queries the database using the supplied SQL query.
		 * 
		 * @argument string The SQL query to execute
		 * @returns	ResultSet A ResultSet object containing the results of the database query
		 */
		abstract public function Query( $sSQL );
		
		
		/**
		 * Pulls the next record from specified database resource and returns it as an object.
		 *         		
		 * @argument resource The database connection resource to pull the next record from
		 * @returns object The next record from the database, or null if there are no more records
		 */		 		 
		abstract public function PullNextResult( &$rResult );
		
		
		/**
		 * Returns the number of results from the last query performed on the specified database
		 * resource object.		 
		 *         		
		 * @argument resource The database connection resource
		 * @returns int The number of rows in the specified database resource
		 */	
		abstract public function CountFromResult( &$rResult );
		
		
		/**
		 * Attempts to return the internal pointer of the specified database resource to the
		 * first row. 
		 * 
		 * @argument resource The database connection resource to pull the next record from
		 * @returns bool True if the operation was successful, false otherwise                           		 
		 */
		abstract public function ResetResult( &$rResult );
		

		/**
		 * The Begin() method begins a database transaction which persists until either Commit() or 
		 * Rollback() is called, or the request ends. If Commit() is not called before the end of the 
		 * request, the database transaction will automatically roll back.
		 *		 
		 * @returns void
		 */
		abstract public function Begin();
		

		/**
		 * The Commit() method commits a database transaction (assuming one was started with 
		 * Begin()). If Commit() is not called before the end of the request, the database 
		 * transaction will automatically roll back.
		 * 
		 * @returns void		 		 
		 */
		abstract public function Commit();
		

		/**
		 * The Rollback() method rolls back a database transaction (assuming one was started with 
		 * Begin()). The database transaction is automatically rolled back if Commit() is not called.
		 * 
		 * @returns void		 		 
		 */
		abstract public function Rollback();
		

		/**
		 * Returns the last database error, if any.
		 * 
		 * @returns string A string representation of the last error		 		 
		 */
		abstract public function GetLastError();
		

		/**
		 * The SetCacheDirectory() method stores which directory should be used to load and store 
		 * database schema cache files. If the directory does not exist, an exception will be thrown.
		 * Setting the cache directory is useless unless schema caching is turned on using 
		 * CacheSchemas().
		 * 
		 * @argument string The absolute path to the directory in the system to store and read cached 
		 * 		 database schema files.
		 * @returns void		 		 
		 */        
		abstract public function SetCacheDirectory( $sDirectoryName );
		

		/**
		 * The CacheSchemas() method toggles whether or not database schemas discovered through the 
		 * GetSchema(), GetTableColumns(), GetTableForeignKeys() and GetTablePrimaryKey() methods 
		 * should be cached, and also whether or not those methods will pull their information from a 
		 * cache, if available.
		 * 
		 * @argument boolean Toggles whether or not to cache discovered database schemas
		 * @returns void		 
		 */
		abstract public function CacheSchemas( $bEnable );
		

		/**
		 * Returns the PHP native database connection resource.
		 * 
		 * @returns resource A database connection resource.
		 */
		abstract public function GetResource();
		

		/**
		 * Returns a database-safe formatted representation of the supplied data, based on the 
		 * supplied data type.
		 * 
		 * @argument string The data type of the supplied value.
		 * @argument string The value to be formatted into a database-safe representation.
		 * @returns string A string of the formatted value supplied.	 		 		 		 
		 */
		abstract public function FormatData( $sType, $sValue );
		
		
		/**
		 * This method returns all tables for the database the class is currently connected to.
		 *		 
		 * @returns array Returns an array of all tables in the form of table_name => table_name.
		 */	
		abstract public function GetTables();
		
		
		/**
		 * This method returns all databases on the database server. 
		 *		 
		 * @returns array An array of all databases on the database server in the formation of 
		 * 		 database_name => database_name
		 */	
		abstract public function GetDatabases();
		

		/**
		 * Collects information about the schema for the specified table, including information on 
		 * columns (name, datatype), primary keys and foreign keys (relationships to other tables).
		 * 
		 * This method stores its information the static variable $aSchemas so that if the data is 
		 * required again, the database does not have to be consoluted.
		 * 
		 * If schema caching is on, this method can pull data from a schema cache. 
		 * 
		 * @argument string The name of the table for the requested schema
		 * @returns array An array of schema information for the specified table	 
		 */		
		abstract public function GetSchema( $sTableName );
		

		/**
		 * Returns an array of columns that belong to the specified table.
		 * 
		 * This method stores its information the static variable $aSchemas so that if the data is 
		 * required again, the database does not have to be consoluted.
		 * 
		 * If schema caching is on, this method can pull data from a schema cache. 
		 * 
		 * @argument string The name of the table for the requested columns
		 * @returns array An array of columns that belong to the specified table
		 */
		abstract public function GetTableColumns( $sTableName );
		

		/**
		 * Returns an array of columns that belong to the primary key for the specified table.
		 * 
		 * This method stores its information the static variable $aSchemas so that if the data is 
		 * required again, the database does not have to be consoluted.
		 * 
		 * If schema caching is on, this method can pull data from a schema cache. 
		 * 
		 * @argument string The name of the table for the requested primary key
		 * @returns array An array of columns that belong to the primary key for the specified table
		 */
		abstract public function GetTablePrimaryKey( $sTableName );
		

		/**
		 * Returns an array of relationships (foreign keys) for the specified table.
		 * 
		 * This method stores its information the static variable $aSchemas so that if the data is 
		 * required again, the database does not have to be consoluted.
		 * 
		 * If schema caching is on, this method can pull data from a schema cache.
		 * 
		 * @argument string The name of the table for the requested relationships
		 * @returns array An array of relationships for the specified table
		 */
		abstract public function GetTableForeignKeys( $sTableName );
		

		/**
		 * Returns the data type of the specified column in the specified table.
		 * 
		 * @argument string The name of the table that the desired column belongs to
		 * @argument string The name of the column that is desired to know the type of
		 * @returns string The data type of the column, if one is found, or null.
		 */
		abstract public function GetColumnType( $sTableName, $sFieldName );
		

		/**
		 * Determines whether the specified table exists in the current database.
		 * 
		 * @argument string The name of the table to determine existence
		 * @returns bool True or false, depending on whether the table exists.	 		 		 
		 */		
		abstract public function TableExists( $sTableName );

    } // Database()

?>
