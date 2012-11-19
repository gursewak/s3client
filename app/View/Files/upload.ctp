<?php
	echo $this->Form->create(false, array('enctype'=>'multipart/form-data'));
		echo $this->Form->input('image', array('label'=>'Upload File:', 'type'=>'file'));
		echo $this->Form->submit();
	echo $this->Form->end();
?>
<a href="<?php echo  $this->Html->url('/')?>">Files List</a>