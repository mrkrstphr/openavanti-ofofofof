<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	SimpleXML
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link				http://www.openavanti.com
 * @version			0.6.4-alpha
 *
 */
 
 
	/**
	 * This class extends the SimpleXMLElement class in PHP and adds a few extra methods to aid
	 * in the XML DOM manipulation.
	 *
	 * @category	XML
	 * @author		Kristopher Wilson
	 * @link			http://www.openavanti.com/docs/simplexmlelementext
	 */
	class SimpleXMLElementExt extends SimpleXMLElement
	{
	
		/**
		 * Adds a child SimpleXMLElement node as a child of this node. This differs from the native
		 * addChild() method in that it allows adding an XML node, not creating a tag.
		 *
		 * @argument SimpleXMLElement The node to add as a child of this node
		 * @returns void
		 */
		public function addChildNode( SimpleXMLElement $oChild ) 
		{
			$oParentDOM = dom_import_simplexml( $this );
			$oChildDOM = dom_import_simplexml( $oChild );
			$oNewParentDOM = $oParentDOM->ownerDocument->importNode( $oChildDOM, true );
			$oParentDOM->appendChild( $oNewParentDOM );
		
		} // addChildNode()
	
	
		/**
		 * Clones this node recursively and returns the cloned node.
		 *
		 * @returns SimpleXMLElementExt A copy of the current node
		 */
		public function cloneNode()
		{
			$oDomNode = dom_import_simplexml( $this );
			$oNewNode = $oDomNode->cloneNode( true );
			
			return( simplexml_import_dom( $oNewNode, "SimpleXMLElementExt" ) );			
			
		} // cloneNode()
		
		
		public function removeChild( $oChild )
		{
			$oParentDOM = dom_import_simplexml( $this );
			$oChildDOM = dom_import_simplexml( $oChild );
			
			$oParentDOM->removeChild( $oChildDOM );
			
		} // removeChild()
		
		
		public function removeAttributeNS( $sNS, $sAttribute )
		{
			$oDOM = dom_import_simplexml( $this );
			
			$oDOM->removeAttributeNS( $sNS, $sAttribute );
			
		} // removeAttributeNS()
		
		
		public function removeAttribute( $sAttribute )
		{
			$oDOM = dom_import_simplexml( $this );
			
			$oDOM->removeAttribute( $sAttribute );
			
		} // removeAttribute()
		
		
		public function addAttributeNS( $sNS, $sAttribute, $sValue )
		{
			$oDOM = dom_import_simplexml( $this );
			
			$oDOM->setAttributeNS( $sNS, $sAttribute, $sValue );
			
		} // removeAttributeNS()
		
		
		public function insertBefore( $oNewNode, $oRefNode )
		{
            $oDOM = dom_import_simplexml( $this );
            
            $oNewNodeDOM = dom_import_simplexml( $oNewNode );
            $oRefNodeDOM = dom_import_simplexml( $oRefNode );            
            
            $oNewNodeDOM = $oDOM->ownerDocument->importNode( $oNewNodeDOM, true );
            
            $oDOM->insertBefore( $oNewNodeDOM, $oRefNodeDOM );
            
		} // insertBefore()
		
		
		public function insertAfter( $oNewNode, $oRefNode )
        {
            $oDOM = dom_import_simplexml( $this );
            
            $oNewNodeDOM = dom_import_simplexml( $oNewNode );
            $oRefNodeDOM = dom_import_simplexml( $oRefNode );
            
            $oNewNodeDOM = $oDOM->ownerDocument->importNode( $oNewNodeDOM, true );
            
            $oDOM->insertBefore( $oNewNodeDOM, $oRefNodeDOM->nextSibling );
            
        } // insertAfter()
		
		
		public function hasChildNodes()
		{
            $oDOM = dom_import_simplexml( $this );
            
            return( $oDOM->hasChildNodes() );
		}
		
		
		public function getParent()
		{
            $oParent = current( $this->xpath( ".." ) );
            
            return( $oParent );
            
		}
		
		
		
		
		

	}; // SimpleXMLElementExt()

?>
