
<h4 class="elementTypeName"><?php echo $element->elementType->name ?></h4>

<table class="subtleWhite normalText">
    <tbody>
        <tr>
            <td width="30%"><?php echo CHtml::encode($element->getAttributeLabel('title')) ?></td>
            <td><span class="big"><?php echo $element->title ?></span></td>
        </tr>
        <tr>
            <td width="30%"><?php echo CHtml::encode($element->getAttributeLabel('description')) ?></td>
            <td><span class="big"><?php echo $element->description ?></span></td>
        </tr>
    </tbody>
</table>

<p>

    <?php
    try {
        Yii::import('application.modules.module_esb_mirth.models.*');
        $assetFile = ServiceBusUtils::getFile($element->asset_id);
    } catch (Exception $e) {
        
    }
    if ($assetFile) {
        ?>
        <a href="<?php echo ServiceBusUtils::getEncodedFileName($element->asset->id, $this->patient->hos_num, '/') ?>">Download <?php echo $element->asset->id ?></a>
    <h4 class="elementTypeName">Preview</h4>
    <img src="<?php echo ServiceBusUtils::getEncodedFileName($element->asset->id, $this->patient->hos_num, '/thumbs/') ?>" />
    <?php
} else {
    ?>
    <a href="<?php echo Yii::app()->createUrl('/asset/download/' . $element->asset_id) ?>">Download <?php echo $element->asset->name ?></a>
    <h4 class="elementTypeName">Preview</h4>
    <img src="<?php echo Yii::app()->createUrl('/asset/preview/' . $element->asset_id) ?>" />
    <?php
}
?>
</p>

