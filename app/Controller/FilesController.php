<?php
	class FilesController extends AppController {
		var $name = 'Files';
		
		
		public function upload() {
		
			if(!empty($this->data)) {	//Check Data not empty if 1
			
				 if(!empty($this->data['image']['name']) && !empty($this->data['image']['tmp_name'])){  //check file has value if 2
						
					$setFileName=$this->clean_url($this->data['image']['name']);
					
					//Upload File On S3 here
						$s3 =$this->simpleStorageService(); // gettting here S3 Object
						if ($s3->putObjectFile($this->data['image']['tmp_name'], Configure::read('awsBucketName'), $setFileName, S3::ACL_PUBLIC_READ)) {
							$this->redirect('/');
						} else {
							echo 'Not Succes';
						}
						
				 } //end of if 2
			}  //end of if 1
		}
		
		public function index(){
			$s3 =$this->simpleStorageService(); // gettting here S3 Object
			$this->set('bucketName', Configure::read('awsBucketName'));
			$this->set('bucketData', $s3->getBucket(Configure::read('awsBucketName')));
		}	
	}
?>