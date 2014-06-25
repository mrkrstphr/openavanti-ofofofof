<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	Database, ResultSet, StringFunctions
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 *
 */


	/**
	 * Database Interaction Class (PostgreSQL)
	 *
	 * @category	Database
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/postgresdatabase
	 */
	class PostgresDatabase extends Database
	{
		private $hDatabase = null;
        
      	protected static $aSchemas = array();
        
      	private static $sCacheDirectory = "";
      	private static $bCacheSchemas = false;
		

		/**
		 * The constructor sets up a new connection to the PostgreSQL database. This method is
		 * protected, and can only be called from within the class, normally through the 
		 * GetConnection() method. This helps support the singleton methodology.
		 * 
		 * @argument array The database profile array containing connection information		 		 		 		 
		 */
		protected function __construct( $aProfile )
      	{
            $sString = "";
	      	
	      	if( isset( $aProfile[ "host" ] ) )
	      	{
	      		$sString .= " host=" . $aProfile[ "host" ] . " ";
	      	}
      
			$sString .= " dbname=" . $aProfile[ "name" ] . " ";
      	
	      	if( isset( $aProfile[ "user" ] ) )
	      	{
	      		$sString .= " user=" . $aProfile[ "user" ] . " ";
	      	}
	      	
	      	if( isset( $aProfile[ "password" ] ) )
	      	{
	      		$sString .= " password=" . $aProfile[ "password" ] . " ";
	      	}
			
			$this->hDatabase = pg_connect( $sString );
			
			if( !$this->hDatabase )
			{
				throw new DatabaseConnectionException( "Failed to connect to Postgres server: " . 
					$aProfile[ "host" ] . "." . $aProfile[ "name" ] );
			}
			
		} // __construct()
		

		/**
		 * Queries the PostgreSQL database using the supplied SQL query.
		 * 
		 * @argument string The PostgreSQL query to execute
		 * @returns string A ResultSet object containing the results of the database query	 		 		 
		 */
		public function Query( $sSQL )
		{
			$rResult = @pg_query( $this->hDatabase, $sSQL );
			
			if( !$rResult )
			{
				return( null );
			}
			
			return( new ResultSet( $this, $rResult ) );
		
		} // Query()
		
		
		/**
		 * Pulls the next record from specified database resource and returns it as an object.
		 *         		
		 * @argument resource The database connection resource to pull the next record from
		 * @returns object The next record from the database, or null if there are no more records
		 */		 
		public function PullNextResult( &$rResult )
		{
			if( !is_null( $rResult ) )
			{                
				return( pg_fetch_object( $rResult ) );
			}
			else
			{
				return( null );
			}
			
		} // PullNextResult()
		
		
		/**
		 * Returns the number of results from the last query performed on the specified database
		 * resource object.		 
		 *         		
		 * @argument resource The database connection resource
		 * @returns int The number of rows in the specified database resource
		 */	
		public function CountFromResult( &$rResult )
		{
			if( $rResult )
			{
				return( pg_num_rows( $rResult ) );
			}
			else
			{
			 	return( 0 );
			}
			
		} // CountFromResult()
		
		
		/**
		 * Attempts to return the internal pointer of the specified database resource to the
		 * first row. 
		 * 
		 * @argument resource The database connection resource to pull the next record from
		 * @returns bool True if the operation was successful, false otherwise                           		 
		 */
		public function ResetResult( &$rResult )
		{
			return( @pg_result_seek( $rResult, 0 ) );
		
		} // ResetResult()
		

		/**
		 * The Begin() method begins a database transaction which persists until either Commit() or 
		 * Rollback() is called, or the request ends. If Commit() is not called before the end of the 
		 * request, the database transaction will automatically roll back.
		 * 
		 * @returns void		 		 
		 */
		public function Begin()
		{
			$rResult = @pg_query( $this->hDatabase, "BEGIN" ) or
				trigger_error( "Failed to begin transaction", E_USER_ERROR );

			return( $rResult ? true : false );

		} // Begin()
		

		/**
		 * The Commit() method commits a database transaction (assuming one was started with 
		 * Begin()). If Commit() is not called before the end of the request, the database 
		 * transaction will automatically roll back.
		 * 
		 * @returns void		 
		 */
		public function Commit()
		{
			$rResult = @pg_query( $this->hDatabase, "COMMIT" ) or
				trigger_error( "Failed to commit transaction", E_USER_ERROR );
		
			return( $rResult ? true : false );
		
		} // Commit()
		

		/**
		 * The Rollback() method rolls back a database transaction (assuming one was started with 
		 * Begin()). The database transaction is automatically rolled back if Commit() is not called.
		 * 		 
		 * @returns void		 
		 */
		public function Rollback()
		{
			$rResult = @pg_query( $this->hDatabase, "ROLLBACK" ) or
				trigger_error( "Failed to rollback transaction", E_USER_ERROR );
		
			return( $rResult ? true : false );
		
		} // Rollback()
		

		/**
		 * Advances the value of the supplied sequence and returns the new value.
		 * 
		 * @argument string The name of the database sequence to advance and get the current value of
		 * @returns integer An integer representation of the next value of the sequence
		 */
		public function NextVal( $sSequenceName )
		{
			$sSQL = "SELECT
				NEXTVAL( '{$sSequenceName}' )
			AS
				next_val";
            
        	$rResult = @pg_query( $this->hDatabase, $sSQL ) or
            	trigger_error( "Failed to query sequence value: " . $this->getLastError(), 
				 	E_USER_ERROR );
            
     		$oRecord = pg_fetch_object( $rResult );
     	
	     	if( $oRecord )
	     	{
	     		return( $oRecord->next_val );
	     	}
     	
     		return( null );
		
		} // NextVal()
		

		/**
		 * Gets the current value of the specified sequence.
		 * 
		 * This method does not alter the current value of the sequence.
		 * 
		 * This method will only work if the value of the sequence has already been altered during 
		 * the current database transaction; meaning that you must call NextVal() or SerialNextVal() 
		 * prior to using this method.
		 *  
		 * @argument string The name of the database sequence to get the current value of
		 * @returns integer An integer representation of the current value of the sequence.
		 */
		public function CurrVal( $sSequenceName )
		{
			$sSQL = "SELECT
				CURRVAL( '{$sSequence}' )
			AS
				current_value";
            
	        $rResult = @pg_query( $this->hDatabase, $sSQL ) or
	            trigger_error( "Failed to query sequence value: " . $this->getLastError(), 
					 	E_USER_ERROR );
	            
	     	$oRecord = pg_fetch_object( $rResult );
	     	
	     	if( $oRecord )
	     	{
	     		return( $oRecord->current_value );
	     	}
	     	
	     	return( null );
		
		} // CurrVal()
		

		/**
		 * Gets the current value of the specified sequence by the name of the table and the name of 
		 * the database column. This will only work if a sequence is defined as the default value of 
		 * a table column.
		 * 
		 * This method does not alter the current value of the sequence.
		 * 
		 * This method will only work if the value of the sequence has already been altered during 
		 * the current database transaction; meaning that you must call NextVal() or SerialNextVal() 
		 * prior to using this method.
		 * 
		 * @argument string The name of the database table that holds the column with the sequence as 
		 * 		 a default value
		 * @argument string The name of the database table column with the sequence as a default value
		 * @returns integer An integer representation of the current value of the sequence
		 */
		public function SerialCurrVal( $sTableName, $sColumnName )
		{
			$sSQL = "SELECT
				CURRVAL(
					PG_GET_SERIAL_SEQUENCE(
						'{$sTableName}', 
						'{$sColumnName}'
					)
				)
			AS
				current_value";
            
			$rResult = @pg_query( $this->hDatabase, $sSQL ) or
				trigger_error( "Failed to query sequence value: " . $this->getLastError(), 
				E_USER_ERROR );
	            
			$oRecord = pg_fetch_object( $rResult );
	     	
			if( $oRecord )
	     	{
	     		return( $oRecord->current_value );
	     	}
	     	
	     	return( null );
		
		} // SerialCurrVal()
		

		/**
		 * Advances the value of the supplied sequence and returns the new value by the name of the 
		 * table and the name of the column. This will only work if a sequence is defined as the 
		 * default value of a table column.
		 * 
		 * @argument string The name of the database table that holds the column with the sequence as 
		 * 		 a default value
		 * @argument string The name of the database table column with the sequence as a default value
		 * @returns integer An integer representation of the next value of the sequence	 		 		 
		 */
		public function SerialNextVal( $sTableName, $sColumnName )
		{
			$sSQL = "SELECT
				NEXTVAL(
					PG_GET_SERIAL_SEQUENCE(
						'{$sTableName}', 
						'{$sColumnName}'
					)
				)
			AS
				next_value";
            
	      $rResult = @pg_query( $this->hDatabase, $sSQL ) or
	         trigger_error( "Failed to query sequence value: " . $this->getLastError(), 
					E_USER_ERROR );
	            
	     	$oRecord = pg_fetch_object( $rResult );
	     	
	     	if( $oRecord )
	     	{
	     		return( $oRecord->next_value );
	     	}
	     	
	     	return( null );
		
		} // SerialNextVal()
		

		/**
		 * Returns the last PostgreSQL database error, if any.
		 * 
		 * @returns string A string representation of the last PostgreSQL error		 		 
		 */
		public function GetLastError()
		{
			return( pg_last_error() );
		
		} // GetLastError()
		

		/**
		 * The SetCacheDirectory() method stores which directory should be used to load and store 
		 * database schema cache files. If the directory does not exist, an exception will be thrown.
		 * 
		 * Setting the cache directory is useless unless schema caching is turned on using 
		 * CacheSchemas().
		 * 
		 * Schema caching is primarily used by the CRUD object, which analyzes database schemas to 
		 * automate database operations. 
		 * 
		 * @argument The absolute path to the directory in the system to store and read cached 
		 * 		 database schema files
		 * @returns void 		 		 		 
		 */
		public function SetCacheDirectory( $sDirectoryName )
		{
			self::$sCacheDirectory = $sDirectoryName;
		
		} // SetCacheDirectory()
		

		/**
		 * The CacheSchemas() method toggles whether or not database schemas discovered through the 
		 * GetSchema(), GetTableColumns(), GetTableForeignKeys() and GetTablePrimaryKey() methods 
		 * should be cached, and also whether or not those methods will pull their information from a 
		 * cache, if available.
		 * 
		 * Attempting to cache schemas without properly setting the cache directory using 
		 * SetCacheDirectory(). If caching is attempted without setting the directory, an exception 
		 * will be thrown.
		 * 
		 * Schema caching is primarily used by the CRUD object, which analyzes database schemas to 
		 * automate database operations. 
		 * 
		 * @argument boolean Toggles whether or not to cache discovered database schemas
		 * @returns void		 
		 */
		public function CacheSchemas( $bEnable )
		{
			self::$bCacheSchemas = $bEnable;

		} // CacheSchemas()
		

		/**
		 * Returns the native PHP database resource
		 * 
		 * @returns resource The native PHP database resource		 		 
		 */
		public function GetResource()
		{
			return( $this->hDatabase );
		
		} // GetResource()
		

		/**
		 * Returns a database-safe formatted representation of the supplied data, based on the 
		 * supplied data type.
		 * 
		 * 1. If the supplied data is empty and does not equal zero, this method returns NULL.
		 * 2. If the data type is of text, varchar, timestamp, or bool, this method returns that 
		 * 		 value surrounded in single quotes.
		 * 
		 * @argument string The data type of the supplied value
		 * @argument string The value to be formatted into a database-safe representation
		 * @returns string A string of the formatted value supplied	 		 		 		 
		 */
		public function FormatData( $sType, $sValue )
		{
            $aQuoted_Types = array( "/text/", "/character varying/", "/date/", 
                "/timestamp/", "/time without time zone/" );
            
            if( strlen( $sValue ) == 0 )
            {
                return( "NULL" );
            }
            
            if( preg_replace( $aQuoted_Types, "", $sType ) != $sType )
            {
                return( "'" . addslashes( $sValue ) . "'" );
            }
            else if( strpos( $sType, "bool" ) !== false )
            {
                if( $sValue === true || strtolower( $sValue ) == "true" || 
                    strtolower( $sValue ) == "t" )
                {
                    return( "true" );
                }
                else
                {
                    return( "false" );
                }
            }
            
            return( $sValue );
		
		} // FormatData()


		/**
		 * This method returns all databases on the database server. 
		 *		 
		 * @returns array An array of all databases on the database server in the formation of 
		 * 		 database_name => database_name
		 */		 
		public function GetDatabases()
		{
			$sSQL = "SELECT
				datname
			FROM
				pg_database
			ORDER BY
				datname";
				
			if( !( $oDatabases = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}
			
			$aDatabases = array();
			
			foreach( $oDatabases as $oDatabase )
			{
				$aDatabases[ $oDatabase->datname ] = $oDatabase->datname;
			}
			
			return( $aDatabases );
		
		} // GetDatabases()
		
		
		/**
		 * This method returns all tables for the database the class is currently connected to.
		 *		 
		 * @returns array Returns an array of all tables in the form of table_name => table_name.
		 */	
		public function GetTables()
		{        		
			$aTables = array();

			$sSQL = "SELECT 
				pt.tablename, 
				pp.typrelid 
			FROM 
				pg_tables AS pt 
			INNER JOIN 
				pg_type AS pp ON pp.typname = pt.tablename 
			WHERE
				pt.tablename NOT LIKE 'pg_%' 
			AND
				pt.tablename NOT LIKE 'sql_%'";
			
			if( !( $oTables = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}

			$aTables = array();

			foreach( $oTables as $oTable ) 
			{
				$aTables[ $oTable->typrelid ] = $oTable->tablename;
			}

			return( $aTables );
		
		} // GetTables()
		

		/**
		 * Collects all fields/columns in the specified database table, as well as data type
		 * and key information.
		 * 		 
		 */
		public function GetSchema( $sTableName )
		{		
			$sCacheFile = self::$sCacheDirectory . "/" . md5( $sTableName );
			
			if( self::$bCacheSchemas && !isset( self::$aSchemas[ $sTableName ] ) && Cache::Exists( $sCacheFile ) )
			{
				$oCache = new Cache( $sCacheFile );
				self::$aSchemas[ $sTableName ] = unserialize( $oCache );	
			}
			else
			{			 
                $this->GetTableColumns( $sTableName );
                $this->GetTablePrimaryKey( $sTableName );
                $this->GetTableForeignKeys( $sTableName );
			
                if( self::$bCacheSchemas )
                {
                    $oCache = new Cache();
                    $oCache->Save( $sCacheFile, serialize( self::$aSchemas[ $sTableName ] ), true );
                }
			}
			
			return( self::$aSchemas[ $sTableName ] );
		
		} // GetSchema()
		

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
		public function GetTableColumns( $sTableName )
		{
			if( isset( self::$aSchemas[ $sTableName ][ "fields" ] ) )
			{
				return( self::$aSchemas[ $sTableName ][ "fields" ] );
			}
			
			$aFields = array();

			$sSQL = "SELECT 
				pa.attname, 
				pa.attnum,
				pat.typname,
				pa.atttypmod,
				pa.attnotnull,
				pg_get_expr( pad.adbin, pa.attrelid, true ) AS default_value,
				format_type( pa.atttypid, pa.atttypmod ) AS data_type
			FROM 
				pg_attribute AS pa 
			INNER JOIN 
				pg_type AS pt 
			ON 
				pt.typrelid = pa.attrelid 
			INNER JOIN  
				pg_type AS pat 
			ON 
				pat.typelem = pa.atttypid 
			LEFT JOIN
				pg_attrdef AS pad
			ON
				pad.adrelid = pa.attrelid
			AND
				pad.adnum = pa.attnum
			WHERE  
				pt.typname = '{$sTableName}' 
			AND 
				pa.attnum > 0 
			ORDER BY 
				pa.attnum";
				
			if( !( $oFields = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}
            
			foreach( $oFields as $iCount => $oField )
			{			
				// When dropping a column with PostgreSQL, you get a lovely .pg.dropped. column
				// in the PostgreSQL catalog
				
				if( strpos( $oField->attname, ".pg.dropped." ) !== false )
				{
					continue;
				}
				
				$aFields[ $oField->attname ] = array(
					"number" => $oField->attnum,
					"field" => $oField->attname, 
					"type" => $oField->data_type,
					"not-null" => $oField->attnotnull == "t",
					"default" => $oField->default_value
				);
				 
				if( $oField->typname == "_varchar" )
				{
					$aFields[ $oField->attname ][ "size" ] = $oField->atttypmod - 4;
				}
			}
			
			self::$aSchemas[ $sTableName ][ "fields" ] = $aFields;
 
			return( $aFields );
            
		} // GetTableColumns()
		

		/**
		 * Returns an array of columns that belong to the primary key for the specified table.
		 * This method stores its information the static variable $aSchemas so that if the data is 
		 * required again, the database does not have to be consoluted.
		 * 
		 * If schema caching is on, this method can pull data from a schema cache. 
		 * 
		 * @argument string The name of hte table for the requested primary key
		 * @returns An array of columns that belong to the primary key for the specified table                  		 
		 */
		public function GetTablePrimaryKey( $sTableName )
		{
			if( isset( self::$aSchemas[ $sTableName ][ "primary_key" ] ) )
			{
				return( self::$aSchemas[ $sTableName ][ "primary_key" ] );
			}
		
			$aLocalTable = $this->GetTableColumns( $sTableName );
			
			self::$aSchemas[ $sTableName ][ "primary_key" ] = array();
					
			$sSQL = "SELECT 
				pi.indkey
			FROM 
				pg_index AS pi 
			INNER JOIN
				pg_type AS pt 
			ON 
				pt.typrelid = pi.indrelid 
			WHERE 
				pt.typname = '{$sTableName}' 
			AND 
				pi.indisprimary = true";			
			
			if( !( $oPrimaryKeys = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}

			if( $oPrimaryKeys->Count() != 0 )
			{
				$oPrimaryKeys->Next();				
				$oPrimaryKey = $oPrimaryKeys->Current();
				
				$aIndexFields = explode( " ", $oPrimaryKey->indkey );
				
				foreach( $aIndexFields as $iField )
				{
					$aField = $this->GetColumnByNumber( $sTableName, $iField );
					
					self::$aSchemas[ $sTableName ][ "primary_key" ][] = 
						$aField[ "field" ];
				}
			}
	
			return( self::$aSchemas[ $sTableName ][ "primary_key" ] );
		
		} // GetTablePrimaryKey()
		

		/**
		 * Returns an array of relationships (foreign keys) for the specified table. 
		 * 
		 * This method stores its information the static variable $aSchemas so that if the data 
		 * is required again, the database does not have to be consoluted.
		 * 
		 * If schema caching is on, this method can pull data from a schema cache. 
		 * 
		 * @argument string The name of the table for the requested relationships
		 * @returns array An array of relationships for the table
		 */
		public function GetTableForeignKeys( $sTableName )
		{
			if( isset( self::$aSchemas[ $sTableName ][ "foreign_key" ] ) )
			{
				return( self::$aSchemas[ $sTableName ][ "foreign_key" ] );
			}
		
			//
			// This method needs to be cleaned up and consolidated
			//
			
			$aLocalTable = $this->GetTableColumns( $sTableName );
			
			self::$aSchemas[ $sTableName ][ "foreign_key" ] = array();
		
			$sSQL = "SELECT 
				rpt.typname,
				pc.confrelid,
				pc.conkey,
				pc.confkey
			FROM 
				pg_constraint AS pc 
			INNER JOIN 
				pg_type AS pt 
			ON 
				pt.typrelid = pc.conrelid 
			INNER JOIN
				pg_type AS rpt
			ON
				rpt.typrelid = confrelid
			WHERE
				pt.typname = '{$sTableName}'
			AND
				contype = 'f'
			AND
				confrelid IS NOT NULL";
				
			if( !( $oForeignKeys = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( "Failed on Query: " . $sSQL . "\n" . $this->GetLastError() );
			}
            
			$iCount = 0;
			
			foreach( $oForeignKeys as $oForeignKey )
			{				
				$aLocalFields = $aArray = explode( ",", 
					str_replace( array( "{", "}" ), "", $oForeignKey->conkey ) );
			
				$aForeignFields = $aArray = explode( ",", 
					str_replace( array( "{", "}" ), "", $oForeignKey->confkey ) );
			
		         	
				$aFields = $this->GetTableColumns( $oForeignKey->typname );
				
				foreach( $aForeignFields as $iIndex => $iField )
				{
					$aField = $this->GetColumnByNumber( $oForeignKey->typname, $iField );
					$aForeignFields[ $iIndex ] = $aField[ "field" ];
				}
				
				foreach( $aLocalFields as $iIndex => $iField )
				{
					$aField = $this->GetColumnByNumber( $sTableName, $iField );
					$aLocalFields[ $iIndex ] = $aField[ "field" ];
				}
         	
				// we currently do not handle references to multiple fields:

				$localField = current( $aLocalFields );

	         	$sName = substr( $localField, strlen( $localField ) - 3 ) == "_id" ? 
	         		substr( $localField, 0, strlen( $localField ) - 3 ) : $localField;
	         	
	         	$sName = StringFunctions::ToSingular( $sName );
	         	
	         	self::$aSchemas[ $sTableName ][ "foreign_key" ][ $sName ] = array(
	         		"table" => $oForeignKey->typname,
	         		"name" => $sName,
	         		"local" => $aLocalFields,
	         		"foreign" => $aForeignFields,
	         		"type" => "m-1",
	         		"dependency" => true
	         	);
	      	
	      		$iCount++;
			}
			
			// find tables that reference us:
					
			$sSQL = "SELECT 
				ptr.typname,
				pc.conrelid,
				pc.conkey,
				pc.confkey
			FROM 
				pg_constraint AS pc 
			INNER JOIN 
				pg_type AS pt 
			ON 
				pt.typrelid = pc.confrelid 
			INNER JOIN
				pg_type AS ptr
			ON
				ptr.typrelid = pc.conrelid	
			WHERE
				pt.typname = '{$sTableName}'
			AND
				contype = 'f'
			AND
				confrelid IS NOT NULL";
				
				
			if( !( $oForeignKeys = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}

			foreach( $oForeignKeys as $oForeignKey )
			{
				$aLocalFields = $aArray = explode( ",", 
					str_replace( array( "{", "}" ), "", $oForeignKey->confkey ) );
			
			 	$aForeignFields = $aArray = explode( ",", 
					str_replace( array( "{", "}" ), "", $oForeignKey->conkey ) );
			 	
			
				$this->GetSchema( $oForeignKey->typname );
				
				$aFields = $this->GetTableColumns( $oForeignKey->typname );
				
				foreach( $aForeignFields as $iIndex => $iField )
				{
					$aField = $this->GetColumnByNumber( $oForeignKey->typname, $iField );
					$aForeignFields[ $iIndex ] = $aField[ "field" ];
				}
				
				foreach( $aLocalFields as $iIndex => $iField )
				{
					$aField = $this->GetColumnByNumber( $sTableName, $iField );
					$aLocalFields[ $iIndex ] = $aField[ "field" ];
				}

				$localField = reset( $aLocalFields );
				$foreignField = reset( $aForeignFields );
				
				// if foreign_table.local_field == foreign_table.primary_key AND
				// if local_table.foreign_key == local_table.primary_key THEN
				//		Relationship = 1-1
				// end
				
				$aTmpForeignPrimaryKey = &self::$aSchemas[ $oForeignKey->typname ][ "primary_key" ];
				$aTmpLocalPrimaryKey = &self::$aSchemas[ $sTableName ][ "primary_key" ];
				
				$bForeignFieldIsPrimary = count( $aTmpForeignPrimaryKey ) == 1 &&
					reset( $aTmpForeignPrimaryKey ) == $foreignField;
				$bLocalFieldIsPrimary = count( $aTmpLocalPrimaryKey ) &&
					reset( $aTmpLocalPrimaryKey ) == $localField;
				$bForeignIsSingular = count( $aForeignFields ) == 1;
				
				$sType = "1-m";
				
				if( $bForeignFieldIsPrimary && $bLocalFieldIsPrimary && $bForeignIsSingular )
				{
					$sType = "1-1";
				}

				self::$aSchemas[ $sTableName ][ "foreign_key" ][ $oForeignKey->typname ] = array(
					"table" => $oForeignKey->typname,
					"name" => $oForeignKey->typname,
						"local" => $aLocalFields,
					"foreign" => $aForeignFields,
					"type" => $sType,
					"dependency" => false
				);
				
				$iCount++;
			}
			
			return( self::$aSchemas[ $sTableName ][ "foreign_key" ] );
		
		} // GetTableForeignKeys()
		

		/**
		 * This method determines if the specified tables primary key (or a single column from
		 * a compound primary key) references another table.		 
		 *
		 * @argument string The name of the table that the key exists on
		 * @argument string The column that is, or is part of, the primary key for the table         		 
		 * @returns boolean True if the primary key references another table, false otherwise         		 
		 */
		public function IsPrimaryKeyReference( $sTableName, $sColumnName )
		{
			$aForeignKeys = $this->GetTableForeignKeys( $sTableName );
						
			foreach( $aForeignKeys as $aForeignKey )
			{
				if( $aForeignKey[ "dependency" ] && reset( $aForeignKey[ "local" ] ) == $sColumnName )
				{
					return( true );
				}
			}
			
			return( false );
		
		} // IsPrimaryKeyReference()
		

		/**
		 * Returns the data type of the specified column in the specified table. 
		 * 
		 * @argument string The name of the table that the desired column belongs to 
		 * @argument string The name of the column that is desired to know the type of 
		 * @returns string The data type of the column, if one is found, or null.
		 */
		public function GetColumnType( $sTableName, $sFieldName )
		{
			$aFields = $this->GetTableColumns( $sTableName );
			
			foreach( $aFields as $aField )
			{
				if( $sFieldName == $aField[ "field" ] )
				{
					return( $aField[ "type" ] );
				}
			}
			
			return( null );
		
		} // GetColumnType()
		

		/**
		 * Determines whether the specified table exists in the current database.
		 * 
		 * This method first determines whether or not the table exists in the schemas array. If not, 
		 * it attempts to find the table in the PostgreSQL catalog. 
		 * 
		 * @argument string The name of the table to determine existence
		 * @returns boolean True or false, depending on whether the table exists		 	 
		 */
		public function TableExists( $sTableName )
		{
			if( isset( self::$aSchemas[ $sTableName ] ) )
			{
				return( true );
			}
			
			$sSQL = "SELECT
				1
			FROM
				pg_tables
			WHERE
				LOWER( tablename ) = '" . strtolower( addslashes( $sTableName ) ) . "'";
							
			if( !( $oResultSet = $this->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}
			
			return( $oResultSet->Count() );
		
		} // TableExists()
		

		/**
		 * Returns the name of the column at the specified position from the specified table. 
		 * This method is primarily interally as, in the PostgreSQL catalog, table references, 
		 * indexes, etc, are stored by column number in the catalog tables. 
		 * 
		 * @argument string The name of the table that the desired column belongs to 
		 * @argument int The column number from the table (from the PostgreSQL catalog) 
		 * @returns string The name of the column, if one is found, or null
		 */
		protected function GetColumnByNumber( $sTableName, $iColumnNumber )
		{
			foreach( self::$aSchemas[ $sTableName ][ "fields" ] as $aField )
			{
				if( $aField[ "number" ] == $iColumnNumber )
				{
					return( $aField );
				}
			}
		
			return( null );
		
		} // GetColumnByNumber()

    } // PostgresDatabase()

?>
