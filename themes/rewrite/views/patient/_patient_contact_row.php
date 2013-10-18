<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

$contact = $pca->location ? $pca->location->contact : $pca->contact;
?>
<tr data-attr-pca-id="<?php echo $pca->id?>"<?php if ($pca->location) {?> data-attr-location-id="<?php echo $pca->location_id?>"<?php } if ($pca->contact) {?> data-attr-contact-id="<?php echo $pca->contact_id?>"<?php }?>>
	<td>
		<?php echo $contact->fullName?> <br />
		<?php echo $contact->qualifications?>
	</td>
	<td>
		<?php echo $pca->locationText?>
	</td>
	<td>
		<?php echo $contact->label->name?>
	</td>
	<td>
		<?php if (BaseController::checkUserLevel(4)) {?>
			<?php if ($pca->location) {?>
				<a class="editContact" rel="<?php echo $pca->id?>" href="#">
					Edit
				</a>
				<br/>
			<?php }?>
			<a class="removeContact small" rel="<?php echo $pca->id?>" href="#">
				Remove
			</a>
		<?php }?>
	</td>
</tr>