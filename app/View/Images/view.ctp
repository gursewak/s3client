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
					<img src="<?php echo "http://".$bucketName.".s3.amazonaws.com/images/thumbnailimage/".$image['Image']['filename']; ?>" />
				<br />
				<br />
				<img src="<?php echo "http://".$bucketName.".s3.amazonaws.com/images/120x263/".$image['Image']['filename']; ?>" />
				<br />
				<br />
				<img src="<?php echo "http://".$bucketName.".s3.amazonaws.com/images/300x180/".$image['Image']['filename']; ?>" />
				<br />
				<br />
				<img src="<?php echo "http://".$bucketName.".s3.amazonaws.com/images/original/".$image['Image']['filename']; ?>" />			
				
			</td>
	</tr>
	
</table>