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
   * 
   */
  function actionCreateHumphreyImagePairEvent() {
    $this->_checkAuth();
    if (!isset($_GET['patient_id'])) {
      $this->_sendResponse(500, 'Error: Parameter pid is missing');
    }
    if (!isset($_GET['tif_file_id'])) {
      $this->_sendResponse(500, 'Error: Parameter tif_file_id is missing');
    }
    if (!isset($_GET['xml_id'])) {
      $this->_sendResponse(500, 'Error: Parameter xml_id is missing');
    }
    if (!isset($_GET['test_strategy'])) {
      $this->_sendResponse(500, 'Error: Parameter test_Strategy is missing');
    }
    $pid = $_GET['patient_id'];
    $tif_file_id = $_GET['tif_file_id'];
    $xml_id = $_GET['xml_id'];
    $test_strategy = $_GET['test_strategy'];

    $event_type = EventType::model()->find('class_name=\'OphInVisualfields\'');
    $patient = Patient::model()->find('hos_num=\'' . $pid . '\'');

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
    $criteria = 'pid=\'' . $patient->hos_num
            . '\' and associated=0 and eye=\'' . $eye . '\'';
    if ($preTime) {
      $criteria = $criteria
              . ' and study_date>=\'' . $createdDate->format('Y-m-d')
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
   * 
   * @param type $episode
   * @param type $xml_image
   * @param type $username
   * @param type $testType
   * @param type $testStrategy
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

    $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
    $event = new Event;
    $event->created_user_id = User::model()->find('username=\'' . $username . '\'')->id;
    $event->episode_id = $episode->id;
    $event->event_type_id = $event_type->id;
    $event->created_date = date($xml_image->study_date
            . ' ' . $xml_image->study_time);
    $event->last_modified_date = date($xml_image->study_date
            . ' ' . $xml_image->study_time);
    $event->datetime = date($xml_image->study_date
            . ' ' . $xml_image->study_time);
    $event->save($allow_overriding = true);

    $event->created_user_id = User::model()->find('username=\'' . $username . '\'')->id;
    $event->created_date = date($xml_image->study_date
            . ' ' . $xml_image->study_time);
    $event->datetime = date($xml_image->study_date
            . ' ' . $xml_image->study_time);
    $event->last_modified_date = date($xml_image->study_date
            . ' ' . $xml_image->study_time);
    $event->save($allow_overriding = true);

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
