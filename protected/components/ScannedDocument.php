<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Provides a common entry point for retrieving images.
 */
class ScannedDocument {

  /**
   * This method uses reflection to load the module specified by the image
   * type.
   * 
   * @param string $imageType the type of image to use; this is important
   * and is used in the reflective part to load a module named
   * OphScImage[imageType].
   * 
   * @param int $assetId the asset to obtain.
   * 
   * @param array params an (optional) extra set of parameters for the specified
   * module.
   * 
   * @return null
   */
  public static function getScannedDocument($imageType, $assetId, $params = null) {
    $class_name = 'Element_OphScImage' . strtolower($imageType) . '_Document';
    $module_name = 'application.modules.OphScImage' . strtolower($imageType)
            . '.models.' . $class_name;
    Yii::import($module_name);
    $doc = new $class_name;
    return $doc->getScannedDocument($assetId, $params);
  }

  /**
   * Get all scanned documents of the given type for the patient.
   * 
   * @param string $imageType the image type, based on having a module named
   * OphScImage[imageType].
   * 
   * @param string $pid the hospital number of the patient.
   * 
   * @param array $params optional parameters for the actual module search.
   */
  public function getScannedDocuments($imageType, $pid, $params) {
    $class_name = 'Element_OphScImage' . strtolower($imageType) . '_Document';
    $module_name = 'application.modules.OphScImage' . strtolower($imageType)
            . '.models.' . $class_name;

    Yii::import($module_name, true);
    $docs = new $class_name;
    return $docs->getScannedDocuments($pid, $params);
  }

  /**
   * Takes the test type removes spaces and turns it to lower case, then
   * determines if the module OphScImage[testType] exists.
   * 
   * @param string $testType describes the image format; like Dicon, Goldman
   * perimetry, humphrey etc.
   * 
   * @return boolean true if the module OphScImage[testType] exists; false
   * otherwise.
   */
  public function isSupported($testType) {
    $class_name = 'Element_OphScImage' . strtolower($testType) . '_Document';
    $module_name = 'application.modules.OphScImage' . strtolower($testType)
            . '.models.' . $class_name;
    try {
      Yii::import($module_name, true);
    } catch(Exception $e) {
      
    }
    return class_exists($class_name, false);
  }

}

?>
