<h1>
	Image View
 	<a href="<?php echo  $this->Html->url(array('controller'=>'images','action'=>'index'))?>" style="float:right;">Images List</a>
 </h1>
<table cellpadding="0" cellspacing="0">
	<tr>
		<th><?php echo $image['Image']['filename'];?></th>
	</tr>
		
		<tr>
			<td>
				<?php 
					if(isset($setUploadFolderInfo) && !empty($setUploadFolderInfo)){
						foreach($setUploadFolderInfo as $key=>$val){
							foreach($val as $key1=>$val1){
								preg_match("/(.+)\.(.*?)\Z/", $image['Image']['filename'], $matches);
				?>
								<img src="<?php echo "http://".$bucketName.".s3.amazonaws.com/images/".$image['Image']['id']."/".$key1.".".$matches[2]; ?>" />
								<br />
								<br />	
				<?php }
						}
				?>
				<?php 
					}
				?>
				
			</td>
	</tr>
	
</table>