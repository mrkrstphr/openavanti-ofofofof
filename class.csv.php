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
 */
 
	/**
	 * Simple object to aid in creating CSV documents
	 *
	 * @category	CSV
	 * @author		Kristopher Wilson
	 * @link		http://www.openavanti.com/docs/csv
	 */
	class CSV
	{
		public $aHeaders = array();	
		public $aData = array();
		
		
		/**
		 * Adds a header to the list of CSV column headers. This method appends to the current list 
		 * of headers by adding the single header.		 
		 *
		 * @argument string The name of the header to add to the list of column headers		 
		 * @returns void		 
		 */		 		 		
		public function AddHeader( $sHeader )
		{
			$this->aHeaders[] = $sHeader;
			
		} // AddHeader()
		
		
		/**
		 * Adds an array of headers to the list of CSV column headers. This method appends to the 
		 * current list of headers by adding the passed array of headers to the existing array of
		 * headers already added.
		 *                   		 
		 * @argument array An array of headers to append to the current array of headers
		 * @returns void
		 */
        public function AddHeaders( $aHeaders )
        {
            if( is_array( $aHeaders ) && !empty( $aHeaders ) )
            {
                $this->aHeaders += $aHeaders;
            }
            
        } // AddHeaders()                        	
		
		
		
		/**
		 * Adds the supplied array of data to the CSV document. If the number of columns in the 
		 * data does not match the number of columns in the headers (unless there are no headers),
		 * this method will throw an exception.		 
		 * 
		 * @argument array An array of CSV row data
		 * @returns void
		 */		 		 		
		public function AddData( $aData )
		{
            if( !empty( $this->aHeaders ) && count( $aData ) != count( $this->aHeaders ) )
            {
                throw new Exception( "Data column count does not match header column " . 
                    "count in CSV data" );
            }
            
			$this->aData[] = $aData;
			
		} // AddData()
		
		
		/**
		 * This method takes the headers and data stored in this object and creates a CSV
		 * document from that data.		 
		 * 		 
		 * @returns string The headers and data supplied as a string formatted as a CSV document
		 */		 		 
		public function __toString()
		{
            $sData = "";
            
            // If headers are supplied, add them to the CSV string:
            
            if( !empty( $this->aHeaders ) )
            {
                $sData = implode( ",", $this->aHeaders ) . "\n";
            }
            
            // Loop each row and convert it to a row of CSV data and add it to the CSV string:
            
			foreach( $this->aData as $aData )
			{
				$sDataRow = "";
				
				foreach( $aData as $sDataElement )
				{
					$sDataElement = str_replace( array( "\n", "\"" ), 
						array( " ", "\"\"" ), $sDataElement );
					
					$sDataRow .= !empty( $sDataRow ) ? "," : "";
					$sDataRow .= "\"{$sDataElement}\"";
				}
				
				$sData .= "{$sDataRow}\n";
			}
			
			return( $sData );
		
		} // __toString()
	
	} // CSV()

?>
