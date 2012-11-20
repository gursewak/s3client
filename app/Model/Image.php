<?php
class Image extends AppModel {
	var $name = 'Image';
	
	// In your model attach the Upload Behavior
	//var $actsAs = array('Upload');	
										
	var $actsAs = array('Upload'=>array(
											 'filename' => array(	
											 
													'sizes' => array(														
														'thumb' => array(
															'width' => 50,
															'height' => 50
														),
														'120x263' => array(
															'width' => 120,
															'height' => 263
														),
														'300x180' => array(
															'width' => 300,
															'height' => 180
														)
														
													),
													
													'storage' => array(
														'engine'=>'s3',
														'options' => array(
															's3_acl'             => 'public-read',
															'allowed_ext'        => array('jpg', 'jpeg', 'png', 'gif'),
														)
													)
										)
								));
	 
}
?>