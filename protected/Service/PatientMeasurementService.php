<?php

/**
 * (C) OpenEyes Foundation, 2014
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2014, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

namespace Service;

class PatientMeasurementService extends ModelService {

  static protected $operations = array(self::OP_READ, self::OP_UPDATE, self::OP_CREATE, self::OP_SEARCH);
  static protected $primary_model = 'PatientMeasurement';

  public function search(array &$params) {
	$this->setUsedParams($params, 'id');

	$model = $this->getSearchModel();
	if (isset($params['id']))
	  $model->id = $params['id'];

	$searchParams = array('pageSize' => null);

	return $this->getResourcesFromDataProvider($model->search($searchParams));
  }

  /**
   * 
   * @param type $res
   * @param type $measurement
   * @return type
   */
  public function resourceToModel($res, $measurement) {

	$measurement_type = \MeasurementType::model()->find("class_name=:class_name", array(":class_name" => $res->measurement_type));
	$measurement->measurement_type_id = $measurement_type->id;
	$measurement->patient_id = $res->patient_id;
	$saved = $measurement->save();
	return $measurement;
  }

  /**
   * 
   * @param type $measurement
   */
  public function modelToResource($measurement) {
	$res = parent::modelToResource($measurement);
	$res->patient_id = $measurement->patient_id;
	$measurement_type = \MeasurementType::model()->findByPk($measurement->id);
	$res->measurement_type = $measurement_type;
	return $res;
  }

}
