<?php

/**
 * Taken and adapted from:
 *  http://www.yiiframework.com/wiki/175/how-to-create-a-rest-api/
 */
class RestfulController extends Controller {
  
  /**
   * Key which has to be in HTTP USERNAME and PASSWORD headers 
   */
  Const APPLICATION_ID = 'ASCCPE';

  /**
   * @return array action filters
   */
  public function filters() {
    return array();
  }

  /**
   * Perform a get with ID (view), get without ID (list), create (post) or
   * update (put) with ID.
   */
  public function actionRest() { 
    // until this has been fully adapted and modifed, localhost access only
    // TODO remove when API is more mature 
    if (Yii::app()->request->getBaseUrl(true) != 'http://localhost') {
      return;
    }
    $this->_checkAuth();
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    switch ($requestMethod) {
      case 'GET':
        // view one item by ID:
        if (isset($_GET['id'])) {
          $this->view($_GET['id'], $_GET['model']);
        } else {
          $this->viewAll($_GET['model']);
        }
        break;
      case 'POST':
          $this->create($_GET['model']);
        break;
      case 'PUT':
          $this->update($_GET['id'], $_GET['model']);
        break;
      // TODO delete is a special case - no deletes yet
//      case 'DELETE':
//        
//        break;
      default :
        $this->_sendResponse(501, sprintf(
                        'Error: Invalid request method: <b>%s</b>', $requestMethod));

        break;
    }
  }
  
  /**
   * 
   * @return type
   */
  public function actionSearch() {
    $this->_checkAuth();
    
    $this->search($_GET['model']);
  }

  /**
   * View all models of the specified class.
   * 
   * @param type $model
   */
  private function viewAll($model) {
    // Get the respective model instance
    try {
      $class_name = new $model;
      $models = $class_name::model()->findAll();
    } catch (Exception $e) {
      // Model not implemented error
      $this->_sendResponse(501, sprintf(
                      'Error: Mode <b>list</b> is not implemented for model <b>%s</b>', $model));
      Yii::app()->end();
    }
    // Did we get some results?
    if (empty($models)) {
      // No
      $this->_sendResponse(200, sprintf('No items where found for model <b>%s</b>', $model));
    } else {
      // Prepare response
      $rows = array();
      foreach ($models as $model)
        $rows[] = $model->attributes;
      // Send the response
      $this->_sendResponse(200, CJSON::encode($rows));
    }
  }
  /**
   * View all models of the specified class.
   * 
   * @param type $model
   */
  private function search($model) {
    // Get the respective model instance
    $json = file_get_contents('php://input');
    $put_vars = CJSON::decode($json, true);  
    try {
      $search = new $model;
      $criteria = new CDbCriteria;
      foreach ($put_vars as $var => $value) {
        // Does the model have this attribute? If not raise an error
        if ($search->hasAttribute($var)) {
          $criteria->compare($var,$value); 
        }
        else
          $this->_sendResponse(500, sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b> - json was %s', $var, $model, $json));
      }
      $models = $search::model()->findAll($criteria);
    } catch (Exception $e) {
      // Model not implemented error
      $this->_sendResponse(501, sprintf(
                      'Error: Mode <b>search</b> is not implemented for model <b>%s</b>', $model));
      Yii::app()->end();
    }
    // Did we get some results?
    if (empty($models)) {
      // No
      $this->_sendResponse(200, sprintf('No items where found for model <b>%s</b>', $model));
    } else {
      // Prepare response
      $rows = array();
      foreach ($models as $model)
        $rows[] = $model->attributes;
      // Send the response
      $this->_sendResponse(200, CJSON::encode($rows));
    }
  }

  /**
   * View the specified model.
   * 
   * @param type $id
   * @param type $model
   */
  private function view($id, $model) {
    // Check if id was submitted via GET
    if (!isset($id)) {
      $this->_sendResponse(500, 'Error: Parameter <b>id</b> is missing');
    }
    try {
      $class_name = new $model;
      // TODO check if this class can be viewed:
      if (true) {
        $model = $class_name::model()->findByPk($id);
      } else {
        $this->_sendResponse(501, sprintf(
                        'Mode <b>view</b> is not implemented for model <b>%s</b>', $model));
        Yii::app()->end();
      }
    } catch (Exception $e) {

      $this->_sendResponse(501, sprintf(
                      'Error loading model <b>%s</b>', $model));
      Yii::app()->end();
    }
    // Did we find the requested model? If not, raise an error
    if (is_null($model))
      $this->_sendResponse(404, 'No Item found with id ' . $id);
    else
      $this->_sendResponse(200, CJSON::encode($model));
  }

  /**
   * Create a new instance of the specified model. Query parameters are
   * specified using JSON.
   * 
   * @param model $model
   */
  private function create($model) {
    $json = file_get_contents('php://input'); //$GLOBALS['HTTP_RAW_POST_DATA'] is not preferred: http://www.php.net/manual/en/ini.core.php#ini.always-populate-raw-post-data
    $put_vars = CJSON::decode($json, true);  //true means use associative array
    // Try to assign POST values to attributes

    try {
      // check is supported
      $model = new $model;
    } catch (Exception $e) {
      $this->_sendResponse(501, sprintf(
                      'Error loading model <b>%s</b>', $model));
      Yii::app()->end();
    }
    foreach ($put_vars as $var => $value) {
      // Does the model have this attribute? If not raise an error
      if ($model->hasAttribute($var))
        $model->$var = $value;
      else
        $this->_sendResponse(500, sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var, $model));
    }
    // Try to save the model
    if ($model->save())
      $this->_sendResponse(200, CJSON::encode($model));
    else {
      // Errors occurred
      $msg = "<h1>Error</h1>";
      $msg .= sprintf("Couldn't create model <b>%s</b>", $model);
      $msg .= "<ul>";
      foreach ($model->errors as $attribute => $attr_errors) {
        $msg .= "<li>Attribute: $attribute</li>";
        $msg .= "<ul>";
        foreach ($attr_errors as $attr_error)
          $msg .= "<li>$attr_error</li>";
        $msg .= "</ul>";
      }
      $msg .= "</ul>";
      $this->_sendResponse(500, $msg);
    }
  }

  /**
   * Update the specified model, query parameters specified using JSON.
   * @param type $id
   * @param type $model
   */
  private function update($id, $model) {
    // Parse the PUT parameters. This didn't work: parse_str(file_get_contents('php://input'), $put_vars);
//    parse_str(file_get_contents('php://input'), $put_vars);
    $json = file_get_contents('php://input'); //$GLOBALS['HTTP_RAW_POST_DATA'] is not preferred: http://www.php.net/manual/en/ini.core.php#ini.always-populate-raw-post-data
    $put_vars = CJSON::decode($json, true);  //true means use associative array

    try {
      $class_name = new $model;
      if (true) {
        $model = $class_name::model()->findByPk($id);
      } else {
        $this->_sendResponse(501, sprintf(
                        'Mode <b>view</b> is not implemented for model <b>%s</b>', $model));
        Yii::app()->end();
      }
    } catch (Exception $e) {

      $this->_sendResponse(501, sprintf(
                      'Error loading model <b>%s</b>', $model));
      Yii::app()->end();
    }
    // Did we find the requested model? If not, raise an error
    if ($model === null)
      $this->_sendResponse(400, sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.", $model, $id));
    unset($put_vars['id']);
    // Try to assign PUT parameters to attributes
    foreach ($put_vars as $var => $value) {
      // Does model have this attribute? If not, raise an error
      if ($model->hasAttribute($var))
        $model->$var = $value;
      else {
        $this->_sendResponse(500, sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var, $model));
      }
    }
    // Try to save the model
    if ($model->save())
      $this->_sendResponse(200, CJSON::encode($model));
    else
    // prepare the error $msg
    // see actionCreate
    // ...
      $this->_sendResponse(500, $msg);
  }

//  public function actionDelete() {
//    $this->_checkAuth();
//    switch ($_GET['model']) {
//      // Load the respective model
//      case 'asset':
//        $model = Asset::model()->findByPk($_GET['id']);
//        break;
//      default:
//        $this->_sendResponse(501, sprintf('Error: Mode <b>delete</b> is not implemented for model <b>%s</b>', $_GET['model']));
//        Yii::app()->end();
//    }
//    // Was a model found? If not, raise an error
//    if ($model === null)
//      $this->_sendResponse(400, sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.", $_GET['model'], $_GET['id']));
//
//    // Delete the model
//    $num = $model->delete();
//    if ($num > 0)
//      $this->_sendResponse(200, $num);    //this is the only way to work with backbone
//    else
//      $this->_sendResponse(500, sprintf("Error: Couldn't delete model <b>%s</b> with ID <b>%s</b>.", $_GET['model'], $_GET['id']));
//  }

  /**
   * 
   * @param type $status
   * @param string $body
   * @param type $content_type
   */
  private function _sendResponse($status = 200, $body = '', $content_type = 'text/html') {
    // set the status
    $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
    header($status_header);
    // and the content type
    header('Content-type: ' . $content_type);

    // pages with body are easy
    if ($body != '') {
      // send the body
      echo $body;
    }
    // we need to create the body if none is passed
    else {
      // create some body messages
      $message = '';

      // this is purely optional, but makes the pages a little nicer to read
      // for your users.  Since you won't likely send a lot of different status codes,
      // this also shouldn't be too ponderous to maintain
      switch ($status) {
        case 401:
          $message = 'You must be authorized to view this page.';
          break;
        case 404:
          $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
          break;
        case 500:
          $message = 'The server encountered an error processing your request.';
          break;
        case 501:
          $message = 'The requested method is not implemented.';
          break;
      }

      // servers don't always have a signature turned on 
      // (this is an apache directive "ServerSignature On")
      $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

      // this should be templated in a real-world solution
      $body = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
</head>
<body>
    <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
    <p>' . $message . '</p>
    <hr />
    <address>' . $signature . '</address>
</body>
</html>';

      echo $body;
    }
    Yii::app()->end();
  }

  /**
   * 
   * @param type $status
   * @return type
   */
  private function _getStatusCodeMessage($status) {
    // these could be stored in a .ini file and loaded
    // via parse_ini_file()... however, this will suffice
    // for an example
    $codes = Array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
    );
    return (isset($codes[$status])) ? $codes[$status] : '';
  }

  /**
   * 
   */
  private function _checkAuth() {
    // Check if we have the USERNAME and PASSWORD HTTP headers set? 
    if (!(isset($_SERVER['HTTP_X_' . self::APPLICATION_ID . '_USERNAME']) and isset($_SERVER['HTTP_X_' . self::APPLICATION_ID . '_PASSWORD']))) {
      // Error: Unauthorized 
      $this->_sendResponse(401);
    }
    $username = $_SERVER['HTTP_X_' . self::APPLICATION_ID . '_USERNAME'];
    $password = $_SERVER['HTTP_X_' . self::APPLICATION_ID . '_PASSWORD'];
    // Find the user 
    $user = User::model()->find('LOWER(username)=?', array(strtolower($username)));
    if ($user === null) {
      // Error: Unauthorized 
      $this->_sendResponse(401, 'Error: User Name is invalid');
    } else if (!$user->validatePassword($password)) {
      // Error: Unauthorized 
      $this->_sendResponse(401, 'Error: User Password is invalid');
    }
  }

}

?>
