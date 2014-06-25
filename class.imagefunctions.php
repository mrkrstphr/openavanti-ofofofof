<?php
/***************************************************************************************************
 * OpenAvanti
 *
 * OpenAvanti is an open source, object oriented framework for PHP 5+
 *
 * @author			Kristopher Wilson
 * @dependencies 	FileFunctions
 * @copyright		Copyright (c) 2008, Kristopher Wilson
 * @license			http://www.openavanti.com/license
 * @link				http://www.openavanti.com
 * @version			0.6.4-alpha
 *
 */
 
 
	/**
	 * A library for manipulating images
	 *
	 * @category	Images
	 * @author		Kristopher Wilson
	 * @link			http://www.openavanti.com/docs/imagefunctions
	 */
	class ImageFunctions
	{
	
		/**
		 * This method creates a thumbnail image of the supplied image, assuming it exists,
		 * based on the supplied width and height. The generated thumbnail will not be
		 * these exact dimensions, but instead is generated based on these rules:
		 * 
		 * 1. If the width and height of the image are less than the supplied with and height for
		 *    the thumbnail, then the actual size of the image is used.
		 * 2. If the ratio of the max height / current height is < current height, then
		 *    an image is generated with a height of height * ratio and width of max width.
		 * 3. If the ratio of the max width / current width is < current width, then
		 *    an image is generated with a width of width * ratio and height of max height.		 		 		 		 		 		 		 		 
		 * 
		 * @argument string The path and file name to the file to create a thumbnail from
		 * @argument string The path and file name of the thumbnail to create
		 * @argument array An array of width and height to limit the image to 0 => x, 1 => y
		 * @returns bool True if the thumbnail is created, false otherwise
		 */
		public static function GenerateThumb( $sFileName, $sThumbName, $aSize )
		{
			$iMaxWidth = $aSize[ 0 ];
			$iMaxHeight = $aSize[ 1 ];
			
			$sExtension = FileFunctions::GetFileExtension( $sFileName );
			
			if( !file_exists( $sFileName ) )
			{	
				return( false );
			}
		
			$rImage = null;
			
			switch( $sExtension )
			{
				case "jpg":
				case "jpeg":
					$rImage = imagecreatefromjpeg( $sFileName );
				break;
				
				case "gif":
					$rImage = imagecreatefromgif( $sFileName );
				break;
				
				case "png":
					$rImage = imagecreatefrompng( $sFileName );
				break;
				
				default:
					throw new Exception( "Invalid image type: {$sExtension}" );
			}
		
			$aImage = getimagesize( $sFileName );

			list( $iWidth, $iHeight, $sType, $sAttr ) = $aImage;

			$fXRatio = $iMaxWidth / $iWidth;
			$fYRatio = $iMaxHeight / $iHeight;

			if( ( $iWidth <= $iMaxWidth ) && ( $iHeight <= $iMaxHeight ) )
			{
				$iNewWidth = $iWidth;
				$iNewHeight = $iHeight;
			}
			else if( ( $fXRatio * $iHeight ) < $iMaxHeight )
			{
				$iNewHeight = ceil( $fXRatio * $iHeight );
				$iNewWidth = $iMaxWidth;
			}
			else
			{
				$iNewWidth = ceil( $fYRatio * $iWidth );
				$iNewHeight = $iMaxHeight;
			}
		
			$rImgThumb = imagecreatetruecolor( $iNewWidth, $iNewHeight );
		
			imagecopyresampled( $rImgThumb, $rImage, 0, 0, 0, 0, $iNewWidth,
				$iNewHeight, imagesx( $rImage ), imagesy( $rImage ) );

			switch( $sExtension )
			{
				case "jpg":
				case "jpeg":
					imagejpeg( $rImgThumb, $sThumbName );
				break;
				
				case "gif":
					imagegif( $rImgThumb, $sThumbName );
				break;
				
				case "png":
					imagepng( $rImgThumb, $sThumbName );
				break;
			}
		
			imagedestroy( $rImgThumb );
			imagedestroy( $rImage );
			
			
			return( true );			
			
		} // GenerateThumb()
		
		
	} // ImageFunctions()

?>
