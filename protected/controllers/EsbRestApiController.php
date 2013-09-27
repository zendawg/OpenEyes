<?php

/**
 * Taken and adapted from:
 *  http://www.yiiframework.com/wiki/175/how-to-create-a-rest-api/
 */
class EsbRestApiController extends RestfulController {

    function getViewableModels() {
        return array_merge($this->getCreatableModels(), Yii::app()->params['esb_rest_api_viewable']);
    }

    function getCreatableModels() {
        return $this->getUpdatableModels();
    }

    function getUpdatableModels() {
        return Yii::app()->params['esb_rest_api_updatable'];
    }

    function getSearchableModels() {
        return $this->getViewableModels();
    }

    public function getApplicationId() {
        return Yii::app()->params['esb_rest_api_id'];
    }

    function actionRest() {
        return parent::actionRest();
    }

    /**
     * Takes an XML source file that references a given image file, both
     * in the same directory.
     * 
     * If only source directory is set, images are imported 'is situ' - used
     * for example demonstrations.
     * 
     * When files are imported from an external source, specifying the destination
     * directory ensures the files are moved from source to destination.
     * 
     * The XML file is parsed for data such as image file name, patient ID,
     * DOB, eye, test type and strategy.
     */
    function actionImportHumphreyImageSet() {
        $this->_checkAuth();
        $dest_dir = null;
        $json = file_get_contents('php://input');
        $put_vars = CJSON::decode($json, true);

        if (!isset($put_vars['src_dir'])) {
            $this->_sendResponse(500, 'Error: Parameter src_dir is missing', "importHumphreyImageSet", "scan");
        } else {
            $src_dir = $put_vars['src_dir'];
        }
        if (!isset($put_vars['xml_file'])) {
            $this->_sendResponse(500, 'Error: Parameter xml_file is missing', "importHumphreyImageSet", "scan");
        } else {
            $xml_file = $put_vars['xml_file'];
        }
        if (isset($put_vars['dest_dir'])) {
            $dest_dir = $put_vars['dest_dir'];
        }
        $src_file = $src_dir . '/' . $xml_file;
        if (!file_exists($src_file)) {
            $this->_sendResponse(400, sprintf("Error: Could not locate XML file '%s'", $src_file), "importHumphreyImageSet", "scan");
        }
        $this->audit("importHumphreyImageSet", "scan", $json);

        $data = file_get_contents($src_file);
        try {
            $xml_data = $this->getXmlData($data);
        } catch (Exception $ex) {
            // need to move files to another (error) location
            $this->_sendResponse(400, sprintf("Error: parsing file '%s': '%s'", $src_file, $ex->getMessage()), "importHumphreyImageSet", "scan");
        }
//$this->_sendResponse(400, 'test', null, null);
	if (!array_key_exists('file_reference', $xml_data)) {
                copy($src_dir . '/' . $xml_data['file_reference'],  '/var/openeyes/hvf-err/' . $xml_data['file_reference']);
                unlink($src_dir . '/' . $xml_data['file_reference']);
		$this->_sendResponse(400, sprintf("Error: XML file %s contained no PID", $xml_data['file_reference']), "importHumphreyImageSet", "scan");
	}
        $image_file = $src_dir . '/' . $xml_data['file_reference'];
        if (!file_exists($image_file)) {
            // again, need to move XML file to another (error) location
            $this->_sendResponse(400, sprintf("Error: Could not find image file '%s' for XML source file '%s'", $xml_data['file_reference'], $src_file), "importHumphreyImageSet", "scan");
        }
        $uid = ScannedDocumentUid::model()->find('pid=\'' . $xml_data['recorded_pid'] . '\'');
        if (!$uid) {
            $uid = new ScannedDocumentUid();
            $uid->pid = $xml_data['recorded_pid'];
            $uid->save();
        }
        $xml_file_exists = false;
        $image_file_exists = false;
        // step 1 - move file:
        try {
            if ($dest_dir) {
                $dest_dir = $dest_dir . '/' . $xml_data['test_strategy'] . '/' . $uid->id;
                if (!file_exists($dest_dir)) {
                    mkdir($dest_dir, 0777, true);
                }
                // TODO check that destinations do not already exist; if they do,
                // copy them to -err
                // -- code here
                $xml_file_exists = file_exists($dest_dir . '/' . $xml_file);
                $image_file_exists = file_exists($dest_dir . '/' . $xml_data['file_reference']);
                $duplicates = '/var/openeyes/duplicates';
                if ($xml_file_exists) {
                    // TODO - what to do if the file exists in the location but
                    // the data has not been written to DB?
                    $dups = $this->addFsDirectory($duplicates);
                    $time = microtime(true);
                    copy($src_dir . '/' . $xml_file, $duplicates . '/' . $time . '.' . $xml_file);
                    unlink($src_dir . '/' . $xml_file);
                    $f = $this->addFile($time . '.' . $xml_file, $dups);
                    $this->audit("importHumphreyImageSet", "scan", "File already exists for patient: " . $dest_dir
                            . '/' . $xml_file . "; moving to duplicates directory");
                } else {
                    // it doesn't exist so move it to the correct directory:
                    copy($src_dir . '/' . $xml_file, $dest_dir . '/' . $xml_file);
                    unlink($src_dir . '/' . $xml_file);
                }
                if ($image_file_exists) {
                    // EITHER reject it and audit it, or move it and audit it:
                    // move it to duplicates:
                    $time = microtime(true);
                    $dups = $this->addFsDirectory($duplicates);
                    copy($src_dir . '/' . $xml_data['file_reference'], $duplicates . '/' . $time . '.' . $xml_data['file_reference']);
                    unlink($src_dir . '/' . $xml_data['file_reference']);
                    $this->addFile($time . '.' . $xml_data['file_reference'], $dups);
                    $this->audit("importHumphreyImageSet", "scan", "File already exists for patient: " . $dest_dir
                            . '/' . $xml_data['file_reference'] . "; moving to duplicates directory");
                } else {
                    // it doesn't exist so move it to the correct directory:
                    copy($src_dir . '/' . $xml_data['file_reference'], $dest_dir . '/' . $xml_data['file_reference']);
                    unlink($src_dir . '/' . $xml_data['file_reference']);
                }
            } else {
                // user has not specified dest; so dest and src are the same (import in situ):
                $dest_dir = $src_dir;
            }
        } catch (Exception $e) {
            $this->_sendResponse(400, sprintf("Error importing file: %s", $e->getMessage()), "importHumphreyImageSet", "scan");
        }
        // if the file does not yet exist, add it's details:
        if (!$xml_file_exists) {
            // step 2 - import file information:
            $dir = $this->addFsDirectory($dest_dir);
            $xmlFileImport = $this->addFile($xml_file, $dir);
            $xmlDataFileImport = $this->addXmlData($xmlFileImport->id, $xml_data);
        }
        // ELSE { // what? }
        // likewise, if the image does not exist, add it:
        if (!$image_file_exists && isset($xmlDataFileImport)) {
            $imageFileImport = $this->addFile($xml_data['file_reference'], $dir);

            $imageDataFileImport = new FsScanHumphreyImage;
            $imageDataFileImport->file_id = $imageFileImport->id;
            $imageDataFileImport->save();

            $xmlDataFileImport->tif_file_id = $imageDataFileImport->file_id;
            $xmlDataFileImport->save();
            if (!file_exists($dest_dir . '/thumbs')) {
                mkdir($dest_dir . '/thumbs', 0777, true);
            }
            try {
                exec('convert -crop 925x834+1302+520 ' . $dest_dir . '/' . $xml_data['file_reference']
                        . ' ' . $dest_dir . '/thumbs/' . $xml_data['file_reference'] . '.jpg');
                exec('convert -scale 300x306 ' . $dest_dir . '/thumbs/' . $xml_data['file_reference']
                        . '.jpg ' . $dest_dir . '/thumbs/' . $xml_data['file_reference'] . '.jpg');
            } catch (Exception $e) {
                $this->audit("importHumphreyImageSet", "scan", "Failed to create thumbnail images: " . $e->getMessage());
            }
        }
        // ELSE { // what? }
        if (!$xml_file_exists && !$image_file_exists) {
            $this->createHumphreyImagePairEvent($xml_data['recorded_pid'], $xmlDataFileImport->tif_file_id, $xmlDataFileImport->id, $xml_data['test_strategy']);
            $this->_sendResponse(200, sprintf("Success", $xmlDataFileImport->id), "text/html", "importHumphreyImageSet", "scan");
        }
        // ELSE { // what? }
    }

    /**
     * 
     * @param type $id
     * @param type $xml_data
     * @return \FsScanHumphreyXml
     */
    private function addXmlData($id, $xml_data) {

        $xmlDataFileImport = new FsScanHumphreyXml;
        $xmlDataFileImport->file_id = $id;
        $xmlDataFileImport->file_name = $xml_data['file_reference'];
        $xmlDataFileImport->pid = $xml_data['recorded_pid'];
        $xmlDataFileImport->study_date = $xml_data['study_date'];
        $xmlDataFileImport->study_time = $xml_data['study_time'];
        $xmlDataFileImport->given_name = $xml_data['given_name'];
        $xmlDataFileImport->family_name = $xml_data['family_name'];
        $xmlDataFileImport->middle_name = $xml_data['middle_name'];
        $xmlDataFileImport->eye = $xml_data['eye'];
        $xmlDataFileImport->test_name = $xml_data['test_name'];
        $xmlDataFileImport->test_strategy = $xml_data['test_strategy'];
        $xmlDataFileImport->birth_date = $xml_data['birth_date'];
        $xmlDataFileImport->gender = $xml_data['gender'];
        $xmlDataFileImport->save();
        return $xmlDataFileImport;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    private function getXmlData($data) {

        $xml = simplexml_load_string($data);
        $xml_data = array();
        $xml_data['file_reference'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->ReferencedImage_M->file_reference;
        $xml_data['recorded_pid'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->Patient_M->patient_id;
        
        // Cardiff check! See if the extraneous char is at the end of the PID:
        $pid = $xml_data['recorded_pid'];
        if (strlen($pid) > 7) {
          // X123456Z - we want to remove the 'Z':
          if (ctype_alpha($pid[7])) {
            // it's a character - remove it:
            $xml_data['recorded_pid'] = substr($pid, 0, 7);
          }
        }
        
        $xml_data['family_name'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->Patient_M->patients_name->family_name;
        $xml_data['given_name'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->Patient_M->patients_name->given_name;
        $xml_data['middle_name'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->Patient_M->patients_name->middle_name;
        $xml_data['birth_date'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->Patient_M->patients_birth_date;
        $xml_data['gender'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->Patient_M->patients_sex;
        $xml_data['study_date'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->GeneralStudy_M->study_date;
        $xml_data['study_time'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->GeneralStudy_M->study_time;
        $xml_data['eye'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->GeneralSeries_M->laterality;
        $xml_data['test_strategy'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->CZM_HFA_Series_M->test_strategy;
        $xml_data['test_name'] = (string) $xml->DataSet->CZM_HFA_EMR_IOD->CZM_HFA_Series_M->test_name;
        return $xml_data;
    }

    /**
     * 
     * @param type $name
     * @param type $dir
     * @return \FsFile
     */
    private function addFile($name, $dir) {
        $file = new FsFile;
        $file->name = $name;
        $file->dir_id = $dir->id;
        $stat = stat($dir->path . '/' . $name);
        $file->modified = $stat['mtime'];
        $file->created_date = date('Y-m-d H:i:s');
        $file->dir = $dir;
        $file->length = filesize($dir->path . '/' . $name);
        $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
        $file->created_user_id = User::model()->find('username=\'' . $username . '\'')->id;
        $file->save();
        return $file;
    }

    /**
     * 
     * @param type $dir
     * @return \FsDirectory
     */
    private function addFsDirectory($dir) {
        $fsdir = FsDirectory::model()->find('path=\'' . $dir . '\'');
        if (!$fsdir) {
            $stat = stat($dir);
            $fsdir = new FsDirectory;
            $fsdir->path = $dir;
            $fsdir->modified = $stat['mtime'];
            $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
            $fsdir->created_user_id = User::model()->find('username=\'' . $username . '\'')->id;
            $fsdir->created_date = date('Y-m-d H:i:s');
            $fsdir->save();
        }
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return $fsdir;
    }

    /**
     * 
     * @param type $type
     * @param type $from
     * @param type $to
     */
//  private function auditFile($operation, $type, $from, $to) {
//      $fileAudit = new FsFileAudit();
//      $fileAudit->operation = $operation;
//      $fileAudit->type = $type;
//      $fileAudit->src_parent = $from->dir_id;
//      $fileAudit->src_child = $from->id;
//      if ($to) {
//        $fileAudit->dest_parent = $to->dir_id;
//        $fileAudit->dest_child = $to->id;
//      }
//      $fileAudit->save();
//  }

    /**
     * 
     * @param type $operation
     * @param type $type
     * @param type $src_parent_id
     * @param type $src_child_id
     * @param type $dest_parent_id
     * @param type $dest_child_id
     */
//  private function createAuditTrail($operation, $type, $src_parent,
//          $src_child, $dest_parent = null, $dest_child = null) {
//    $fileAudit = new FsFileAudit;
//    $fileAudit->operation = $operation;
//    $fileAudit->type = $type;
//    $fileAudit->srcParent = $src_parent->id;
//    $fileAudit->srcChild = $src_child->id;
//    if ($dest_parent) {
//      $fileAudit->destParent = $dest_parent->id;
//    }
//    if ($dest_child) {
//      $fileAudit->destChild = $dest_child->id;
//    }
//    $fileAudit->save();
//  }

    /**
     * Takes just an image, with supplied data that would normally be
     * extracted from the XML file. Used for demos or when the data
     * has already been extracted from the XML file.
     * 
     * If only source directory is set, images are imported 'is situ' - used
     * for example demonstrations.
     * 
     * When files are imported from an external source, specifying the destination
     * directory ensures the files are moved from source to destination.
     * 
     */
    function actionImportHumphreyImage() {
        $this->_checkAuth();
        if (!isset($_GET['patient_id'])) {
            $this->_sendResponse(500, 'Error: Parameter pid is missing');
        }
    }
    
    /**
     * Attempts to find a matching (opposite) test to the test file given in.
     * 
     * Opposite tests must be made on the same day, with the opposite eye.
     * 
     * @param type $pid the patient's non-null hospital number.
     * @param type $tif_file_id non-null ID detailing the TIF file ID related
     * to the test.
     * @param type $xml_id non-null ID for the XML test file's ID.
     * @param type $test_strategy
     * @return void is returned if any of the specified values do not exist
     * (like the patient not existing or the parameters being null).
     */
    public function createHumphreyImagePairEvent($pid, $tif_file_id, $xml_id, $test_strategy) {
	if (!$pid || !$tif_file_id || !$xml_id || !$test_strategy) {
		return;
	}
        $event_type = EventType::model()->find('class_name=\'OphInVisualfields\'');
        $criteria = new CdbCriteria;
        $criteria->addSearchCondition('hos_num', strtolower($pid), true, 'OR', 'like');
        $criteria->addSearchCondition('hos_num', strtoupper($pid), true, 'OR', 'like');
        
        $patient = Patient::model()->find($criteria);
	if (!$patient) {
		return;
	}
        $xml_image = FsScanHumphreyXml::model()->find('id=' . $xml_id);

        $createdDate = new DateTime($xml_image->study_date);
        $createdTime = new DateTime($xml_image->study_date . ' ' . $xml_image->study_time);
        //          $x = $createdTime->sub(new DateInterval('PT1H2M'))->format('H:i:s');
        $interval = Yii::app()->params['esb_rest_api_humphrey_event_bond_time'];
        if ($interval) {
            $preTime = $createdTime->sub(new DateInterval($interval));
        }
        // search for images of the other eye:
        $eye = 'L';
        if ($xml_image->eye == 'L') {
            $eye = 'R';
        }
        $criteria = '(pid=\'' . strtolower($patient->hos_num)
                . '\' or pid=\'' . strtoupper($patient->hos_num)
                . '\') and associated=0 and eye=\'' . $eye . '\'';
        if ($preTime) {
            $criteria = $criteria
                    . ' and study_date=\'' . $createdDate->format('Y-m-d')
                    . '\' and study_time>=\'' . $preTime->format('H:i:s') . '\'';
        }
        // so these are the images that are for the other eye that are not yet
        // associated:
        $images = FsScanHumphreyXml::model()->findAll($criteria);

        if ($patient && $event_type) {
            // are we in legacy mode or normal import mode?
            if ($this->isLegacyMode('humphreys') && count($images) > 0) {
                $episode = null;
                if (!$patient->legacyepisodes || count($patient->legacyepisodes) == 0) {
                    $ep = new Episode;
                    $ep->legacy = 1;
                    $ep->patient_id = $patient->id;
                    $ep->save();
                    $episode = $ep;
                } else {
                    $episode = $patient->legacyepisodes[0];
                }
                $this->createLegacyEvent($episode, $tif_file_id, $images[0], $event_type, $test_strategy, $xml_image, $xml_id);
                $image1 = FsScanHumphreyXml::model()->find('id=' . $xml_id);
                $image1->associated = 1;
                $image1->save();
                $image2 = FsScanHumphreyXml::model()->find('id=' . $images[0]->id);
                $image2->associated = 1;
                $image2->save();
            } else {
                $bindingImage = null;
                $specialities = Yii::app()->params['esb_rest_api_image_specialities']['humphreys'];
                foreach ($specialities as $speciality) {
                    $condition = '';
                    if (count($specialities) > 0) {
                        $condition = $condition . ' and (';
                    }
                    $sp = Subspecialty::model()->find('name=\'' . $speciality . '\'');
                    if ($sp) {
                        $x = ServiceSubspecialtyAssignment::model()->find('subspecialty_id=' . $sp->id);
                        $y = Firm::model()->findAll('service_subspecialty_assignment_id=' . $x->id);
                        $index = 0;
                        foreach ($y as $firm) {
                            if ($index > 0 && $index < count($specialities)) {
                                $condition = $condition . ' or ';
                            }
                            $condition = $condition . ' firm_id=' . $firm->id;
                            $index++;
                        }
                    }
                    if (count($specialities) > 0) {
                        $condition = $condition . ')';
                    }
                    $cdbcriteria = new CDbCriteria;
                    $cdbcriteria->condition = 'patient_id=' . $patient->id . $condition;
                    $episodes = Episode::model()->findAll($cdbcriteria);
                    if (count($images) > 0) {
                        $bindingImage = $images[0];
                        $this->createEvent($tif_file_id, $images[0], $episodes, $event_type, $test_strategy, $xml_image, $xml_id);
                    }
                }
                if ($bindingImage) {
                    $image1 = FsScanHumphreyXml::model()->find('id=' . $xml_id);
                    $image1->associated = 1;
                    $image1->save();
                    $image2 = FsScanHumphreyXml::model()->find('id=' . $bindingImage->id);
                    $image2->associated = 1;
                    $image2->save();
                }
            }
        }
    }

    /**
     * 
     */
    function actionCreateHumphreyImagePairEvent() {
        $this->_checkAuth();
        if (!isset($_GET['patient_id'])) {
            $this->_sendResponse(500, 'Error: Parameter pid is missing', "createHumphreyImagePairEvent", "scan");
        }
        if (!isset($_GET['tif_file_id'])) {
            $this->_sendResponse(500, 'Error: Parameter tif_file_id is missing', "createHumphreyImagePairEvent", "scan");
        }
        if (!isset($_GET['xml_id'])) {
            $this->_sendResponse(500, 'Error: Parameter xml_id is missing', "createHumphreyImagePairEvent", "scan");
        }
        if (!isset($_GET['test_strategy'])) {
            $this->_sendResponse(500, 'Error: Parameter test_Strategy is missing', "createHumphreyImagePairEvent", "scan");
        }
        $this->createHumphreyImagePairEvent($_GET['patient_id'], $_GET['tif_file_id'], $_GET['xml_id'], $_GET['test_strategy']);
    }

    /**
     * Determines if the specified type is in legacy mode or not.
     * Note that if the specified type cannot be found, the default
     * type will be used. This enables the configuration to run off one value
     * to either be legacy mode or not.
     * 
     * If type and default is NOT defined, legacy mode is always false.
     * 
     * @return boolean true if legacy mode is supported for the specified type;
     * false otherwise.
     */
    private function isLegacyMode($type = 'default') {
        if (!Yii::app()->params['esb_rest_api_legacy_mode'][$type]) {
            $type = 'default';
        }
        return Yii::app()->params['esb_rest_api_legacy_mode'][$type]
                && Yii::app()->params['esb_rest_api_legacy_mode'][$type] == true;
    }

    /**
     * @param type $patient_id
     * @param type $tif_file_id
     * @param type $image
     * @param type $event_type
     * @param type $test_strategy
     * @param type $xml_image
     * @param type $xml_id
     */
    private function createLegacyEvent($episode, $tif_file_id, $image, $event_type, $test_strategy, $xml_image, $xml_id) {
        $this->bindEpisode($episode, $event_type, $tif_file_id, $image, $xml_image, $test_strategy);
    }

    /**
     * 
     * @param type $tif_file_id
     * @param type $image
     * @param type $episodes
     * @param type $event_type
     * @param type $test_strategy
     * @param type $xml_image
     * @param type $xml_id
     */
    private function createEvent($tif_file_id, $image, $episodes, $event_type, $test_strategy, $xml_image, $xml_id) {
        foreach ($episodes as $episode) {
            $this->bindEpisode($episode, $event_type, $tif_file_id, $image, $xml_image, $test_strategy);
        }
    }
    
    /**
     * Binds the episode to a new event, and creates necessary OphInVisualfield
     * objects based on the given information from the TIF and XML files and the
     * test strategy. Both eyes are bound to the new event.
     * 
     * @param type $episode the episode to bind the new event to.
     * 
     * @param type $event_type the event type that the event will be associated
     * with.
     * 
     * @param type $tif_file_id the TIF file ID of the image to bind.
     * 
     * @param type $image
     * @param type $xml_image
     * @param type $test_strategy
     */
    private function bindEpisode($episode, $event_type, $tif_file_id, $image, $xml_image, $test_strategy) {
        $tifCriteria = new CDbCriteria;
        $tifCriteria->addCondition('file_id=\'' . $image->tif_file_id . '\'');
        $previous_tif = FsScanHumphreyImage::model()->find($tifCriteria);
        // the current tif that is being tested for:
        $tifCriteria = new CDbCriteria;
        $tifCriteria->addCondition('file_id=\'' . $tif_file_id . '\'');
        $tif_image = FsScanHumphreyImage::model()->find($tifCriteria);
        if ($image->eye == 'R') {
            $tmp = $previous_tif->file_id;
            $previous_tif->file_id = $tif_image->file_id;
            $tif_image->file_id = $tmp;
        }

        $testType = OphInVisualfields_Testtype::model()->find('name=\'Humphreys\'');
        $testStrategy = OphInVisualfields_Strategy::model()->find('name=\'' . $test_strategy . '\'');

        // is this a logged in user or a HTTP request that is requesting this?
        $uid = (Yii::app()->session['user'] ? Yii::app()->session['user']->id : null);
        if ($uid == null) {
          // then it must be HTTP:
          $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
          $uid = User::model()->find('username=\'' . $username . '\'')->id;
    
        }
        $event = new Event;
        $event->created_user_id = $uid;
        $event->episode_id = $episode->id;
        $event->event_type_id = $event_type->id;
        $event->created_date = date($xml_image->study_date
                . ' ' . $xml_image->study_time);
        $event->last_modified_date = date($xml_image->study_date
                . ' ' . $xml_image->study_time);
        $event->datetime = date($xml_image->study_date
                . ' ' . $xml_image->study_time);
        $event->save($allow_overriding = true);
//        $event->created_date = date($xml_image->study_date
//                . ' ' . $xml_image->study_time);
//        $event->last_modified_date = date($xml_image->study_date
//                . ' ' . $xml_image->study_time);
//        $event->datetime = date($xml_image->study_date
//                . ' ' . $xml_image->study_time);
//        $event->save($allow_overriding = true);

//        $event->created_user_id = (Yii::app()->session['user'] ? Yii::app()->session['user']->id : null);
//        $event->created_date = date($xml_image->study_date
//                . ' ' . $xml_image->study_time);
//        $event->datetime = date($xml_image->study_date
//                . ' ' . $xml_image->study_time);
//        $event->last_modified_date = date($xml_image->study_date
//                . ' ' . $xml_image->study_time);
//        $event->save($allow_overriding = true);

        $objTestType = new Element_OphInVisualfields_Testtype;
        $objTestType->event_id = $event->id;
        $objTestType->test_type_id = $testType->id;
        $objTestType->save();
        $objDetails = new Element_OphInVisualfields_Details;
        $objDetails->event_id = $event->id;
        $objDetails->strategy_id = $testStrategy->id;
        $objDetails->save();
        $objImage = new Element_OphInVisualfields_Image;
        $objImage->event_id = $event->id;
        $objImage->left_image = $previous_tif->file_id;
        $objImage->right_image = $tif_image->file_id;
        $objImage->save();
    }

//  public function actionDelete() {
//    $this->_checkAuth();
//    switch ($_GET['model']) {
//      // Load the respective model
//      case 'asset':
//        $model = Asset::model()->findByPk($_GET['id']);
//        break;
//      default:
//        $this->_sendResponse(501, sprintf('Error: Mode delete is not implemented for model \'%s\'', $_GET['model']));
//        Yii::app()->end();
//    }
//    // Was a model found? If not, raise an error
//    if ($model === null)
//      $this->_sendResponse(400, sprintf("Error: Didn't find any model \'%s\' with ID \'%s\'.", $_GET['model'], $_GET['id']));
//
//    // Delete the model
//    $num = $model->delete();
//    if ($num > 0)
//      $this->_sendResponse(200, $num);    //this is the only way to work with backbone
//    else
//      $this->_sendResponse(500, sprintf("Error: Couldn't delete model \'%s\' with ID \'%s\'.", $_GET['model'], $_GET['id']));
//  }
}

?>
