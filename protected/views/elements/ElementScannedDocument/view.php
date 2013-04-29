
<h4 class="elementTypeName"><?php  echo $element->elementType->name ?></h4>

<table class="subtleWhite normalText">
	<tbody>
		<tr>
			<td width="30%"><?php echo CHtml::encode($element->getAttributeLabel('title'))?></td>
			<td><span class="big"><?php echo $element->title?></span></td>
		</tr>
		<tr>
			<td width="30%"><?php echo CHtml::encode($element->getAttributeLabel('description'))?></td>
			<td><span class="big"><?php echo $element->description?></span></td>
		</tr>
	</tbody>
</table>

<p>
	<a href="<?php echo Yii::app()->createUrl('/asset/download/'.$element->asset_id)?>">Download <?php echo $element->asset->name?></a>
</p>

<h4 class="elementTypeName">Preview</h4>
<img src="<?php echo Yii::app()->createUrl('/asset/preview/'.$element->asset_id)?>" />
