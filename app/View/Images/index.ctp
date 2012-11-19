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
				<b><?php echo $val['Image']['filename']; ?></b>
			</td>
		<td>
			<a href="<?php echo $this->Html->url(array('controller'=>'images','action'=>'edit',$val['Image']['id'])) ?>">Edit</a>&nbsp;|&nbsp;
			<a href="<?php echo $this->Html->url(array('controller'=>'images','action'=>'view',$val['Image']['id'])) ?>">View</a>&nbsp;|&nbsp;			
			<a href="<?php echo $this->Html->url(array('controller'=>'images','action'=>'delete',$val['Image']['id'])) ?>">Delete</a>&nbsp;|&nbsp;
			<a href="<?php echo "http://".$bucketName.".s3.amazonaws.com/images/original/".$val['Image']['filename']; ?>" target="_blank">Download</a>
		</td>
	</tr>
<?php 
		}
	}
?>
</table>
<a href="<?php echo  $this->Html->url(array('controller'=>'images','action'=>'add'))?>">Upload File</a>