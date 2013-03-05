<?php
$this->breadcrumbs = array(
    'Patients' => array('index'),
    'Create',
);

//$this->menu = array(
//    array('label' => 'List Patient', 'url' => array('index')),
//    array('label' => 'Manage Patient', 'url' => array('admin')),
//);
?>

<h1>Create Patient</h1>
<?php
if (isset($patient_exists)) {
    ?>
<div class="form" style="margin-left: 100px">
    <p class="note">The specified patient already exists.</p>
    </div>
    <?php
}
?>
<?php // echo $this->renderPartial('_form', array('model'=>$model)); ?>
<?php
$patient = new Patient();
echo $this->renderPartial('_form', array('model' => $patient));
?>