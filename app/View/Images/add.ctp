<?php
	echo $this->Form->create('Image', array('enctype'=>'multipart/form-data'));
		echo $this->Form->input('filename', array('label'=>'Upload File:', 'type'=>'file'));
		echo $this->Form->submit();
	echo $this->Form->end();
?>
<a href="<?php echo  $this->Html->url(array('controller'=>'images','action'=>'index'))?>">Files List</a>