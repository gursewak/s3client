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
												'thumbsizes' => array('width'=>200, 'height'=>200)
											 )
										));	
	 
}
?>