<?php
	class ImagesController extends AppController {
	
		var $name = 'Images';
	
		public function add() {
			if(!empty($this->data)) {	//Check Data not empty if 1
			
				 if(!empty($this->data['Image']['filename']['name']) && !empty($this->data['Image']['filename']['tmp_name'])){  //check file has value if 2
				 		//$this->Image->setS3Credentials(Configure::read('awsAccessKey'), Configure::read('awsSecretKey')); 
						$this->Image->save($this->data);
						$this->redirect(array('action'=>'index'));
				 } //end of if 2
			}  //end of if 1
		}
		
		public function index(){
			$this->set('bucketName', Configure::read('awsBucketName'));
			$this->set('bucketData', $this->Image->find('all'));
		}		
	}
?>