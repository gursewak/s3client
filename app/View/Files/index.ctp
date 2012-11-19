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
				<?php echo $key; ?>
			</td>
		<td>
			 <a href="<?php echo "http://".$bucketName.".s3.amazonaws.com/".$key; ?>" target="_blank">Download</a>
		</td>
	</tr>
<?php 
		}
	}
?>
</table>
 <a href="<?php echo  $this->Html->url(array('controller'=>'files','action'=>'upload'))?>">Upload File</a>