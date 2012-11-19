<?php
	echo $this->Form->create('Image', array('enctype'=>'multipart/form-data'));
		echo $this->Form->input('id');
		echo $this->Form->input('filename', array('label'=>'Upload File:', 'type'=>'file'));
		echo '<b>'.$this->data['Image']['filename'].'</b>';
		echo $this->Form->submit();
	echo $this->Form->end();
?>
<a href="<?php echo  $this->Html->url(array('controller'=>'images','action'=>'index'))?>">Files List</a>