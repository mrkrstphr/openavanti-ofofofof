<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	Database, StringFunctions
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 *
 */
 
	/**
	 * Database abstraction layer implementing CRUD procedures
	 *
	 * @category	Database
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/crud
	 */
	class CRUD implements Iterator
	{
		protected $oDatabase = null;
		protected $sTableName = null;	
        	
		protected $oDataSet = null;
		protected $aData = array();
		
		
		/**
		 *  The constructor makes the necessary connection to the database (see Database::Construct) 
		 *  and attempts to load the schema of the specified table.
		 *  
		 *  If the second argument of oData is supplied, the constructor will attempt to load that 
		 *  data into the class for later saving.
		 * 
		 *  If there is a define defined called ENABLE_SCHEMA_CACHING, schema caching is turned on, 
		 *  allowing for faster subsequent page loads. 	 	 
		 * 		 
		 * @argument string The name of the database table
		 * @argument mixed An array or object of data to load into the CRUD object		 
		 * @returns void
		 */
		public function __construct( $sTableName, $oData = null )
		{
			// relies on there only being one database profile or a default profile set:
			$this->oDatabase = Database::GetConnection();

			$this->sTableName = $sTableName;
		
			// Get the schema for this table:
			$this->oDatabase->GetSchema( $this->sTableName );
			
			// Prepare the fields for this table for CRUD->column access:
			$this->PrepareColumns();

			// If data is supplied, load it, depending on data type:
			
			if( !is_null( $oData ) )
			{
				$this->Load( $oData );
			}

		} // __construct()
		
		
		/**
		 * Grabs all columns for this table and adds each as a key in the data array for
		 * this object		 
		 * 
		 * @returns void
		 */		 		 		 
		protected function PrepareColumns()
		{
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
			
			// Loop each column in the table and create a member variable for it:			
			foreach( $aColumns as $aColumn )
			{
				$this->aData[ $aColumn[ "field" ] ] = null;
			}
		
		} // PrepareColumns()
		
		
		/**
		 * This method attempts to load a record from the database based on the passed ID, or a 
		 * passed set of SQL query clauses. This method can be used retrieve one record from the 
		 * database, or a set of records that can be iterated through.
		 * 		 		 
		 * @argument mixed The ID of the data being found
		 * @argument array Additional databases clauses, including: join, where, order, offset and 
		 * 		 limit. All except for join are string that are directly appended to the query. 
		 * 		 Join is an array of referenced tables to inner join.
		 * @returns CRUD returns a reference to itself to allow chaining
		 */
		public function Find( $xId = null, $aClauses = array() )
		{
			$aPrimaryKey = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );
			
			if( !empty( $xId ) )
			{
				// If we have a primary key specified, make sure it the number of columns matches:
				if( count( $aPrimaryKey ) > 1 && ( !is_array( $xId ) || 
					count( $xId ) != count( $aPrimaryKey ) ) )
				{
					throw new QueryFailedException( "Invalid record key provided" );
				}
			}
			
			$sTableAlias = StringFunctions::ToSingular( $this->sTableName );
			
			
			$sWhere = isset( $aClauses[ "where" ] ) ? $aClauses[ "where" ] : "";
			
					
			// Handle our provided key:	
			
			if( !empty( $sWhere ) )
			{
				$sWhere = " WHERE {$sWhere} ";
			}

			if( is_array( $xId ) && count( $aPrimaryKey ) > 0 )
			{
				// our primary key value is an array -- put the data in the WHERE clause:
				
				foreach( $xId as $sField => $sValue )
				{					
					$sType = $this->oDatabase->GetColumnType( $this->sTableName, $sField );
					
					$sWhere .= !empty( $sWhere ) ? " AND " : " WHERE ";
					$sWhere .= "_{$sTableAlias}.{$sField} = " . 
						$this->oDatabase->FormatData( $sType, $sValue ) . " ";
				}
			}
			else if( !empty( $xId ) )
			{
				// we have a singular primary key -- put the data in the WHERE clause:
				$sKey = reset( $aPrimaryKey );
				$sType = $this->oDatabase->GetColumnType( $this->sTableName, $sKey );
				
				$sWhere .= !empty( $sWhere ) ? " AND " : " WHERE ";
				$sWhere .= "_{$sTableAlias}.{$sKey} = " . 
					$this->oDatabase->FormatData( $sType, $xId ) . " ";
			}
			
			$iLimit = isset( $aClauses[ "limit" ] ) ? 
				" LIMIT " . intval( $aClauses[ "limit" ] ) : "";
			
			$iOffset = isset( $aClauses[ "offset" ] ) ? 
				" OFFSET " . intval( $aClauses[ "offset" ] ) : "";
			
			
			// Setup supplied joins:
			
			$sJoins = "";
			
            if( isset( $aClauses[ "join" ] ) )
			{
                foreach( $aClauses[ "join" ] as &$xJoin )
				{				    
				    //
				    // xJoin may be either a relationship name, or it might be an array of
				    // join information:
				    //
				    // array(
				    //      table => table_name (required)
				    //      on => column_name (required)
				    //      as => table_alias (optional)
				    //      through => join_through (optional, through must be another join's "as")
				    // )
				    //
				    
				    
				    // If the join is an array:
                    
                    if( is_array( $xJoin ) )
					{				        
                        // Make sure the table value is provided:
                        if( !isset( $xJoin[ "table" ] ) )
                        {
                            throw new Exception( "Join table not specified" );
                        }
                        
                        // Make sure the column is provided:
                        if( !isset( $xJoin[ "on" ] ) )
                        {
                            throw new Exception( "Join column not specified" );
                        }
                        
    				    $sJoinType = isset( $xJoin[ "type" ] ) ? 
    				        $xJoin[ "type" ] : Database::JoinTypeInner;		
                            	        
    				    if( !isset( Database::$aJoinTypes[ $sJoinType ] ) )
    				    {
    				        throw new Exception( "Unknown join type specified: " . $xJoin[ "type" ] );
    				    }
    				        
    				    $sJoinType = Database::$aJoinTypes[ $sJoinType ];
                        					
                        if( isset( $xJoin[ "through" ] ) )
                        {
                            //throw new Exception( "through not yet implemented!" );
                            
                            // If we are joining through another table, we should have already 
                            // setup that join. Let's find it:
                            
                            $aJoin = array();
                            
                            foreach( $aClauses[ "join" ] as $xJoinSub )
                            {
                                if( isset( $xJoinSub[ "as" ] ) )
                                {
                                    if( $xJoin[ "through" ] == $xJoinSub[ "as" ] )
                                    {
                                        $aJoin = $xJoinSub;
                                        break;
                                    }
                                }
                            }
                            
                            if( empty( $aJoin ) )
                            {
                                throw new Exception( "Invalid through join specified: " . 
                                    $xJoin[ "through" ] );
                            }
                            
                            // Find the relationship:
                            $aRelationship = $this->FindRelationship2( $aJoin[ "table" ],
                                $xJoin[ "table" ], $xJoin[ "on" ] );
                            
                            // If the relationship doesn't exist:
                            if( empty( $aRelationship ) )
                            {
                                throw new Exception( "Relationship not found: " . 
                                    $this->sTableName . " -> " . $xJoin[ "table" ] . "." .
                                    $xJoin[ "on" ] );
                            }
                            
                            
                            // Start the join:
                            $sJoins .= "{$sJoinType} " . $xJoin[ "table" ] . " ";
                            
                            // Determine the alias (AS):
                            $sAs = "_" . $aRelationship[ "name" ];
                            
                            if( !empty( $xJoin[ "as" ] ) )
                            {
                                $sAs = $xJoin[ "as" ];
                            }
                            
                            $xJoin[ "as" ] = $sAs; // Store this for later use!
                            
                            // Add the alias:
                            $sJoins .= " AS " . $sAs . " ";
					        
					        // Add the ON clause:
                            $sJoins .= " ON " . $aJoin[ "as" ] . "." . 
                                current( $aRelationship[ "local" ] ) . " = " . 
                                $sAs . "." . current( $aRelationship[ "foreign" ] ) . " "; 
                        }
                        else
                        {
                            // Find the relationship:
                            $aRelationship = $this->FindRelationship2( $this->sTableName,
                                $xJoin[ "table" ], $xJoin[ "on" ] );
                            
                            // If the relationship doesn't exist:
                            if( empty( $aRelationship ) )
                            {
                                throw new Exception( "Relationship not found: " . 
                                    $this->sTableName . " -> " . $xJoin[ "table" ] . "." .
                                    $xJoin[ "on" ] );
                            }
                            
                            
                            // Start the join:
                            $sJoins .= "{$sJoinType} " . $xJoin[ "table" ] . " ";
                            
                            // Determine the alias (AS):
                            $sAs = "_" . $aRelationship[ "name" ];
                            
                            if( !empty( $xJoin[ "as" ] ) )
                            {
                                $sAs = $xJoin[ "as" ];
                            }
                            
                            $xJoin[ "as" ] = $sAs; // Store this for later use!
                            
                            // Add the alias:
                            $sJoins .= " AS " . $sAs . " ";
					        
					        // Add the ON clause:
                            $sJoins .= " ON _" . $sTableAlias . "." . 
                                current( $aRelationship[ "local" ] ) . " = " . 
                                $sAs . "." . current( $aRelationship[ "foreign" ] ) . " "; 
                        }
					}
					else
					{
						$aRelationship = $this->FindRelationship( $xJoin );
						
						if( !count( $aRelationship ) )
						{
							throw new Exception( "Unknown join relationship specified: {$xJoin}" );
						}
						
						$sJoins .= " INNER JOIN " . $aRelationship[ "table" ] . " AS " . 
							"_" . $aRelationship[ "name" ] . " ON ";
						
						$sOn = "";
						
						foreach( $aRelationship[ "local" ] as $iIndex => $sField )
						{
							$sOn .= ( !empty( $sOn ) ? " AND " : "" ) . 
								"_" . StringFunctions::ToSingular( $this->sTableName ) . 
								"." . $sField . " = " . "_" . $aRelationship[ "name" ] . 
								"." . $aRelationship[ "foreign" ][ $iIndex ];
						}
						
						$sJoins .= " {$sOn} ";
					}
				}
			}
			
			$sFields = "_" . StringFunctions::ToSingular( $this->sTableName ) . ".*";
			
			$sOrder = isset( $aClauses[ "order" ] ) ? 
				"ORDER BY " . $aClauses[ "order" ] : "";
				
			if( isset( $aClauses[ "distinct" ] ) && $aClauses[ "distinct" ] === true )
			{
				$sFields = " DISTINCT {$sFields} ";
			}
			
			// Concatenate all the pieces of the query together:
			$sSQL = "SELECT 
				{$sFields} 
			FROM 
				{$this->sTableName} AS _" . 
					StringFunctions::ToSingular( $this->sTableName ) . " 
			{$sJoins} 
			{$sWhere} 
			{$sOrder}
			{$iLimit}
			{$iOffset}";

			// Execute and pray:
			if( !( $this->oDataSet = $this->oDatabase->Query( $sSQL ) ) )
			{
				throw new Exception( "Failed on Query. Error: " . 
					$this->oDatabase->GetLastError() . "\n Query: {$sSQL}" );
			}
			
			// Loop the data and create member variables
			if( $this->oDataSet->Count() != 0 )
			{
				$this->Next();
			}
			
			return( $this );
			
		} // Find()
		
		
		/**
		 * This method returns the number of records that match a passed set of SQL query clauses. 
		 * This method is very similiar to Find(), except that it returns an integer value 
		 * representing the number of matching records.
		 * 
		 * @argument array Additional databases clauses, including: join and where. Where is a string 
		 * 		 that are directly appended to the query. Join is an array of referenced tables to 
		 * 		 inner join.
		 * @returns int Returns the number of database records that match the passed clauses
		 */
		public function FindCount( $aClauses = array() )
		{
			$aPrimaryKey = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );
						
			
			$sWhere = isset( $aClauses[ "where" ] ) ? $aClauses[ "where" ] : "";
			
			if( !empty( $sWhere ) )
			{
				$sWhere = " WHERE {$sWhere} ";
			}			
			
			// Setup supplied joins:
			
			$sJoins = "";
			
			if( isset( $aClauses[ "join" ] ) )
			{
				foreach( $aClauses[ "join" ] as $sJoin )
				{
					$aRelationship = $this->FindRelationship( $sJoin );
					
					if( !count( $aRelationship ) )
					{
						throw new Exception( "Unknown join relationship specified: {$sJoin}" );
					}
					
					$sJoins .= " INNER JOIN " . $aRelationship[ "table" ] . " AS " . 
						"_" . $aRelationship[ "name" ] . " ON ";
					
					$sOn = "";
					
					foreach( $aRelationship[ "local" ] as $iIndex => $sField )
					{
						$sOn .= ( !empty( $sOn ) ? " AND " : "" ) . 
							"_" . StringFunctions::ToSingular( $this->sTableName ) . 
							"." . $sField . " = " . "_" . $aRelationship[ "name" ] . 
							"." . $aRelationship[ "foreign" ][ $iIndex ];
					}
					
					$sJoins .= " {$sOn} ";
				}
			}
			
			// Concatenate all the pieces of the query together:
			$sSQL = "SELECT 
				COUNT( * ) AS count 
			FROM 
				{$this->sTableName} AS _" . 
					StringFunctions::ToSingular( $this->sTableName ) . " 
			{$sJoins} 
			{$sWhere}";		


			// Execute and pray:
			if( !( $this->oDataSet = $this->oDatabase->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->oDatabase->GetLastError() );
			}
			
			$this->oDataSet->Next();
			
			return( $this->oDataSet->Current()->count );
			
		} // FindCount()
		
		
		/**
		 * This method will retrieve records from the table based on column value using the supplied
		 * column name (which may have had underscores removed and be cased differently) and
		 * column value.
		 * 
		 * This method is invoked through __call() when the user uses the CRUD::getBy[column]()
		 * "virtual" method.			 		 		 		 
		 *
		 * @argument string The name of the column we are pulling records by. This name may 
		 * 	underscores removed and be cased differently		 
		 * @argument string The value of the column in the first argument that determines which
		 * 	records will be selected
		 * @argument string The order clause for the query		 
		 * @returns CRUD A reference to the current object to support chaining or secondary assignment
		 * @throws Exception, QueryFailedException		 		 		 		 		 
		 */
		protected function GetDataByColumnValue( $sColumn, $sValue, $sOrder = "" )
		{
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
			
			$aColumn = null;
			
			foreach( $aColumns as $sName => $aTmpColumn )
			{
				if( strtolower( str_replace( "_", "", $sName ) ) == strtolower( $sColumn ) )
				{
					$aColumn = $aTmpColumn;
					break;
				}
			}
			
			if( is_null( $aColumn ) )
			{
				throw new Exception( "Database column {$this->sTableName}.{$sColumn} does not exist." );
			}
			
			$sDataType = $aColumn[ "type" ];
			
			$aClauses = array(
				"where" => $aColumn[ "field" ] . " = " . 
					$this->oDatabase->FormatData( $sDataType, $sValue )
			);
			
			if( !empty( $sOrder ) )
			{
                $aClauses[ "order" ] = $sOrder;
			}
			
			$this->Find( null, $aClauses );
			
			return( $this );
			
		} // GetDataByColumnValue()		
		
		
		/**
		 * This method will delete records from the table based on column value using the supplied
		 * column name (which may have had underscores removed and be cased differently) and
		 * column value.
		 * 
		 * This method is invoked through __call() when the user uses the CRUD::destroyBy[column]()
		 * "virtual" method.		 		 		 		 		 
		 *
		 * @argument string The name of the column we are basing our delete from. This name may
		 * 	underscores removed and be cased differently		 
		 * @argument string The value of the column in the first argument that determines which
		 * 	records will be deleted.
		 * @returns boolean True if successful/no error; throws an Exception otherwise
		 * @throws Exception, QueryFailedException		 		 		 		 		 
		 */
		protected function DestroyDataByColumnValue( $sColumn, $sValue )
		{
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
			
			$aColumn = null;
			
			foreach( $aColumns as $sName => $aTmpColumn )
			{
				if( strtolower( str_replace( "_", "", $sName ) ) == strtolower( $sColumn ) )
				{
					$aColumn = $aTmpColumn;
					break;
				}
			}
			
			if( is_null( $aColumn ) )
			{
				throw new Exception( "Database column {$this->sTableName}.{$sColumn} does not exist." );
			}
			
			$sDataType = $aColumn[ "type" ];
			
			$sSQL = "DELETE FROM 
				{$this->sTableName}
			WHERE
				" . $aColumn[ "field" ] . " = " . $this->oDatabase->FormatData( $sDataType, $sValue );
			
			if( !$this->oDatabase->Query( $sSQL ) )
			{
				throw new QueryFailedException( "Failed to delete data" );
			}
			
			return( true );
			
		} // GetDataByColumnValue()	 		 		 		
		
		
		/**
		 *
		 * 		 
		 * @note GetRecord() will move the internal pointers of all 1-M iterators loaded
		 * 
		 *		 		 
		 */
		public function GetRecord()
		{			
			$oRecord = new StdClass();
			
			foreach( $this->aData as $sKey => $xValue )
			{
				if( is_object( $xValue ) )
				{
					if( $xValue->Count() > 1 )
					{
						$oRecord->$sKey = array();
						
						foreach( $xValue as $oValue )
						{
							$oRecord->{$sKey}[] = $oValue->GetRecord();
						}
					}
					else
					{
						$oRecord->$sKey = $xValue->GetRecord();
					}
				}
				else
				{
					$oRecord->$sKey = $xValue;
				}
			}
			
			return( $oRecord );
		
		} // GetRecord()
		
		
		/**
		 *
		 *
		 * 		 
		 */		 		 		
		public function GetAll()
		{
			$aRecords = array();
			
			$this->Rewind();
			
			foreach( $this->oDataSet as $oData )
			{
				$aRecords[] = $oData;
			}
		
			return( $aRecords );
		
		} // GetAll()
		
		
		/**
		 *
		 *
		 *		 
		 */		 		 		
		protected function FindRelationship( $sName )
		{
			$aForeignKeys = $this->oDatabase->GetTableForeignKeys( $this->sTableName );
			
			foreach( $aForeignKeys as $aForeignKey )
			{
				if( $aForeignKey[ "name" ] == $sName )
				{
					return( $aForeignKey );
				}
			}
			
			return( null );
		
		} // FindRelationship()
		
		
		/**
		 *
		 *
		 *		 
		 */		 		 		
		protected function FindRelationship2( $sPrimaryTable, $sRelatedTable, $sThroughColumn )
		{
			$aForeignKeys = $this->oDatabase->GetTableForeignKeys( $sPrimaryTable );
			
			foreach( $aForeignKeys as $aForeignKey )
			{
				if( $aForeignKey[ "table" ] == $sRelatedTable ||
                    current( $aForeignKey[ "local" ] ) == $sThroughColumn )
				{
					return( $aForeignKey );
				}
			}
			
			return( null );
		
		} // FindRelationship()
		
		
		/**
		 * Loads the specified data (either an array or object) into the CRUD object. This 
		 * array/object to load can contained referenced data (through foreign keys) as either
		 * an array or object.
		 * 		 		 
		 * @argument mixed The data to load into the CRUD object
		 * @returns void
		 */
		protected function Load( $oRecord )
		{
			if( !is_object( $oRecord ) && !is_array( $oRecord ) )
			{
				return;
			}
			
      		$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
				$aRelationships = $this->oDatabase->GetTableForeignKeys( $this->sTableName );

	         foreach( $oRecord as $sKey => $xValue )
	         {
	         	if( is_array( $xValue ) || is_object( $xValue ) )
	            {
						if( isset( $aRelationships[ $sKey ] ) )
						{
							$aRelationship = $aRelationships[ $sKey ];
							$sTable = $aRelationships[ $sKey ][ "table" ];
							
							if( $aRelationship[ "type" ] == "1-1" || $aRelationship[ "type" ] == "m-1" )
							{							
								$this->aData[ $sKey ] = $this->InstantiateClass( $sTable, $xValue );
							}
							else if( $aRelationships[ $sKey ][ "type" ] == "1-m" )
							{
								if( !isset( $this->aData[ $sKey ] ) )
								{
									$this->aData[ $sKey ] = array();
								}
								
								foreach( $xValue as $oRelatedData )
								{
									$this->aData[ $sKey ][] = $this->InstantiateClass( 
										$sTable, $oRelatedData );
								}
							}
						}					
	            }
	            else if( isset( $aColumns[ $sKey ] ) )
	            {
						$this->aData[ $sKey ] = $xValue;
	         	}
			}

		} // Load()
		
		
		/**
		 *  Determines whether or not there is currently data in the CRUD object. Data is loaded into 
		 *  CRUD through the Find() method, through specifying data into fields manually, or by 
		 *  passing data to the constructor. If any of these cases are met, this method will 
		 *  return true.	 		 	 
		 * 	
		 * @returns boolean True if there is no data currently in CRUD, false otherwise
		 */
		protected function IsEmpty()
		{
			return( $this->Count() == 0 );
			
		} // IsEmpty()
		
		
		/**
		 *  Gets the number of rows returned by the last Find() call. If Find() has not yet been 
		 *  called, this method will return This method is invoked through the __call() method to 
		 *  allow using the method name Count(), which is a reserved word in PHP. 		 		 	 
		 * 	
		 * @returns integer The number of results in the data set
		 */
		public function Count() 
		{
			if( !is_null( $this->oDataSet ) )
			{
				return( $this->oDataSet->Count() );
			}
			
			return( 0 );
		
		} // GetCount()
			
		
		/**
		 *
		 *
		 *
		 */		 		 		 		
		public function __isset( $sName )
		{
			return( array_key_exists( $sName, $this->aData ) );
			
		} // __isset()
		
		
		/**
		 *
		 *
		 */		 		 		
		public function __get( $sName )
		{			
			if( array_key_exists( $sName, $this->aData ) )
			{
				return( $this->aData[ $sName ] );
			}
		
			$aSchema = $this->oDatabase->GetSchema( $this->sTableName );
			
			$aRelationships = $aSchema[ "foreign_key" ];			

			if( !isset( $aRelationships[ $sName ] ) )
			{
				throw new Exception( "Relationship [{$sName}] does not exist" );
			}

			$aRelationship = $aSchema[ "foreign_key" ][ $sName ];
			
			// the relationship exists, attempt to load the data:
			
			if( $aRelationship[ "type" ] == "1-m" )
			{				
				$sWhere = "";
				
				foreach( $aRelationship[ "foreign" ] as $iIndex => $sKey )
				{
					$sRelated = $aRelationship[ "local" ][ $iIndex ];
					
					$sWhere .= empty( $sWhere ) ? "" : " AND ";
					$sWhere .= " {$sKey} = " . intval( $this->aData[ $sRelated ] );
				}
							
				$this->aData[ $sName ] = $this->InstantiateClass( $aRelationship[ "table" ] );
				$this->aData[ $sName ]->Find( null, array( "where" => $sWhere ) );
			}
			else
			{
				$sLocalColumn = current( $aRelationship[ "local" ] );
				
				if( isset( $this->aData[ $sLocalColumn ] ) )
				{
					$this->aData[ $sName ] = $this->InstantiateClass( $aRelationship[ "table" ] );
					$this->aData[ $sName ]->Find( $this->aData[ $sLocalColumn ] );	
				}
				else
				{
					// Modified 2007-12-29 to prevent error:
					// Notice: Indirect modification of overloaded property has no effect
					// If we are dynamically creating a record, we need to return an empty object for 
					// this relationship to load into
					
					$this->aData[ $sName ] = $this->InstantiateClass( $aRelationship[ "table" ] );
				}
			}
			
			return( $this->aData[ $sName ] );
			
		} // __get()
		
		
		/** 
		 * Attempts to set the value of a database column, or sets a relationship through the
		 * CRUD->[column_name] syntax.
		 * 
		 * @argument string The name of the column to set
		 * @argument string The value to set the column specified in the first argument
		 * @returns void
		 * @throws Exception
		 */	
		public function __set( $sName, $sValue )
		{			
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
		
			if( isset( $aColumns[ $sName ] ) )
			{
				$this->aData[ $sName ] = $sValue;
			}
			else if( !is_null( $this->FindRelationship( $sName ) ) )
			{
				$this->aData[ $sName ] = $sValue;
			}
			else
			{
				throw new Exception( "Unknown column [{$sName}] referenced" );
			}
		
		} // __set()
		
		
		/**
		 * Unsets the value of a database column. This will effectively remove the column from
		 * the known list of columns for this instance, causing a CRUD::Save() operation to not
		 * update the value.
		 * 
		 * @argument string The name of the database column to unset
		 * @returns void
		 */	
		public function __unset( $sName )
		{
			if( isset( $this->aData[ $sName ] ) )
			{
				unset( $this->aData[ $sName ] );
			}
		
		} // __set()
		
		
		/**
		 * Supports several "virtual" or magic methods, such as data manipulation/retrieval through 
		 * 	getBy[column_name] and destroyBy[column_name], reserved word methods, such as empty(),
		 * 	and also provides access to public methods of the database, which fakes database
		 * 	class inheritance (which is needed to support multiple database drivers).
		 *
		 * @argument string The name of the argument to be called magically	
		 * @argument array An array of arguments to pass to the magically called method
		 * @returns mixed Depends sName, the first argument
		 * @throws Exception
		 */
		public function __call( $sName, $aArguments )
		{
			switch( strtolower( $sName ) )
			{
				case "empty":
					return( $this->IsEmpty() );
				break;
			}
			
			if( is_callable( array( $this->oDatabase, $sName ) ) )
			{
				return( call_user_func_array( array( $this->oDatabase, $sName ), $aArguments ) );
			}
			
			if( substr( $sName, 0, 5 ) == "getBy" )
			{			
				return( $this->GetDataByColumnValue( substr( $sName, 5 ), $aArguments[ 0 ],
                    isset( $aArguments[ 1 ] ) ? $aArguments[ 1 ] : null ) );
			}
			else if( substr( $sName, 0, 9 ) == "destroyBy" )
			{				
				return( $this->DestroyDataByColumnValue( substr( $sName, 9 ), $aArguments[ 0 ] ) );
			}
			
			throw new Exception( "Call to undefined method: {$sName}" );
				
		} // __call()
		
		
		/**
		 * Assists slightly in object cloning. If this table has a single primary key, the value
		 * of this key will be whiped out when cloning. 		 
		 *		 		 
		 * @returns void		 
		 */		 		
		public function __clone()
		{
			$aPrimaryKey = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );
			
			if( count( $aPrimaryKey ) == 1 )
			{
				$sPrimaryKey = reset( $aPrimaryKey );
				
				$this->{$sPrimaryKey} = null;
			}
		
		} // __clone()
		
		
		/**
		 * Based on presence of primary key data, either creates a new record, or 
		 * updates theexisting record
		 *
		 * @returns boolean True if the save was successful, false otherwise		 
		 */
		public function Save()
		{			
			// grab a copy of the primary key:
			$aPrimaryKeys = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );
			
			$bInsert = false;
			
			// If we have a compound primary key, we must first determine if the record
			// already exists in the database. If it does, we're doing an update.
			
			// If we have a singular primary key, we can rely on whether the primary key
			// value of this object is null
			
			if( count( $aPrimaryKeys ) == 1 )
			{
				$sPrimaryKey = reset( $aPrimaryKeys );
				
				if( $this->oDatabase->IsPrimaryKeyReference( $this->sTableName, $sPrimaryKey ) )
				{
					// See Task #56
					$bInsert = !$this->RecordExists();
				}
				else if( empty( $this->aData[ $sPrimaryKey ] ) )
				{
					$bInsert = true;
				}
			}
			else
			{
				$bInsert = !$this->RecordExists();
			}
			
			if( $bInsert )
			{
				return( $this->Insert() );
			}
			else
			{
				return( $this->Update() );
			}
		
		} // Save()
		
		
		/**
		 *
		 */
		public function SaveAll()
		{            
			$aForeignKeys = $this->oDatabase->GetTableForeignKeys( $this->sTableName );
					
			// Save all dependencies first

			foreach( $aForeignKeys as $aRelationship )
			{
				$sRelationshipName = $aRelationship[ "name" ];
				
				if( isset( $this->aData[ $sRelationshipName ] ) && $aRelationship[ "dependency" ] )
				{
					if( !$this->aData[ $sRelationshipName ]->SaveAll() )
					{
						return( false );
					}
					
					// We only work with single keys here !!
					$sLocal = reset( $aRelationship[ "local" ] );
					$sForeign = reset( $aRelationship[ "foreign" ] );
					
					$this->aData[ $sLocal ] = $this->aData[ $sRelationshipName ]->$sForeign;
				}
			}

			// Save the primary record
			
			if( !$this->Save() )
			{
				return( false );
			}
			
			// Save all related data last

			foreach( $aForeignKeys as $aRelationship )
			{
				$sRelationshipName = $aRelationship[ "name" ];
				
				if( isset( $this->aData[ $sRelationshipName ] ) && !$aRelationship[ "dependency" ] )
				{
					// We only work with single keys here !!
					$sLocal = reset( $aRelationship[ "local" ] );
					$sForeign = reset( $aRelationship[ "foreign" ] );
						
					if( $aRelationship[ "type" ] == "1-m" )
					{
						foreach( $this->aData[ $sRelationshipName ] as $oRelationship )
						{
							$oRelationship->$sForeign = $this->aData[ $sLocal ];
							
							if( !$oRelationship->SaveAll() )
							{
								return( false );
							}
						}
					}
					else if( $aRelationship[ "type" ] == "1-1" )
					{						
						$this->aData[ $sRelationshipName ]->$sForeign = $this->aData[ $sLocal ];
						$this->aData[ $sRelationshipName ]->SaveAll();
					}
				}
			}
			
			return( true );
		
		} // SaveAll()
		
		
		/**
		 *
		 */
		protected function Insert()
		{
			$sColumns = "";
			$sValues = "";
			
			$aPrimaryKeys = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );			
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
			
			// loop each column in the table and specify it's data:
			foreach( $aColumns as $aColumn )
			{
				// automate updating created date column:
				if( in_array( $aColumn[ "field" ], array( "created_date", "created_stamp", "created_on" ) ) )
				{
					// dates are stored as GMT
					$this->aData[ $aColumn[ "field" ] ] = gmdate( "Y-m-d H:i:s" );
				}
				
				// If the primary key is singular, do not provide a value for it:				
				if( in_array( $aColumn[ "field" ], $aPrimaryKeys ) && count( $aPrimaryKeys ) == 1 && 
					!$this->oDatabase->IsPrimaryKeyReference( $this->sTableName, reset( $aPrimaryKeys ) ) )
				{
					continue;
				}
				
				if( empty( $this->aData[ $aColumn[ "field" ] ] ) )
				{
					continue;
				}
				
				// Create a list of columns to insert into:
				$sColumns .= ( !empty( $sColumns ) ? ", " : "" ) . 
					$aColumn[ "field" ];
				
				// Get the value for the column (if present):
				$sValue = isset( $this->aData[ $aColumn[ "field" ] ] ) ? 
					$this->aData[ $aColumn[ "field" ] ] : "";
				
				// Create a list of values to insert into the above columns:
				$sValues .= ( !empty( $sValues ) ? ", " : "" ) . 
					$this->oDatabase->FormatData( $aColumn[ "type" ], $sValue );
			}
			
			$sSQL = "INSERT INTO {$this->sTableName} (
				{$sColumns}
			) VALUES (
				{$sValues}
			)";
			
			if( !$this->oDatabase->Query( $sSQL ) )
			{
				throw new Exception( "Failed on Query: {$sSQL} - " . $this->oDatabase->GetLastError() );
			}
			
			// Note: an assumption is made that if the primary key is not singular, then there all
			// the data for the compound primary key should already be present -- meaning, we should 
			// not have a serial value on the table for a compound primary key.
			
			// If we have a singular primary key:
			if( count( $aPrimaryKeys ) == 1 )
			{				
				// Get the current value of the serial for the primary key column:
				$iKey = $this->oDatabase->SerialCurrVal( $this->sTableName, reset( $aPrimaryKeys ) );
				
				// Store the primary key:
				$this->aData[ $aPrimaryKeys[0] ] = $iKey;
				
				// return the primary key:
				return( true );
			}
			
			
			// If we have a compound primary key, return true:
			return( true );
			
		} // Insert()
		
		
		/**
		 * Responsible for updating the currently stored data for primary table and
		 * all foreign tables referenced
		 * 
		 * @returns boolean True if the update was successful, false otherwise		 		 		 
		 */
		protected function Update()
		{			
			// update the primary record:
			$sSQL = $this->UpdateQuery();
			
			if( !$this->oDatabase->Query( $sSQL ) )
			{
				throw new Exception( "Failed on Query: " . $this->oDatabase->GetLastError() );
			}
			
			return( true );
			
		} // Update()
		
		
		/**
		 * Called by the Update() method to generate an update query for this table
		 * 
		 * @returns string The generated SQL query		 		 
		 */
		protected function UpdateQuery()
		{
			$aSchema = $this->oDatabase->GetSchema( $this->sTableName );
			
			$aPrimaryKeys = $aSchema[ "primary_key" ];
					
			$sSet = "";

			// loop each field in the table and specify it's data:
			foreach( $aSchema[ "fields" ] as $field )
			{
				// do not update certain fields:
				if( in_array( $field[ "field" ], array( "created_date", "created_stamp", "created_on" ) ) )
				{
					continue;
				}
				
				// automate updating update date fields:
				if( in_array( $field[ "field" ], array( "updated_date", "updated_stamp", "updated_on" ) ) )
				{
					$this->aData[ $field[ "field" ] ] = gmdate( "Y-m-d H:i:s" );
				}
				
				if( !isset( $this->aData[ $field[ "field" ] ] ) )
				{
					continue;
				}
				
				// complete the query for this field:
				$sSet .= ( !empty( $sSet ) ? ", " : "" ) . 
					$field[ "field" ] . " = " . 
						$this->oDatabase->FormatData( $field[ "type" ], $this->aData[ $field[ "field" ] ] ) . " ";
			}
			
			// if we found no fields to update, return:
			if( empty( $sSet ) )
			{
				return;
			}
			
						
			$sWhere = "";
			
			foreach( $aPrimaryKeys as $sKey )
			{
				$sWhere .= !empty( $sWhere ) ? " AND " : "";
				$sWhere .= "{$sKey} = " . intval( $this->aData[ $sKey ] );
			}
			
			$sSQL = "UPDATE {$this->sTableName} SET {$sSet} WHERE {$sWhere}";	
			

			return( $sSQL );
			
		} // UpdateQuery()
		
		
		/**
		 *
		 *
		 * 		 
		 */		 		 		
		protected function RecordExists()
		{
			$aPrimaryKeys = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );
		
			$sSQL = "SELECT
				1
			FROM
				{$this->sTableName} ";
			
			$sWhere = "";
			
			foreach( $aPrimaryKeys as $sPrimaryKey )
			{
				$sType = $this->oDatabase->GetColumnType( $this->sTableName, $sPrimaryKey );
				
				$sWhere .= empty( $sWhere ) ? " WHERE " : " AND ";
				$sWhere .= $sPrimaryKey . " = " . 
					$this->oDatabase->FormatData( $sType, $this->aData[ $sPrimaryKey ] ) . " ";
			}
			
			$sSQL .= $sWhere;
			
			if( !( $oResultSet = $this->oDatabase->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( "Failed on Query: " . $this->oDatabase->GetLastError() );
			}
			
			return( $oResultSet->Count() != 0 );
		
		} // RecordExists()
		
		
		/**
		 * Destroys (deletes) the current data. This method will delete the primary record 
		 * (assuming that the primary key for the data is set).
		 * 	
		 * @returns void
		 */
		public function Destroy()
		{
			$aPrimaryKeys = $this->oDatabase->GetTablePrimaryKey( $this->sTableName );
			
			$sSQL = "DELETE FROM
				{$this->sTableName}
			WHERE ";
			
			$sWhere = "";
			
			foreach( $aPrimaryKeys as $sKey )
			{
				$sWhere .= empty( $sWhere ) ? "" : " AND ";
				$sWhere .= "{$sKey} = " . $this->oDatabase->FormatData( 
					$this->oDatabase->GetColumnType( $this->sTableName, $sKey ), $this->aData[ $sKey ] );
			}
			
			$sSQL .= $sWhere;
			
			if( !$this->oDatabase->Query( $sSQL ) )
			{
				throw new QueryFailedException( "Failed on Query: " . $this->oDatabase->GetLastError() );
			}
		
		} // Destroy()
		
		
		/**
		 * Helper method for generating a where clause for a query string. Where clause is
		 * built by supplied keys and associated data
		 * 
		 */		 		 		
		protected function buildWhereClause( $keys, $dataSet )
		{
			$where = "";
			
			// loop each primary key and build a where clause for the data:	
			foreach( $keys as $key )
			{
				if( isset( $dataSet->$key ) )
				{
					$where .= !empty( $where ) ? " AND " : " WHERE ";
					$where .= "{$key} = {$dataSet->$key}";
				}
			}
			
			return( $where );
			
		} // buildWhereClause()
	
	
		//
		// ITERATOR DEFINITION
		//
		

		/**
		 *
		 * See http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
		 * 
		 */		 		 
		public function Rewind() 
		{							
			if( !is_null( $this->oDataSet ) && $this->oDataSet->Count() != 0 )
			{
				$this->Cleanup();
				
				$this->oDataSet->Rewind();
			
				if( $this->oDataSet->Valid() )
				{
					$this->Load( $this->oDataSet->Current() );
				}
			}
		
		} // Rewind()
    	

		/**
		 *  Returns the current object from the DataSet generated from the last call to Find().
		 *  This method is part of the PHP Iterator implementation, see
		 *  http://www.php.net/~helly/php/ext/spl/interfaceIterator.html for reference.		 
		 * 	
		 * @returns CRUD Returns a CRUD object if there data, or null otherwise
		 */
		public function Current() 
		{			
			if( $this->Valid() )
			{
				return( $this );
			}
			
			return( null );
		
		} // Current()
		
		
		/**
		 *
		 * See http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
		 * 
		 */		
		public function Key() 
		{			
			return( $this->oDataSet->Key() );
			
		} // Key()


		/**
		 *
		 * See http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
		 * 
		 */		
		public function Next() 
		{			
			if( !is_null( $this->oDataSet ) )
			{
				$this->Cleanup();
			
				$this->oDataSet->Next();
				
				if( $this->Valid() )
				{
				    $aSchema = $this->oDatabase->GetSchema( $this->sTableName );
				
					$oData = $this->oDataSet->Current();
					
					// Turn any boolean fields into true booleans, instead of chars:
                    foreach( $oData as $sKey => $sValue )
                    {    
                        if( strpos( $aSchema[ "fields" ][ $sKey ][ "type" ], "bool" ) !== false )
                        {
                            $oData->$sKey = $sValue == "t" ? true : false;
                        }
                    }
                    	
					$this->Load( $oData );
				}
			}
		
		} // Next()


		/**
		 *
		 * See http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
		 * 
		 */		
		public function Valid()  
		{			
			return( $this->oDataSet->Valid() );
		
		} // Valid()
		
		
		/**
		 *
		 *
		 */		 		 		
		protected function Cleanup()
		{
			$aRelationships = $this->oDatabase->GetTableForeignKeys( $this->sTableName );
			
			foreach( $aRelationships as $aRelationship )
			{
				$sRelationshipName = $aRelationship[ "name" ];
				
				if( array_key_exists( $sRelationshipName, $this->aData ) )
				{
					unset( $this->aData[ $sRelationshipName ] );
				}
			}
			
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
			
			// Loop each column in the table and create a member variable for it:			
			foreach( $aColumns as $aColumn )
			{
				$this->aData[ $aColumn[ "field" ] ] = null;
			}
				
		} // Cleanup()
		
		
		
		/**
		 * Returns the table name associated with this CRUD object
		 *
		 * @returns string The name of the table associated with this CRUD object
		 */
		public function GetTableName()
		{
			return( $this->sTableName );
			
		} // GetTableName() 
		
		
		/**
		 * Returns the data currently stored in the CRUD object a well formed XML document as a
		 * string representation. This requires the DOM and SimpleXML extensions of PHP to be 
		 * installed. If either extension is not installed, this method will throw an exception.
		 * 	        
		 * @argument bool Should this returned XML include references? Default false.
		 * @argument bool Should this returned XML include all records returned by the last Find()
		 *		call? If not, only the current record stored is returned. Default false.      
		 * @returns string A well formed XML document as a string representation
		 */
		public function AsXMLString( $bIncludeReferences = false, $bProvideAll = false )
		{
			$oXML = $this->asXML( $bIncludeReferences, $bProvideAll );			
			$sXML = XMLFunctions::PrettyPrint( $oXML->asXML() );
			
			return( $sXML );
			
		} // AsXMLString()
		
		
		/**
		 * Returns the data currently stored in the CRUD object a well formed XML document as a 
		 * SimpleXMLElement object. This method requires the SimpleXML extension of PHP to be
		 * installed. If the SimpleXML extension is not installed, this method will throw an 
		 * exception.
		 * 	        
		 * @argument bool Should this returned XML include references? Default false.
		 * @argument bool Should this returned XML include all records returned by the last Find()
		 *		call? If not, only the current record stored is returned. Default false.      
		 * @returns SimpleXMLElement The data requested as a SimpleXMLElement object
		 */
		public function AsXML( $bIncludeReferences = false, $bProvideAll = false )
		{
			$oXML = null;
			
			if( $bProvideAll )
			{
				$sName = $this->sTableName;
				$sElementName = StringFunctions::ToSingular( $this->sTableName );
				
				$oXML = new SimpleXMLElement( "<{$sName}></{$sName}>" );
				
				foreach( $this as $oObject )
				{
					$oElement = $oXML->addChild( $sElementName );
					$this->AddColumns( $oElement, $oObject, $this->sTableName );
					
					if( $bIncludeReferences )
					{
						$this->AddReferences( $oElement, $oObject, $this->sTableName );
					}
				}
			}
			else
			{
				$sName = StringFunctions::ToSingular( $this->sTableName );
				
				$oXML = new SimpleXMLElement( "<{$sName}></{$sName}>" );
				
				$this->AddColumns( $oXML, $this, $this->sTableName );
				$this->AddReferences( $oXML, $this, $this->sTableName );
			}
		
			return( $oXML );
				
		} // AsXML()
		
	
		/**
         * Add the database table columns for the specified table, from the specified object, to
         * the specfied SimpleXMLElement. Used internally by AsXML() 
		 * 	        
		 * @argument SimpleXMLElement 
		 * @argument CRUD
		 * @argument string		      
		 * @returns SimpleXMLElement The data requested as a SimpleXMLElement object
		  */
		private function AddColumns( &$oElement, &$oObject, $sTableName )
		{
			$aColumns = $this->oDatabase->GetTableColumns( $sTableName );
			
			foreach( $aColumns as $aColumn )
			{
				$oElement->addChild( $aColumn[ "field" ], $oObject->{$aColumn[ "field" ]} );
			}
			
		} // AddColumns()
		

		/**
		 * Add the database table references for the specified table, from the specified object, to
		 * the specfied SimpleXMLElement. Used internally by AsXML()   
		 * 	        
		 * @argument SimpleXMLElement 
		 * @argument CRUD
		 * @argument string		      
		 * @returns SimpleXMLElement The data requested as a SimpleXMLElement object
		 */
		private function AddReferences( &$oElement, &$oObject, $sTableName )
		{
			$aTableReferences = $this->oDatabase->GetTableForeignKeys( $sTableName );
				
			foreach( $aTableReferences as $aReference )
			{
				$oData = $this->{$aReference[ "name" ]};
								
				if( !empty( $oData ) && !$oData->Empty() )
				{
					if( $aReference[ "type" ] == "1-m" )
					{
						$sChildReferenceName = StringFunctions::ToSingular( $aReference[ "name" ] );
						
						$oReference = $oElement->addChild( $aReference[ "name" ] );
						
						foreach( $oData as $oDataElement )
						{
							$oChildReference = $oReference->addChild( $sChildReferenceName );
							
							$this->AddColumns( $oChildReference, $oDataElement, $aReference[ "table" ] );
						}
					}
					else
					{
						$oReference = $oElement->addChild( $aReference[ "name" ] );
						$this->AddColumns( $oReference, $oData, $aReference[ "table" ] );
					}
				}
				
			}
		
		} // AddReferences()

		
		/**
         * Returns the data currently stored in the CRUD object as a JSON (JavaScript object notation)
         * string. If bIncludeReferences is true, then each reference to the table is considered and 
         * added to the XML document.
         *
         * @argument bool Toggles whether references/relationships should be stored in the JSON string       
         * @returns string A JSON string representing the CRUD object
         */
		public function AsJSON( $bIncludeReferences = false)
		{
			$oJSON = new JSONObject();
			
			$aColumns = $this->oDatabase->GetTableColumns( $this->sTableName );
			
			foreach( $aColumns as $aColumn )
			{
				$oJSON->AddAttribute( $aColumn[ "field" ], $this->aData[ $aColumn[ "field" ] ] );
			}
			
			if( $bIncludeReferences )
			{
				$aTableReferences = $this->oDatabase->GetTableForeignKeys( $this->sTableName );
					
				foreach( $aTableReferences as $aReference )
				{
					$oData = $this->{$aReference[ "name" ]};
									
					if( !empty( $oData ) && !$oData->Empty() )
					{
						$aReferenceColumns = $this->oDatabase->GetTableColumns( $aReference[ "table" ] );
							
						if( $aReference[ "type" ] == "1-m" )
						{						
							$aReferences = array();
							
							$sChildReferenceName = StringFunctions::ToSingular( $aReference[ "name" ] );
							
							//$oReference = $oElement->addChild( $aReference[ "name" ] );
							
							foreach( $oData as $oDataElement )
							{
								$oReferenceJSON = new JSONObject();
							
								foreach( $aReferenceColumns as $aColumn )
								{
									$oReferenceJSON->AddAttribute( $aColumn[ "field" ], $oData->{$aColumn[ "field" ]} );
								}
							
								$aReferences[] = $oReferenceJSON;
							}
							
							
							$oJSON->AddAttribute( $aReference[ "name" ], $aReferences );							
						}
						else
						{
							$oReferenceJSON = new JSONObject();
							
							foreach( $aReferenceColumns as $aColumn )
							{
								$oReferenceJSON->AddAttribute( $aColumn[ "field" ], $oData->{$aColumn[ "field" ]} );
							}
							
							$oJSON->AddAttribute( $aReference[ "name" ], $oReferenceJSON );
						}
					}
					
				}
			}
			
			return( $oJSON->__toString() );
			
		} // AsJSON()
		
		
		/**
		 * Creates a readable, string representation of the object using print_r and returns that
		 * string.       
		 *
		 * @returns string A readable, string representation of the object
		 */
		public function __toString()
		{
			return( print_r( $this->aData, true ) );
			
		} // __toString()
		
		
		/**
		 * The purpose of this method is to instantiate a class based on a table name. This is used 
		 * several times throughout the CRUD class. If we determine that a Model class exists for the 
		 * specified table name, then we instiantiate an object of that class. Otherwise, we 
		 * instantiate an object of CRUD for that table name.
		 *
		 * To determine if a Model exists, we look for a class name that matches the English singular
		 * version of the table name. If we find such a class, and if this class is a subclass of
		 * the Model class (which itself is a subclass of CRUD), we assume this is the Model class
		 * we should use and instantiate it.
		 *
		 * @returns object The generated object, either CRUD or a subclass of CRUD
		 */
		private function InstantiateClass( $sTableName, $xData = null )
		{			
			$sModelName = StringFunctions::ToSingular( $sTableName );
			
			$oObject = null;
			
			if( class_exists( $sModelName, true ) && is_subclass_of( $sModelName, "Model" ) )
			{
				$oObject = new $sModelName( $xData );
			}
			else
			{
				$oObject = new CRUD( $sTableName, $xData );
			}			 
			
			return( $oObject );
			
		} // InstantiateClass()
		
	} // CRUD()

?>
