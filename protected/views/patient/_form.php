<div class="form" style="margin-left: 100px">

    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'patient-form',
        'enableAjaxValidation' => false));
    ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>


    <?php
    if ($this->patient) {
        $err = $this->patient->getErrors();
    }
    $patient = $this->patient;
    if (!$patient) {
        $patient = new Patient;
        if (isset($_GET['hos_num'])) {
            $patient->hos_num = $_GET['hos_num'];
        }
        $patient->contact = new Contact;
        $patient->address = new Address;
    }
    $contact = $patient->contact;
    $address = $patient->address;
    ?>
    <table cellpadding="2">
        <tr>
            <td>
                <?php echo $form->labelEx($patient, 'hos_num'); ?>
            </td>
            <td>
                <?php echo $form->labelEx($patient, 'nhs_num'); ?>
            </td>
            <td>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->textField($patient, 'hos_num'); ?>
            </td>
            <td>
                <?php echo $form->textField($patient, 'nhs_num'); ?>
            </td>
            <td>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->error($patient, 'hos_num'); ?>
            </td>
            <td>
                <?php echo $form->error($patient, 'nhs_num'); ?>
            </td>
            <td>
            </td>
        <tr>
            <td>
                <?php echo $form->labelEx($contact, 'first_name'); ?>
            </td>
            <td>
                <?php echo $form->labelEx($contact, 'last_name'); ?>
            </td>
            <td>
                <?php echo $form->labelEx($contact, 'title'); ?>
            </td>
        </tr>

        <tr>
            <td>
                <?php echo $form->textField($contact, 'first_name'); ?>
            </td>
            <td>
                <?php echo $form->textField($contact, 'last_name'); ?>
            </td>
            <td>
                <?php echo $form->textField($contact, 'title', array('size' => 4)); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->error($contact, 'first_name'); ?>
            </td>
            <td>
                <?php echo $form->error($contact, 'last_name'); ?>
            </td>
            <td>
                <?php echo $form->error($contact, 'title'); ?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <?php echo $form->labelEx($patient, 'dob') . " (yyyy-mm-dd)"; ?>
            </td>
            <td>
                <?php echo $form->labelEx($patient, 'gender'); ?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <?php echo $form->textField($patient, 'dob'); ?>
            </td>
            <td>
                <?php echo $form->textField($patient, 'gender', array('size' => 2, 'maxlength' => 1)); ?>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <?php echo $form->error($patient, 'dob'); ?>
            </td>
            <td>
                <?php echo $form->error($patient, 'gender'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->labelEx($address, 'address1'); ?>
            </td>
            <td>
                <?php echo $form->labelEx($address, 'address2'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->textField($address, 'address1'); ?>
            </td>
            <td>
                <?php echo $form->textField($address, 'address2'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->error($address, 'address1'); ?>
            </td>
            <td>
                <?php echo $form->error($address, 'address2'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->labelEx($address, 'city'); ?>
            </td>
            <td>
                <?php echo $form->labelEx($address, 'postcode'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->textField($address, 'city'); ?>
            </td>
            <td>
                <?php echo $form->textField($address, 'postcode'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $form->error($address, 'city'); ?>
            </td>
            <td>
                <?php echo $form->error($address, 'postcode'); ?>
            </td>
        </tr>

        <tr>
            <td>
                <?php echo $form->labelEx($address, 'email'); ?>
            </td>
            <td>
                <?php echo $form->labelEx($contact, 'primary_phone'); ?>
            </td>
            <td></td>
        </tr>
        <tr>
            <td>
                <?php echo $form->textField($address, 'email'); ?>
            </td>
            <td>
                <?php echo $form->textField($contact, 'primary_phone'); ?>
            </td>
            <td></td>
        </tr>
        <tr>
            <td>
                <?php echo $form->error($address, 'email'); ?>
            </td>
            <td>
                <?php echo $form->error($contact, 'primary_phone'); ?>
            </td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>
                <button type="submit" style="margin-right: 10px; float: left; display: block;" class="classy blue tall" id="findPatient_id" tabindex="2"><span class="button-span button-span-blue">Create</span></button>
            </td>
        </tr>
    </table>
</div>


<?php $this->endWidget(); ?>

</div><!-- form -->