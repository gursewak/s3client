<?php
class Image extends AppModel {
	var $name = 'Image';
	
	// In your model attach the Upload Behavior
	//var $actsAs = array('Upload');
	var $actsAs = array('Upload'=>array(
											 'filename' => array(	
												// Other options as explained below
												's3_acl'             => 'public-read',
											 	//'formfield'=>'filename',
												'allowed_ext'        => array('jpg', 'jpeg', 'png', 'gif'),
												'thumbnailimage' => array('width'=>60, 'height'=>45),
												'120x263' => array('width'=>120, 'height'=>263),
												'300x180' => array('width'=>300, 'height'=>180)
											 )
										));	
	 
}
?>