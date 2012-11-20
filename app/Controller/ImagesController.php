<?php
	class ImagesController extends AppController {
	
		var $name = 'Images';
		
		public function index(){
			$this->set('bucketName', Configure::read('awsBucketName'));
			$this->set('bucketData', $this->Image->find('all'));
		}
	
		public function add() {
			if(!empty($this->data)) {	//Check Data not empty if 1
			
				// if(!empty($this->data['Image']['filename']['name']) && !empty($this->data['Image']['filename']['tmp_name'])){  //check file has value if 2
				 		//$this->Image->setS3Credentials(Configure::read('awsAccessKey'), Configure::read('awsSecretKey')); 
						$this->Image->save($this->data);
						$this->redirect(array('action'=>'index'));
				// }else{
				 	//$this->Session->setFlash(__('please upload image'));
				// } //end of if 2
			}  //end of if 1
		}
		
		public function edit($id=null){
			if (!$id) {
				$this->Session->setFlash(__('Invalid id for Image'));
				$this->redirect(array('action'=>'index'));
			}
			
			if(!empty($this->request->data)) {			
				 if(!empty($this->data['Image']['filename']['name']) && !empty($this->data['Image']['filename']['tmp_name'])){
						$this->Image->save($this->data);
						$this->redirect(array('action'=>'index'));
				 }else{
				 	$this->Session->setFlash(__('please upload image'));
				 } 
			}if (empty($this->request->data)) {
				$this->request->data = $this->Image->read(null, $id);
			}
		}
		
		public function view($id=null){
			if (!$id) {
				$this->Session->setFlash(__('Invalid id for Image'));
				$this->redirect(array('action'=>'index'));
			}
			$this->set('image', $this->Image->read(null,$id));
			$this->set('bucketName', Configure::read('awsBucketName'));
			$this->set('setUploadFolderInfo', $this->Image->getUploadFolderInfo());
		}
		
		public function delete($id=null){
			if (!$id) {
				$this->Session->setFlash(__('Invalid id for Image'));
				$this->redirect(array('action'=>'index'));
			}
			if ($this->Image->delete($id)) {
				$this->Session->setFlash(__('Image deleted'));
				$this->redirect(array('action'=>'index'));
			}
			
		}		
	}
?>