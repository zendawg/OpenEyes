<?php

/**
 * Taken and adapted from:
 *  http://www.yiiframework.com/wiki/175/how-to-create-a-rest-api/
 */
class EsbRestApiController extends RestfulController {
  
  /**
   * Key which has to be in HTTP USERNAME and PASSWORD headers 
   */
  Const APPLICATION_ID = 'ASCCPE';

  function getViewableModels() {
    return array_merge($this->getCreatableModels(), array('Patient',
        'OphInVisualfields_Testtype', 'OphInVisualfields_Strategy',
        'EventType'));
  }
  function getCreatableModels() {
    return $this->getUpdatableModels();
  }
  function getUpdatableModels() {
    return array('Asset', 'FsFile', 'FsDirectory', 'FsScanHumphreyImage',
        'FsScanHumphreyXml', 'Element_OphInVisualfields_Testtype',
        'Element_OphInVisualfields_Details', 'Element_OphInVisualfields_Image',
        'ScannedDocumentUid',
        'Episode', 'Event');
  }
  function getSearchableModels() {
    return $this->getViewableModels();
  }
  
  public function getApplicationId() {
    return 'ASCCPE';
  }
  
  function actionRest() {
    return parent::actionRest();
  }
  
  /**
   * 
   */
  function actionCreateHumphreyImagePairEvent() {
    
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
    
    $tif_image = FsScanHumphreyImage::model()->find('file_id=' . $tif_file_id);
    $event_type = EventType::model()->find('class_name=\'OphInVisualfields\'');
    $patient = Patient::model()->find('hos_num=' . $pid);
    if ($patient && $event_type && $tif_image) {
      
      $episode = Episode::model()->find('patient_id=' . $patient->id);
      if (count($episode) == 1) {
        
          $timestamp = time() - (60 * 60 * 1000);
          $date = date('o-m-d H:i:s', $timestamp);
          $xml_image = FsScanHumphreyXml::model()->find('id=' . $xml_id);

          $eye = 'L';
          if ($xml_image->eye == 'L') {
            $eye = 'R';
          }
          $images = FsScanHumphreyXml::model()->findAll(
                  'pid=\'' . $patient->hos_num . '\' and created_date>=\'' . $date . '\' and associated=0 and eye=\'' . $eye . '\''
                  );
          if (count($images) > 0) {
            $previous_tif = FsScanHumphreyImage::model()->find('file_id=' . $images[0]->tif_file_id);
            
            if ($images[0]->eye == 'R') {
              $tmp = $previous_tif->asset_id;
              $previous_tif->asset_id = $tif_image->asset_id;
              $tif_image->asset_id = $tmp;
            }
            
            $testType = OphInVisualfields_Testtype::model()->find('name=\'Humphreys\'');
            $testStrategy = OphInVisualfields_Strategy::model()->find('name=\'' . $test_strategy . '\'');

            $event = new Event;
            $event->episode_id = $episode->id;
            $event->event_type_id = $event_type->id;
            $event->created_user_id=1;
            $event->created_date=$date;
            $event->datetime=$date;
            $event->save();
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
            $objImage->left_image = $previous_tif->asset_id;
            $objImage->right_image = $tif_image->asset_id;
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
