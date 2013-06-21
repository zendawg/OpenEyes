<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

$this->breadcrumbs=array(
	'Phrase By Firm' => array('/admin/phraseByFirm/index'), 
	$sectionName => array('firmIndex', 'section_id'=>$sectionId),
	$firmName
);
$this->menu=array(
	array('label'=>'Create a phrase for this firm', 'url'=> array('create', 'section_id'=>$sectionId, 'firm_id'=>$firmId)),
	array('label'=>'Manage phrases for this firm', 'url'=>array('admin', 'section_id'=>$sectionId)),
);
?>

<h1>Phrase By Firm</h1>
<h2>Phrases for the section: <?php echo $sectionName; ?> and the firm: <?php echo $firmName; ?></h2>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view'
)); ?>