<h1>Files List</h1>

<table cellpadding="0" cellspacing="0">
	<tr>
		<th>File Name</th>
		<th><?php echo __('Actions');?></th>
	</tr>
	<?php
		if(!empty($bucketData)){
			$i = 0;
			foreach ($bucketData as $key=>$val){
	?>
		<tr>
			<td>
				<a href="<?php echo "http://".$bucketName.".s3.amazonaws.com/".$val['Image']['filename']; ?>" target="_blank">
					<img src="<?php echo "http://".$bucketName.".s3.amazonaws.com/".$val['Image']['filename']; ?>" width="60" height="60" />
				</a><?php echo $val['Image']['filename']; ?>
			</td>
		<td>
			 <a href="<?php echo "http://".$bucketName.".s3.amazonaws.com/".$val['Image']['filename']; ?>" target="_blank">Download</a>
		</td>
	</tr>
<?php 
		}
	}
?>
</table>
 <a href="<?php echo  $this->Html->url(array('controller'=>'images','action'=>'add'))?>">Upload File</a>