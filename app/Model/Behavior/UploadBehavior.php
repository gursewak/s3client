<?php
class UploadBehavior extends ModelBehavior {
    /**
     * Variable to hold the files to be upload to S3
     *
     * @var array
     */
    var $files = array();
    
    /**
     * AWS access key
     *
     * @var string
     */
    var $__accessKey = '';
    
    /**
     * AWS secret key
     *
     * @var string
     */
    var $__secretKey = '';
    
    /**
     * Method called automatically by model's constructor
     *
     * @param object $Model Object of model
     * @param array $settings Settings for behavior
     */
		
	 function setup(Model $Model, $settings = array()) {
       $this->settings[$Model->alias] = array();
		
        // Initialize behavior's default settings
        $default = array(
                    's3_access_key'      => Configure::read('awsAccessKey'),
                    's3_secret_key'      => Configure::read('awsSecretKey'),
                    'formfield'          => '',
                    's3_path'            => '',
                    'allowed_ext'        => array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico'),
                    's3_request_headers' => array(
                                             'Expires'       => 'Fri, 30 Oct 2030 14:19:41 GMT', //Far future date
                                             'Cache-control' => 'public',
                                            ),
                    's3_meta_headers'    => array(),
                    's3_acl'             => 'public-read',
                    'append_salt'        => false,
                    's3_bucket'          => Configure::read('awsBucketName'),
                    'required'           => false,
                    'unique'             => true,
					'thumbQuality'  =>  80,
					'120x263' => array(
						// Place any custom thumbsize in model config instead,
					),
					'300x180' => array(
						// Place any custom thumbsize in model config instead,
					),
					'thumbnailimage' => array(
						// Place any custom thumbsize in model config instead,
					)
       );
		if(!empty($settings)){
			foreach ($settings as $field => $options) {
				$settings = $this->_arrayMerge($default, $options);
				$this->settings[$Model->alias][$field] = $settings;
			}
		}else{
			$this->settings[$Model->alias]['filename'] = $default;
		}
    }//end setup()
    
    /**
     * Convinient method to set AWS credentials
     *
     * @param string $accessKey AWS access key
     * @param string $secretKey AWS secret key
     */
    function setS3Credentials(Model $Model, $accessKey, $secretKey) {
        $this->__accessKey = $accessKey;
        $this->__secretKey = $secretKey;
    }//end setS3Credentials()

    /**
     * Method called automatically by model's save
     *
     * @param object $Model Object of model
     * @return boolean Return's true if save should continue else false
     */
    function beforeSave(Model $Model) {
		foreach ($this->settings[$Model->alias] as $field => $options) {
        //foreach ($this->settings[$Model->name] as $field => $options) {
            $formfield = $field;
            if (!empty($options['formfield'])) {
                $formfield = $options['formfield'];
            }
            // If the field is required and file name is empty then invalidate the field
            if ($options['required'] && empty($Model->data[$Model->name][$formfield]['name']) && empty($Model->{$Model->primaryKey})) {
                $Model->invalidate($options['formfield'], 'required');
                return false;
            }
            // If no file was selected to upload then continue
            if (empty($Model->data[$Model->name][$formfield]['name'])) {
                unset($Model->data[$Model->name][$formfield]);
                continue;
            }
            // Self explainatory
            if (!is_uploaded_file($Model->data[$Model->name][$formfield]['tmp_name'])) {
                $Model->invalidate($formfield, 'not_uploaded_file');
                return false;
            }

            // If no bucket for this field has been specified then invalidate the field
            if (empty($options['s3_bucket'])) {
                $Model->invalidate($options['formfield'], 'missing_bucket');
                return false;
            }

            // Check if there is an error in file upload and invalidate the field accordingly
            if ($Model->data[$Model->name][$formfield]['error'] != 0) {
                switch($Model->data[$Model->name][$formfield]['error']) {
                    case 1:
                        $Model->invalidate($formfield, 'php_max_filesize');
                        break;
                    case 2:
                        $Model->invalidate($formfield, 'html_max_filesize');
                        break;
                    case 3:
                        $Model->invalidate($formfield, 'partially_uploaded');
                        break;
                    case 4:
                    default:
                        $Model->invalidate($formfield, 'no_file_uploaded');
                        break;
                }
                // Return false after invalidating field
                return false;
            }
            // Split the filename to get the name and extension separated
            preg_match("/(.+)\.(.*?)\Z/", $Model->data[$Model->name][$formfield]['name'], $matches);

            // If allowed_ext has been set then check that the selected file has a valid extension
            if(count($options['allowed_ext'])) {
                if (!in_array(strtolower($matches[2]), $options['allowed_ext'])) {
                    $Model->invalidate($formfield, 'forbidden_ext');
                    return false;
                }
            }
            
            App::import('Utility', 'Sanitize');
            // Sanitize the filename. We will only keep letters, numbers, (.), - and _ in filename
            $filename = Sanitize::paranoid($Model->data[$Model->name][$formfield]['name'], array('.', '-', '_'));
            // Again split the filename        
            preg_match("/(.+)\.(.*?)\Z/", $filename, $matches);
                    
            // Append a unique salt to the filename. This hopefully will give unique filenames
            if ($options['append_salt']) {
                $uniqueString = substr(md5(uniqid(mt_rand(), true)), 0, 8);
                $filename   = $matches[1].'-'.$uniqueString. '.' . $matches[2];
                $matches[1]    = $matches[1].'-'.$uniqueString;
            }
            
            // If the S3 path is set then append it to the filename. S3 has virtual directories
            if ($options['s3_path']) {
                if (substr($options['s3_path'], -1) != DS) {
                    $options['s3_path'] .= DS;
                }
                $filename = $options['s3_path'] . $filename;
                $matches[1] = $options['s3_path'] . $matches[1];
            }

            // If this is an update operation and file is being replaced then we need to remove earlier one
            $oldFilename = '';
            if (!empty($Model->{$Model->primaryKey})) {
                // Get the current filename
                $oldFilename = $Model->field($Model->name . '.' . $field);
                $uniqueConditions[$Model->name . '.' . $field . ' <>'] = $Model->{$Model->primaryKey};
            }

            // Get unique filename only if append_salt is not true. append_salt should hopefully give unique filename anyways.
            // We will query the db table to see if filename already exists
            if ($options['unique'] && !$options['append_salt']) {
                $uniqueConditions[$Model->name . '.' . $field] = $filename;
                $i = 1;
                while ($Model->hasAny($uniqueConditions)) {
                    $filename = $matches[1] . '-' . $i++ . '.' . $matches[2];
                    $uniqueConditions[$Model->name . '.' . $field] = $filename;
                }
            }

            // Put the file in queue to be uploaded to S3
            $this->files[$field] = array(
                                    'tmp_name'     => $Model->data[$Model->name][$formfield]['tmp_name'],
                                    'name'         => $filename,
                                    'old_filename' => $oldFilename,
                                    );
        }
            
        return $this->__uploadToS3($Model);
    }//end beforeSave()
        
    /**
     * Method to upload file to S3.
     * This method also deletes the old files from S3.
     *
     * @param object $Model Object of current model
     * @return boolean
     */
    function __uploadToS3(Model $Model) {
        App::import('Vendor', 'S3', array('file' => 'S3.php'));

        // Run a loop on all files to be uploaded to S3
        foreach ($this->files as $field => $file) {
            $accessKey = $this->__accessKey;
            $secretKey = $this->__secretKey;
            // If we have S3 credentials for this field/file
            if (!empty($this->settings[$Model->name][$field]['s3_access_key']) && !empty($this->settings[$Model->name][$field]['s3_secret_key'])) {
                $accessKey = $this->settings[$Model->name][$field]['s3_access_key'];
                $secretKey = $this->settings[$Model->name][$field]['s3_secret_key'];
            }
            // Instantiate the class
            $aws = new S3($accessKey, $secretKey);
			//$Model->useTable
			//$Model->table			
			$setModelTableName =  $this->getModelTableName($Model);
			$setUploadFolderInfo =  $this->getUploadFolderInfo($Model);
			
            // If there is an old file to be removed
            if (!empty($file['old_filename'])) {
                //$aws->deleteObject($this->settings[$Model->name][$field]['s3_bucket'], $setModelTableName.'/'.$file['old_filename']);
					if(!empty($setUploadFolderInfo)){
						foreach($setUploadFolderInfo as $key=>$val){
							foreach($val as $key1=>$val1){
								$aws->deleteObject($this->settings[$Model->name][$field]['s3_bucket'], $setModelTableName.'/'.$key1.'/'.$file['old_filename']);
							}
						}
					}
            }
			
			//Code For Image Resize
			$dir = 'tmpImage';
			
			// create new directory with 777 permissions if it does not exist yet
			// owner will be the user/group the PHP script is run under
			if ( !file_exists($dir) ) {
				mkdir ($dir, 0777);
				chmod($dir, 0777);
			}
			chmod($dir, 0777);
			
			App::import('Vendor','wide-image', array('file' => 'wideimage/lib/WideImage.php'));
				if(!empty($setUploadFolderInfo)){
					foreach($setUploadFolderInfo as $key=>$val){
						foreach($val as $key1=>$val1){
							if($key1!='original'){
								if(!empty($val1) && !empty($val1['width']) && !empty($val1['height'])){
									$width = $this->settings[$Model->name][$field][$key1]['width'];
									$height = $this->settings[$Model->name][$field][$key1]['height'];
									WideImage::load($file['tmp_name'])->resize($width, $height)->saveToFile('tmpImage/'.$file['name']);
											
										$isUploaded = $aws->putObjectFile(
											  WWW_ROOT.'tmpImage/'.$file['name'],
											   $this->settings[$Model->name][$field]['s3_bucket'],
												$setModelTableName.'/'.$key1.'/'.$file['name'],
											   $this->settings[$Model->name][$field]['s3_acl'],
											   $this->settings[$Model->name][$field]['s3_meta_headers'],
											   $this->settings[$Model->name][$field]['s3_request_headers']
										);
										@unlink(WWW_ROOT.'tmpImage/'.$file['name']);
								}
							}
						}
					}
				}
			rmdir($dir);
            // Put the object on S3
            //$isUploaded = $aws->putObject(
			   $isUploaded = $aws->putObjectFile(
                           //$aws->inputResource(fopen($file['tmp_name'], 'rb'), filesize($file['tmp_name'])),
						   $file['tmp_name'],
                           $this->settings[$Model->name][$field]['s3_bucket'],
                           $setModelTableName.'/original/'.$file['name'],
                           $this->settings[$Model->name][$field]['s3_acl'],
                           $this->settings[$Model->name][$field]['s3_meta_headers'],
                           $this->settings[$Model->name][$field]['s3_request_headers']
                );
            // If S3 upload failed then set the model error
            if ($isUploaded == false) {
                $Model->invalidate($this->settings[$Model->name][$field]['formfield'], 's3_upload_error');
                return false;
            }
            // Set the field values to be saved in table
            $Model->data[$Model->name][$field] = $file['name'];
        }
        return true;
    }//end __uploadToS3()
	
    /**
     * Method called automatically by model's delete
     *
     * @param object $Model Object of model
     * @return boolean Return's true if delete should continue, false otherwise
     */
    function beforeDelete(Model $Model) {
        App::import('Vendor', 'S3', array('file' => 'S3.php'));
        
        foreach ($this->settings[$Model->alias] as $field => $options) {
            $accessKey = $this->__accessKey;
            $secretKey = $this->__secretKey;
            // If we have S3 credentials for this field/file
            if (!empty($options['s3_access_key']) && !empty($options['s3_secret_key'])) {
                $accessKey = $options['s3_access_key'];
                $secretKey = $options['s3_secret_key'];
            }
            // Instantiate the class
            $aws = new S3($accessKey, $secretKey);
            // Get model's data for filename of photo
            $filename = $Model->field($Model->name . '.' . $field);
			$setModelTableName =  $this->getModelTableName($Model);
			$setUploadFolderInfo =  $this->getUploadFolderInfo($Model);

            // If filename is found then delete original photo
            if (!empty($filename)) {				
				if(!empty($setUploadFolderInfo)){
					foreach($setUploadFolderInfo as $key=>$val){
						foreach($val as $key1=>$val1){
							$aws->deleteObject($options['s3_bucket'], $setModelTableName.'/'.$key1.'/'.$filename);
						}
					}
				}
            }
        }
        // Return true by default
        return true;
    }//end beforeDelete()
	
	function getUploadFolderInfo(Model $Model) {
		$setTmpFolderInfo =array();
		foreach ($this->settings[$Model->alias] as $field => $options) {
			if(isset($this->settings[$Model->name][$field]['thumbnailimage']) && !empty($this->settings[$Model->name][$field]['thumbnailimage'])){
				$tmpArray =array();
				$tmpArray['thumbnailimage'] = $this->settings[$Model->name][$field]['thumbnailimage'];
				array_push($setTmpFolderInfo,$tmpArray);
			}
			if(isset($this->settings[$Model->name][$field]['120x263']) && !empty($this->settings[$Model->name][$field]['120x263'])){
				$tmpArray =array();
				$tmpArray['120x263'] = $this->settings[$Model->name][$field]['120x263'];
				array_push($setTmpFolderInfo,$tmpArray);
			}
			if(isset($this->settings[$Model->name][$field]['300x180']) && !empty($this->settings[$Model->name][$field]['300x180'])){
				$tmpArray =array();
				$tmpArray['300x180'] = $this->settings[$Model->name][$field]['300x180'];
				array_push($setTmpFolderInfo,$tmpArray);
			}
			
				$tmpArray =array();
				$tmpArray['original'] = 'original';
				array_push($setTmpFolderInfo,$tmpArray);
		}
		return $setTmpFolderInfo;
	}
	
	function getModelTableName(Model $Model) {
		return $Model->table;
	}
	
	function _arrayMerge($arr, $ins) {
		if (is_array($arr)) {
			if (is_array($ins)) {
				foreach ($ins as $k => $v) {
					if (isset($arr[$k]) && is_array($v) && is_array($arr[$k])) {
						$arr[$k] = $this->_arrayMerge($arr[$k], $v);
					} elseif (is_numeric($k)) {
						array_splice($arr, $k, count($arr));
						$arr[$k] = $v;
					} else {
						$arr[$k] = $v;
					}
				}
			}
		} elseif (!is_array($arr) && (strlen($arr) == 0 || $arr == 0)) {
			$arr = $ins;
		}
		return $arr;
	}
    
}//end class