<?php

/**
 * Taken and adapted from:
 *  http://www.yiiframework.com/wiki/175/how-to-create-a-rest-api/
 */
class EsbRestApiController extends RestfulController {
  
  function getViewableModels() {
    return array_merge($this->getCreatableModels(), 
            Yii::app()->params['esb_rest_api_viewable']);
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
    $patient = Patient::model()->find('hos_num=' . $pid);
    if ($patient && $event_type) {
      
      $episodes = Episode::model()->findAll('patient_id=' . $patient->id);
      if (count($episodes) == 1) {
        
          $xml_image = FsScanHumphreyXml::model()->find('id=' . $xml_id);

          $createdDate = new DateTime($xml_image->study_date);
          $createdTime = new DateTime($xml_image->study_date . ' ' . $xml_image->study_time);
          $x = $createdTime->sub(new DateInterval('PT1H2M'))->format('H:i:s');
          $preTime = $createdTime->sub(new DateInterval('PT1H2M'));
          $t = $preTime->format('H:i:s');
          $y = $createdDate->format('Y-m-d');
          $eye = 'L';
          if ($xml_image->eye == 'L') {
            $eye = 'R';
          }
          $images = FsScanHumphreyXml::model()->findAll(
                  'pid=\'' . $patient->hos_num
                  . '\' and study_date>=\'' . $createdDate->format('Y-m-d')
                  . '\' and study_time>=\'' . $preTime->format('H:i:s')
                  . '\' and associated=0 and eye=\'' . $eye . '\''
                  );
          if (count($images) > 0) {
            $tifCriteria = new CDbCriteria;
            $tifCriteria->addCondition('file_id=\'' . $images[0]->tif_file_id . '\'');
            $previous_tif = FsScanHumphreyImage::model()->find($tifCriteria);
            // the current tif that is being tested for:
            $tifCriteria = new CDbCriteria;
            $tifCriteria->addCondition('file_id=\'' . $tif_file_id . '\'');
            $tif_image = FsScanHumphreyImage::model()->find($tifCriteria);
            if ($images[0]->eye == 'R') {
              $tmp = $previous_tif->file_id;
              $previous_tif->file_id = $tif_image->file_id;
              $tif_image->file_id = $tmp;
            }
            
            $testType = OphInVisualfields_Testtype::model()->find('name=\'Humphreys\'');
            $testStrategy = OphInVisualfields_Strategy::model()->find('name=\'' . $test_strategy . '\'');

            $event = new Event;
            $event->episode_id = $episodes[0]->id;
            $event->event_type_id = $event_type->id;
            
            $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
            $event->created_user_id = User::model()->find('username=\'' . $username. '\'')->id;
            $event->created_date = date($xml_image->study_date
                    . ' ' . $xml_image->study_time);
            $event->last_modified_date = date($xml_image->study_date
                    . ' ' . $xml_image->study_time);
            $t = $event->created_date;
            $event->datetime= date($xml_image->study_date
                    . ' ' . $xml_image->study_time);
            $event->save($allow_overriding=true);
            
            $event->created_user_id = User::model()->find('username=\'' . $username. '\'')->id;
            $event->created_date = date($xml_image->study_date
                    . ' ' . $xml_image->study_time);
            $t = $event->created_date;
            $event->datetime= date($xml_image->study_date
                    . ' ' . $xml_image->study_time);
            $event->last_modified_date = date($xml_image->study_date
                    . ' ' . $xml_image->study_time);
            $event->save($allow_overriding=true);
            
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
            
            $image1 = FsScanHumphreyXml::model()->find('id=' . $xml_id);
            $image1->associated = 1;
            $image1->save();
            $image2 = FsScanHumphreyXml::model()->find('id=' . $images[0]->id);
            $image2->associated = 1;
            $image2->save();
          }
      }
    }
    
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
