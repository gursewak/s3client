<?php

class S3Behavior extends ModelBehavior {
    /**
     * Variable to hold the files to be upload to S3
     *
     * @var array
     */
    public $files = array();
    
    /**
     * AWS access key
     *
     * @var string
     */
    protected $__accessKey = '';
    
    /**
     * AWS secret key
     *
     * @var string
     */
    protected $__secretKey = '';
    
    /**
     * Method called automatically by model's constructor
     *
     * @param object $model Object of model
     * @param array $settings Settings for behavior
     */
    function setup(&$model, $settings = array()) {
        
        // allow to use a config file in app/config/s3.php instead of editing the class directly.
        //Configure::load('s3');
		
        // Initialize behavior's default settings
        $default = array(
                    's3_access_key'      => Configure::read('awsAccessKey'),
                    's3_secret_key'      => Configure::read('awsSecretKey'),
                    'formfield'          => '',
                    's3_path'            => '',
                    'allowed_ext'        => array('jpg', 'jpeg', 'png', 'gif'),
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
                   );

        foreach ($settings as $field => $options) {
            // Merge behavior's default settings and model field's settings
            $settings = am($default, ife(is_array($options), $options, array()));
            // Put the settings in class variable
            $this->settings[$model->name][$field] = $settings;
        }
    }//end setup()
    
    /**
     * Convinient method to set AWS credentials
     *
     * @param string $accessKey AWS access key
     * @param string $secretKey AWS secret key
     */
    function setS3Credentials(&$model, $accessKey, $secretKey) {
        $this->__accessKey = $accessKey;
        $this->__secretKey = $secretKey;
    }//end setS3Credentials()

    /**
     * Method called automatically by model's save
     *
     * @param object $model Object of model
     * @return boolean Return's true if save should continue else false
     */
    function beforeSave(&$model) {
        foreach ($this->settings[$model->name] as $field => $options) {
            $formfield = $field;
            if (!empty($options['formfield'])) {
                $formfield = $options['formfield'];
            }
            // If the field is required and file name is empty then invalidate the field
            if ($options['required'] && empty($model->data[$model->name][$formfield]['name']) && empty($model->{$model->primaryKey})) {
                $model->invalidate($options['formfield'], 'required');
                return false;
            }
            // If no file was selected to upload then continue
            if (empty($model->data[$model->name][$formfield]['name'])) {
                unset($model->data[$model->name][$formfield]);
                continue;
            }
            // Self explainatory
            if (!is_uploaded_file($model->data[$model->name][$formfield]['tmp_name'])) {
                $model->invalidate($formfield, 'not_uploaded_file');
                return false;
            }

            // If no bucket for this field has been specified then invalidate the field
            if (empty($options['s3_bucket'])) {
                $model->invalidate($options['formfield'], 'missing_bucket');
                return false;
            }

            // Check if there is an error in file upload and invalidate the field accordingly
            if ($model->data[$model->name][$formfield]['error'] != 0) {
                switch($model->data[$model->name][$formfield]['error']) {
                    case 1:
                        $model->invalidate($formfield, 'php_max_filesize');
                        break;
                    case 2:
                        $model->invalidate($formfield, 'html_max_filesize');
                        break;
                    case 3:
                        $model->invalidate($formfield, 'partially_uploaded');
                        break;
                    case 4:
                    default:
                        $model->invalidate($formfield, 'no_file_uploaded');
                        break;
                }
                // Return false after invalidating field
                return false;
            }
            // Split the filename to get the name and extension separated
            preg_match("/(.+)\.(.*?)\Z/", $model->data[$model->name][$formfield]['name'], $matches);

            // If allowed_ext has been set then check that the selected file has a valid extension
            if(count($options['allowed_ext'])) {
                if (!in_array(low($matches[2]), $options['allowed_ext'])) {
                    $model->invalidate($formfield, 'forbidden_ext');
                    return false;
                }
            }
            
            App::import('Core', 'Sanitize');
            // Sanitize the filename. We will only keep letters, numbers, (.), - and _ in filename
            $filename = Sanitize::paranoid($model->data[$model->name][$formfield]['name'], array('.', '-', '_'));
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
            if (!empty($model->{$model->primaryKey})) {
                // Get the current filename
                $oldFilename = $model->field($model->name . '.' . $field);
                $uniqueConditions[$model->name . '.' . $field . ' <>'] = $model->{$model->primaryKey};
            }

            // Get unique filename only if append_salt is not true. append_salt should hopefully give unique filename anyways.
            // We will query the db table to see if filename already exists
            if ($options['unique'] && !$options['append_salt']) {
                $uniqueConditions[$model->name . '.' . $field] = $filename;
                $i = 1;
                while ($model->hasAny($uniqueConditions)) {
                    $filename = $matches[1] . '-' . $i++ . '.' . $matches[2];
                    $uniqueConditions[$model->name . '.' . $field] = $filename;
                }
            }

            // Put the file in queue to be uploaded to S3
            $this->files[$field] = array(
                                    'tmp_name'     => $model->data[$model->name][$formfield]['tmp_name'],
                                    'name'         => $filename,
                                    'old_filename' => $oldFilename,
                                    );
        }
            
        return $this->__uploadToS3($model);
    }//end beforeSave()
        
    /**
     * Method to upload file to S3.
     * This method also deletes the old files from S3.
     *
     * @param object $model Object of current model
     * @return boolean
     */
    function __uploadToS3(&$model) {
        App::import('Vendor', 'S3', array('file' => 'S3.php'));

        // Run a loop on all files to be uploaded to S3
        foreach ($this->files as $field => $file) {
            $accessKey = $this->__accessKey;
            $secretKey = $this->__secretKey;
            // If we have S3 credentials for this field/file
            if (!empty($this->settings[$model->name][$field]['s3_access_key']) && !empty($this->settings[$model->name][$field]['s3_secret_key'])) {
                $accessKey = $this->settings[$model->name][$field]['s3_access_key'];
                $secretKey = $this->settings[$model->name][$field]['s3_secret_key'];
            }
            // Instantiate the class
            $aws = new S3($accessKey, $secretKey);
            // If there is an old file to be removed
            if (!empty($file['old_filename'])) {
                $aws->deleteObject($this->settings[$model->name][$field]['s3_bucket'], $file['old_filename']);
            }
            // Put the object on S3
            $isUploaded = $aws->putObject(
                           $aws->inputResource(fopen($file['tmp_name'], 'rb'), filesize($file['tmp_name'])),
                           $this->settings[$model->name][$field]['s3_bucket'],
                           $file['name'],
                           $this->settings[$model->name][$field]['s3_acl'],
                           $this->settings[$model->name][$field]['s3_meta_headers'],
                           $this->settings[$model->name][$field]['s3_request_headers']
                          );
            // If S3 upload failed then set the model error
            if ($isUploaded == false) {
                $model->invalidate($this->settings[$model->name][$field]['formfield'], 's3_upload_error');
                return false;
            }
            // Set the field values to be saved in table
            $model->data[$model->name][$field] = $file['name'];
        }
        return true;
    }//end __uploadToS3()

    /**
     * Method called automatically by model's delete
     *
     * @param object $model Object of model
     * @return boolean Return's true if delete should continue, false otherwise
     */
    function beforeDelete(&$model) {
        App::import('Vendor', 'S3', array('file' => 'S3.php'));
        
        foreach ($this->settings[$model->name] as $field => $options) {
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
            $filename = $model->field($model->name . '.' . $field);

            // If filename is found then delete original photo
            if (!empty($filename)) {
                $aws->deleteObject($options['s3_bucket'], $filename);
            }
        }
        // Return true by default
        return true;
    }//end beforeDelete()
    
}//end class