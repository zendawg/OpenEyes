<?php

/**
 * Taken and adapted from:
 *  http://www.yiiframework.com/wiki/175/how-to-create-a-rest-api/
 */
abstract class RestfulController extends Controller {

    /**
     * @return array action filters
     */
    public function filters() {
        return array();
    }

    /**
     * 
     */
    abstract function getViewableModels();

    /**
     * 
     */
    abstract function getCreatableModels();

    /**
     * 
     */
    abstract function getUpdatableModels();

    /**
     * 
     */
    abstract function getSearchableModels();

    /**
     * Key which has to be in HTTP USERNAME and PASSWORD headers 
     */
    abstract function getApplicationId();

    /**
     * Perform a get with ID (view), get without ID (list), create (post) or
     * update (put) with ID.
     */
    public function actionRest() {
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
                                'Error: Invalid request method: \'%s\'', $requestMethod));

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
        if (!in_array($model, $this->getViewableModels())) {
            $this->_sendResponse(500, 'Error: REST transation are not supported for ' . $model);
        }
        // Get the respective model instance
        try {
            $class_name = new $model;
            $models = $class_name::model()->findAll();
        } catch (Exception $e) {
            // Model not implemented error
            $this->_sendResponse(501, sprintf(
                            'Error: Mode list is not implemented for model \'%s\'', $model));
            Yii::app()->end();
        }
        // Did we get some results?
        if (empty($models)) {
            // No
            $this->_sendResponse(200, sprintf('No items where found for model \'%s\'', $model));
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
     * Search for the specified model and get a list of matches.
     * 
     * @param type $model
     */
    private function search($model) {
        if (!in_array($model, $this->getSearchableModels())) {
            $this->_sendResponse(500, 'Error: REST transation are not supported for ' . $model);
        }
        // Get the respective model instance
        $json = file_get_contents('php://input');
        $put_vars = CJSON::decode($json, true);
        try {
            $search = new $model;
            $criteria = new CDbCriteria;
            foreach ($put_vars as $var => $value) {
                // Does the model have this attribute? If not raise an error
                if ($search->hasAttribute($var)) {
                    $value = urldecode($value);
                    // default operator is equality:
                    $operator = '=';
                    if (strpos($value, '< ') > -1) {
                        $operator = '<';
                    } else if (strpos($value, '<= ') > -1) {
                        $operator = '<=';
                    } else if (strpos($value, '> ') > -1) {
                        $operator = '>=';
                    } else if (strpos($value, '>= ') > -1) {
                        $operator = '>=';
                    } else if (strpos($value, '!= ') > -1) {
                        $operator = '!=';
                    }
                    if ($operator != '=') {
                        $value = substr($value, strpos($value, ' ') + 1);
                    }
                    $criteria->addCondition($var . $operator . '\'' . $value . '\'');
                }
                else
                    $this->_sendResponse(500, sprintf('Parameter \'%s\' is not allowed for model \'%s\' - json was \'%s\'', $var, $model, $json));
            }
            $models = $search::model()->findAll($criteria);
        } catch (Exception $e) {
            // Model not implemented error
            $this->_sendResponse(501, sprintf(
                            'Error: Mode search is not implemented for model \'%s\'', $model));
            Yii::app()->end();
        }
        // Did we get some results?
        if (empty($models)) {
            // No
            $this->_sendResponse(400, sprintf('No items where found for model \'%s\'', $model));
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
        if (!in_array($model, $this->getViewableModels())) {
            $this->_sendResponse(500, 'Error: REST transation are not supported for ' . $model);
        }
        // Check if id was submitted via GET
        if (!isset($id)) {
            $this->_sendResponse(500, 'Error: Parameter id is missing');
        }
        try {
            $class_name = new $model;
            // TODO check if this class can be viewed:
            if (true) {
                $model = $class_name::model()->findByPk($id);
            } else {
                $this->_sendResponse(501, sprintf(
                                'Mode view is not implemented for model \'%s\'', $model));
                Yii::app()->end();
            }
        } catch (Exception $e) {

            $this->_sendResponse(501, sprintf(
                            'Error loading model \'%s\'', $model));
            Yii::app()->end();
        }
        $x = $model;
        $y = is_null($model);
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
        if (!in_array($model, $this->getCreatableModels())) {
            $this->_sendResponse(500, 'Error: REST transation are not supported for ' . $model);
        }
        $json = file_get_contents('php://input'); //$GLOBALS['HTTP_RAW_POST_DATA'] is not preferred: http://www.php.net/manual/en/ini.core.php#ini.always-populate-raw-post-data
        $put_vars = CJSON::decode($json, true);  //true means use associative array
        // Try to assign POST values to attributes

        try {
            // check is supported
            $obj = new $model;
        } catch (Exception $e) {
            $this->_sendResponse(501, sprintf(
                            'Error loading model \'%s\'', $model));
            Yii::app()->end();
        }
        foreach ($put_vars as $var => $value) {
            // Does the model have this attribute? If not raise an error
            if ($obj->hasAttribute($var))
                $obj->$var = $value;
            else
                $this->_sendResponse(500, sprintf('Parameter \'%s\' is not allowed for model \'%s\'', $var, $model));
        }
        // Try to save the model
        if ($obj->save())
            $this->_sendResponse(200, CJSON::encode($obj));
        else {
            // Errors occurred
            $msg = "<h1>Error</h1>";
            $msg .= sprintf("Couldn't create model \'%s\'", $model);
            $msg .= "<ul>";
            foreach ($obj->errors as $attribute => $attr_errors) {
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
        if (!in_array($model, $this->getUpdatableModels())) {
            $this->_sendResponse(500, 'Error: REST transation are not supported for ' . $model);
        }
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
                                'Mode view is not implemented for model \'%s\'', $model));
                Yii::app()->end();
            }
        } catch (Exception $e) {

            $this->_sendResponse(501, sprintf(
                            'Error loading model \'%s\'', $model));
            Yii::app()->end();
        }
        // Did we find the requested model? If not, raise an error
        if ($model === null)
            $this->_sendResponse(400, sprintf("Error: Didn't find any model \'%s\' with ID \'%s\'.", $model, $id));
        unset($put_vars['id']);
        // Try to assign PUT parameters to attributes
        foreach ($put_vars as $var => $value) {
            // Does model have this attribute? If not, raise an error
            if ($model->hasAttribute($var))
                $model->$var = $value;
            else {
                $this->_sendResponse(500, sprintf('Parameter \'%s\' is not allowed for model \'%s\'', $var, $model));
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

    /**
     * 
     * @param type $status
     * @param string $body
     * @param type $content_type
     */
    protected function _sendResponse($status = 200, $body = '',
            $content_type = 'text/html', $action = null, $target_type = null) {
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);

        // pages with body are easy
        if ($body != '') {
            if (!is_null($action) && !is_null($target_type)) {
                $this->audit($action, $target_type, $body);
            }
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

            if ($action && $target_type) {
                $this->audit($action, $target_type, $body);
            }
            echo $body;
        }
        Yii::app()->end();
    }

    /**
     * 
     * @param type $action
     * @param type $target_type
     * @param type $data
     */
    protected function audit($action, $target_type, $data) {
        $audit = new Audit;
        $audit->action = $action;
        $audit->target_type = $target_type;
        $audit->data = $data;
        $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
        $audit->user_id = User::model()->find('username=\'' . $username . '\'')->id;
        $audit->save();
    }

    /**
     * 
     * @param type $status
     * @return type
     */
    protected function _getStatusCodeMessage($status) {
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
    protected function _checkAuth() {
        if (Yii::app()->params['esb_rest_api_on'] != 'true') {
            $this->_sendResponse(500, 'Error: There is no REST API: ' . Yii::app()->params['esb_rest_api_on']);
        }
        // Check if we have the USERNAME and PASSWORD HTTP headers set? 
        if (!(isset($_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME']) and isset($_SERVER['HTTP_X_' . $this->getApplicationId() . '_PASSWORD']))) {
            // Error: Unauthorized 
            $this->_sendResponse(401);
        }
        $username = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_USERNAME'];
        $password = $_SERVER['HTTP_X_' . $this->getApplicationId() . '_PASSWORD'];

        if (!in_array($username, Yii::app()->params['esb_rest_api_users'])) {
            $this->_sendResponse(401, 'Error: Permissions.');
        }
        // Find the user 
        $user = User::model()->find('LOWER(username)=?', array(strtolower($username)));
        if ($user === null) {
            // Error: Unauthorized 
            $this->_sendResponse(401, 'Error: Invalid login parameters.');
        } else if (!$user->validatePassword($password)) {
            // Error: Unauthorized 
            $this->_sendResponse(401, 'Error: Invalid login parameters.');
        }
    }

}

?>
