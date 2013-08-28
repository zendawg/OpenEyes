<?php
$this->breadcrumbs = array(
    'Patients' => array('index'),
    'Edit',
);
//$this->menu = array(
//    array('label' => 'List Patient', 'url' => array('index')),
//    array('label' => 'Manage Patient', 'url' => array('admin')),
//);
?>

<h1>Edit Patient</h1>

<?php // echo $this->renderPartial('_form', array('model'=>$model)); ?>
<?php
echo $this->renderPartial('_form', array('action' => 'Edit'));
?>