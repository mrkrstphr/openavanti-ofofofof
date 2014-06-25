<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	Database, CRUD
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link			http://www.openavanti.com
 * @version			0.6.7-beta
 *
 */

	/**
	 * The model class of the MVC architecture. Extends the CRUD database abstraction layer
	 * to enhance it. This class should not be used directly. It should be extended with methods
	 * specific to the database table it is interacting with.	 	 
	 *
	 * @category	MVC
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/model
	 */
	class Model extends CRUD
	{
	
		/**
		 * The Model's constructor - accepts the name of the table and an optional set of data
		 * to load into the parent CRUD object. Invokes CRUD::__construct() and passes these two
		 * parameters along.
		 * 
		 * @argument string The name of the table to work on
		 * @argument mixed An array or object of data to load into the CRUD object		 		 		 		 		 
		 */
		public function __construct( $sTableName, $oData = null )
		{			
			parent::__construct( $sTableName, $oData );
			
		} // __construct()
		

		/**
		 * Wraps CRUD's Save() method to invoke the events ValidateUpdate(), Validate(), 
		 * OnBeforeUpdate(), OnBeforeSave(), respectively, before calling CRUD::Save() for an UPDATE
		 * query. Likewise, invokes the events ValidateInsert(), Validate(), OnBeforeInsert() and
		 * OnBeforeSave(), respectively, before calling CRUD::Save() for an INSERT statement. 
		 * 
		 * If any of these events return false, Model::Save() returns false, before calling 
		 * CRUD::Save().
		 * 
		 * If CRUD::Save() is invoked and returns true, the events OnAfterUpdate() and OnAfterSave()
		 * are invoked, respectively, for UPDATE queries. The events OnAfterInsert() and OnAfterSave()
		 * are invoked, respectively, for INSERT queries.		 		 		 		 
		 *
		 * @returns bool True if the object can be saved, false if not
		 */	
		public function Save()
		{		
			$bUpdate = parent::RecordExists();
		
			if( $bUpdate )
			{
				if( !$this->ValidateUpdate() ||
					 !$this->Validate() ||
					 !$this->OnBeforeUpdate() || 
					 !$this->OnBeforeSave() )
				{
					return( false );
				}
			}
			else
			{
				if( !$this->ValidateInsert() ||
					 !$this->Validate() ||
					 !$this->OnBeforeInsert() || 
					 !$this->OnBeforeSave() )
				{
					return( false );
				}
			}	
			
			if( !parent::Save() )
			{
				return( false );
			}
		
			if( $bUpdate )
			{
				if( !$this->OnAfterUpdate() || 
					 !$this->OnAfterSave() )
				{
					return( false );
				}
			}
			else
			{
				if( !$this->OnAfterInsert() || 
					 !$this->OnAfterSave() )
				{
					return( false );
				}
			}
			
			// Everything returned true, so should we:
			return( true );			
		
		} // Save()
		
		
		/**		 		 		 
		 * This method does the same thing as Save(), but also saves all related data loaded into
		 * the CRUD object as well. See CRUD::SaveAll() for more details		 
		 * 		 
		 * @returns bool True if the object can be saved, false if not
		 */	
		public function SaveAll()
		{		
			$bUpdate = parent::RecordExists();
		
			if( $bUpdate )
			{
				if( !$this->OnBeforeUpdate() || 
                    !$this->OnBeforeSave() ||
                    !$this->ValidateUpdate() ||
                    !$this->Validate() )
				{
					return( false );
				}
			}
			else
			{
				if( !$this->OnBeforeInsert() || 
                    !$this->OnBeforeSave() || 
                    !$this->ValidateInsert() ||
                    !$this->Validate() )
				{
					return( false );
				}
			}	
			
			if( !parent::SaveAll() )
			{
				return( false );
			}
		
			if( $bUpdate )
			{
				if( !$this->OnAfterUpdate() || 
					 !$this->OnAfterSave() )
				{
					return( false );
				}
			}
			else
			{
				if( !$this->OnAfterInsert() || 
					 !$this->OnAfterSave() )
				{
					return( false );
				}
			}
			
			// Everything returned true, so should we:
			return( true );			
		
		} // SaveAll()
		
		
		/**
		 * Wraps CRUD's Destroy() method to invoke the event OnBeforeDestroy() before calling
		 * CRUD::Destroy(), and to invoke the event OnAfterDestroy() afterwards. If either 
		 * event returns false, execution of this method will stop and false will be returned.		 		 
		 *
		 * @returns bool True if the object can be destroyed, false if not
		 */	
		public function Destroy()
		{
			// Run the OnBeforeDestroy() event. If it fails, return false
			if( !$this->OnBeforeDestroy() )
			{
				return( false );
			}
			
			// Invoke CRUD's Destroy() method. If it fails, return false
			if( !parent::Destroy() )
			{
				return( false );
			}
		
			// Run the OnAfterDestroy() event. If it fails, return false
			if( !$this->OnAfterDestroy() )
			{
				return( false );
			}			
			
			// Everything returned true, so should we:
			return( true );
		
		} // Destroy()
		
		
		/**
		 * Triggered before a call to CRUD::Save(), for both INSERT and UPDATE actions. If this method
		 * returns false, Model::Save() will be halted and false returned.
		 *
		 * @returns bool True if the object can be saved, false if not
		 */		 		 
		protected function OnBeforeSave()
		{
			// Default return true if this method is not extended:
			return( true );
		
		} // OnBeforeSave()
		
		
		/**
		 * Triggered before a call to CRUD::Save(), for INSERT statements only. If this method
		 * returns false, Model::Save() will be halted and false returned.
		 *
		 * @returns bool True if the object can be saved, false if not
		 */	
		protected function OnBeforeInsert()
		{
			// Default return true if this method is not extended:
			return( true );
		
		} // OnBeforeInsert()
		
		
		/**
		 * Triggered before a call to CRUD::Save(), for UPDATE statements only. If this method
		 * returns false, Model::Save() will be halted and false returned.
		 *
		 * @returns bool True if the object can be saved, false if not
		 */	
		protected function OnBeforeUpdate()
		{
			// Default return true if this method is not extended:
			return( true );
		 
		} // OnBeforeUpdate()
	
		
		/**
		 * Triggered after a call to CRUD::Save(), for both INSERT and UPDATE statements only. If 
		 * this method returns false, Model::Save() will return false. It is up to the user at this
		 * point to take the necessary actions, such as Rolling back the database transaction.		 
		 *
		 * @returns bool True if the object can be saved, false if not
		 */
		protected function OnAfterSave()
		{
			// Default return true if this method is not extended:
			return( true );
		
		} // OnBeforeSave()
		
		
		/**
		 * Triggered after a call to CRUD::Save(), for INSERT only statements only. If this method 
		 * returns false, Model::Save() will return false. It is up to the user at this point to 
		 * take the necessary actions, such as Rolling back the database transaction.		 
		 *
		 * @returns bool True if the object can be saved, false if not
		 */
		protected function OnAfterInsert()
		{
			// Default return true if this method is not extended:
			return( true );		
		
		} // OnAfterInsert()
		
		
		/**
		 * Triggered after a call to CRUD::Save(), for UPDATE only statements only. If this method 
		 * returns false, Model::Save() will return false. It is up to the user at this point to 
		 * take the necessary actions, such as rolling back the database transaction.		 
		 *
		 * @returns bool True if the object can be saved, false if not
		 */
		protected function OnAfterUpdate()
		{
			// Default return true if this method is not extended:
			return( true );		
		
		} // OnAfterUpdate()
		
		
		/**
		 * Triggered before a call to CRUD::Destroy(). If this method returns false, Model::Destroy() 
		 * will be halted and false returned.
		 *
		 * @returns bool True if the object can be destroyed, false if not
		 */
		protected function OnBeforeDestroy()
		{
			// Default return true if this method is not extended:
			return( true );
		
		} // OnBeforeDestroy()
		
		
		/**
		 * Triggered after a call to CRUD::Destroy(). If this method returns false, Model::Destroy() 
		 * will return false. It is up to the user at this point to take the necessary actions, such
		 * as rolling back the database transaction.		 
		 *
		 * @returns bool True if the object can be destroyed, false if not
		 */
		protected function OnAfterDestroy()
		{
			// Default return true if this method is not extended:
			return( true );
		
		} // OnBeforeDestroy()
		
		
		/**
		 * Triggered before a call to CRUD::Save(), for both INSERT and UPDATE actions. If this method
		 * returns false, Model::Save() will be halted and false returned.
		 *
		 * @returns bool True if the object can be saved, false if not
		 */	
		protected function Validate()
		{
			// Default return true if this method is not extended:
			return( true );
			
		} // Validate()
		

		/**
		 * Triggered before a call to CRUD::Save(), for INSERT only. If this method returns false, 
		 * Model::Save() will be halted and false returned.
		 *
		 * @returns bool True if the object can be saved, false if not
		 */	
		protected function ValidateInsert()
		{
			// Default return true if this method is not extended:
			return( true );
			
		} // ValidateInsert()
		
		
		/**
		 * Triggered before a call to CRUD::Save(), for UPDATE only. If this method returns false, 
		 * Model::Save() will be halted and false returned.
		 *
		 * @returns bool True if the object can be saved, false if not
		 */	
		protected function ValidateUpdate()
		{
			// Default return true if this method is not extended:
			return( true );
		
		} // ValidateUpdate()
	
	} // Model()

?>
