<script type="text/javascript" src="<?php echo URL?>/administrator/components/com_virtuemart/classes/shipping/inpostparcels/js/jquery-1.6.4.min.js"></script>
<!--<script type="text/javascript" src="--><?php //echo URL?><!--/administrator/components/com_virtuemart/classes/shipping/inpostparcels/js/inpostparcels/noconflict.js"></script>-->
<script type="text/javascript" src="https://geowidget.inpost.co.uk/dropdown.php?field_to_update=name&field_to_update2=address&user_function=user_function"></script>
<script type="text/javascript">
    function user_function(value) {
        var address = value.split(';');
        //document.getElementById('town').value=address[1];
        //document.getElementById('street').value=address[2]+address[3];
        var box_machine_name = document.getElementById('name').value;
        var box_machine_town = document.value=address[1];
        var box_machine_street = document.value=address[2];


        var is_value = 0;
        document.getElementById('shipping_inpostparcels').value = box_machine_name;
        var shipping_inpostparcels = document.getElementById('shipping_inpostparcels');

        for(i=0;i<shipping_inpostparcels.length;i++){
            if(shipping_inpostparcels.options[i].value == document.getElementById('name').value){
                shipping_inpostparcels.selectedIndex = i;
                is_value = 1;
            }
        }

        if (is_value == 0){
            shipping_inpostparcels.options[shipping_inpostparcels.options.length] = new Option(box_machine_name+','+box_machine_town+','+box_machine_street, box_machine_name);
            shipping_inpostparcels.selectedIndex = shipping_inpostparcels.length-1;
        }
    }
</script>

<script type="text/javascript" src="https://geowidget.inpost.co.uk/dropdown.php?field_to_update=name_source&field_to_update2=address_source&user_function=user_function_source"></script>
<script type="text/javascript">
    function user_function_source(value) {
        var address = value.split(';');
        //document.getElementById('town').value=address[1];
        //document.getElementById('street').value=address[2]+address[3];
        var box_machine_name = document.getElementById('name_source').value;
        var box_machine_town = document.value=address[1];
        var box_machine_street = document.value=address[2];


        var is_value = 0;
        document.getElementById('shipping_inpostparcels_source').value = box_machine_name;
        var shipping_inpostparcels = document.getElementById('shipping_inpostparcels_source');

        for(i=0;i<shipping_inpostparcels.length;i++){
            if(shipping_inpostparcels.options[i].value == document.getElementById('name_source').value){
                shipping_inpostparcels.selectedIndex = i;
                is_value = 1;
            }
        }

        if (is_value == 0){
            shipping_inpostparcels.options[shipping_inpostparcels.options.length] = new Option(box_machine_name+','+box_machine_town+','+box_machine_street, box_machine_name);
            shipping_inpostparcels.selectedIndex = shipping_inpostparcels.length-1;
        }
    }
</script>



<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
mm_showMyFileName( __FILE__ );

require_once(CLASSPATH ."shipping/inpostparcels/helpers/inpostparcelsHelper.php");
require_once(CLASSPATH ."shipping/inpostparcels.cfg.php");

//First create the object and let it print a form heading
$formObj = new formFactory( $VM_LANG->_('PHPSHOP_CARRIER_FORM_LBL') );

//Then Start the form
$formObj->startForm();

$shipping_carrier_id = vmGet( $_REQUEST, 'id');
$option = empty($option)?vmGet( $_REQUEST, 'option', 'com_virtuemart'):$option;
$conf =& JFactory::getConfig();
$db_prefix = $conf->getValue('config.dbprefix');

$db = new ps_DB;
if (!empty($id)) {
  $q = "SELECT * FROM ".$db_prefix."order_shipping_inpostparcels WHERE id='$id'";
  $db->query($q);
  $db->next_record();
}


if ($db->f("id") || $id == 0) {
    $parcelTargetMachineDetailDb = json_decode($db->f('parcel_target_machine_detail'));
    $parcelDetailDb = json_decode($db->f('parcel_detail'));

    // set disabled
    $disabledCodAmount = '';
    $disabledDescription = '';
    $disabledInsuranceAmount = '';
    $disabledReceiverPhone = '';
    $disabledReceiverEmail = '';
    $disabledParcelSize = '';
    $disabledParcelStatus = '';
    $disabledSourceMachine = '';
    $disabledTmpId = '';
    $disabledTargetMachine = '';

    if($db->f('parcel_status') != 'Created' && $db->f('parcel_status') != ''){
        $disabledCodAmount = 'disabled';
        $disabledDescription = 'disabled';
        $disabledInsuranceAmount = 'disabled';
        $disabledReceiverPhone = 'disabled';
        $disabledReceiverEmail = 'disabled';
        $disabledParcelSize = 'disabled';
        $disabledParcelStatus = 'disabled';
        $disabledSourceMachine = 'disabled';
        $disabledTmpId = 'disabled';
        $disabledTargetMachine = 'disabled';
    }
    if($db->f('parcel_status') == 'Created'){
        $disabledCodAmount = 'disabled';
        //$disabledDescription = 'disabled';
        $disabledInsuranceAmount = 'disabled';
        $disabledReceiverPhone = 'disabled';
        $disabledReceiverEmail = 'disabled';
        //$disabledParcelSize = 'disabled';
        //$disabledParcelStatus = 'disabled';
        $disabledSourceMachine = 'disabled';
        $disabledTmpId = 'disabled';
        $disabledTargetMachine = 'disabled';
    }

    $allMachines = inpostparcelsHelper::connectInpostparcels(
        array(
            'url' => API_URL.'machines',
            'token' => API_KEY,
            'methodType' => 'GET',
            'params' => array(
            )
        )
    );

    // target machines
    $parcelTargetAllMachinesId = array();
    $parcelTargetAllMachinesDetail = array();
    $machines = array();
    if(is_array(@$allMachines['result']) && !empty($allMachines['result'])){
        foreach($allMachines['result'] as $key => $machine){
            $parcelTargetAllMachinesId[$machine->id] = $machine->id.', '.@$machine->address->city.', '.@$machine->address->street;
            $parcelTargetAllMachinesDetail[$machine->id] = array(
                'id' => $machine->id,
                'address' => array(
                    'building_number' => @$machine->address->building_number,
                    'flat_number' => @$machine->address->flat_number,
                    'post_code' => @$machine->address->post_code,
                    'province' => @$machine->address->province,
                    'street' => @$machine->address->street,
                    'city' => @$machine->address->city
                )
            );
            if($machine->address->post_code == @$parcelTargetMachineDetailDb->address->post_code){
                $machines[$key] = $machine;
                continue;
            }elseif($machine->address->city == @$parcelTargetMachineDetailDb->address->city){
                $machines[$key] = $machine;
            }
        }
    }

    $parcelTargetMachinesId = array();
    $parcelTargetMachinesDetail = array();
    $defaultTargetMachine = $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_MACHINE');
    if(is_array(@$machines) && !empty($machines)){
        foreach($machines as $key => $machine){
            $parcelTargetMachinesId[$machine->id] = $machine->id.', '.@$machine->address->city.', '.@$machine->address->street;
            $parcelTargetMachinesDetail[$machine->id] = $parcelTargetAllMachinesDetail[$machine->id];
        }
    }else{
        $defaultTargetMachine = $VM_LANG->_('INPOSTPARCELS_VIEW_DEFAULT_SELECT');
    }

    //$parcel['api_source'] = 'PL';
    $parcelInsurancesAmount = array();
    $defaultInsuranceAmount = $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_INSURANCE');
    switch($db->f('api_source')){
        case 'PL':
            $api = inpostparcelsHelper::connectInpostparcels(
                array(
                    'url' => API_URL.'customer/pricelist',
                    'token' => API_KEY,
                    'methodType' => 'GET',
                    'params' => array(
                    )
                )
            );

            if(isset($api['result']) && !empty($api['result'])){
                $parcelInsurancesAmount = array(
                    'insurance_price1' => $api['result']->insurance_price1,
                    'insurance_price2' => $api['result']->insurance_price2,
                    'insurance_price3' => $api['result']->insurance_price3
                );
            }

            $_SESSION['inpostparcels']['parcelInsurancesAmount'] = $parcelInsurancesAmount;
            $parcelSourceAllMachinesId = array();
            $parcelSourceAllMachinesDetail = array();
            $machines = array();
            $shopCities = explode(',',SHOP_CITIES);

            if(is_array(@$allMachines['result']) && !empty($allMachines['result'])){
                foreach($allMachines['result'] as $key => $machine){
                    $parcelSourceAllMachinesId[$machine->id] = $machine->id.', '.@$machine->address->city.', '.@$machine->address->street;
                    $parcelSourceAllMachinesDetail[$machine->id] = array(
                        'id' => $machine->id,
                        'address' => array(
                            'building_number' => @$machine->address->building_number,
                            'flat_number' => @$machine->address->flat_number,
                            'post_code' => @$machine->address->post_code,
                            'province' => @$machine->address->province,
                            'street' => @$machine->address->street,
                            'city' => @$machine->address->city
                        )
                    );
                    if(in_array($machine->address->city, $shopCities)){
                        $machines[$key] = $machine;
                    }
                }
            }

            $parcelSourceMachinesId = array();
            $parcelSourceMachinesDetail = array();
            $defaultSourceMachine = $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_MACHINE');
            if(is_array(@$machines) && !empty($machines)){
                foreach($machines as $key => $machine){
                    $parcelSourceMachinesId[$machine->id] = $machine->id.', '.@$machine->address->city.', '.@$machine->address->street;
                    $parcelSourceMachinesDetail[$machine->id] = $parcelSourceAllMachinesDetail[$machine->id];
                }
            }else{
                $defaultSourceMachine = $VM_LANG->_('INPOSTPARCELS_VIEW_DEFAULT_SELECT');
            }
            break;
    }

    $inpostparcelsData = array(
        'id' => $db->f("id"),
        'parcel_id' => $db->f("parcel_id"),

        'parcel_cod_amount' => @$parcelDetailDb->cod_amount,
        'parcel_description' => @$parcelDetailDb->description,
        'parcel_insurance_amount' => @$parcelDetailDb->insurance_amount,
        'parcel_receiver_phone' => @$parcelDetailDb->receiver->phone,
        'parcel_receiver_email' => @$parcelDetailDb->receiver->email,
        'parcel_size' => @$parcelDetailDb->size,
        'parcel_status' => $db->f("parcel_status"),
        'parcel_source_machine_id' => @$parcelDetailDb->source_machine,
        'parcel_tmp_id' => @$parcelDetailDb->tmp_id,
        'parcel_target_machine_id' => @$parcelDetailDb->target_machine,
    );

    $defaultParcelSize = @$parcelDetailDb->size;

} else {
    $vmLogger->err($VM_LANG->_('INPOSTPARCELS_VIEW_ERR_1'));
}

?><br />
<input type="hidden" name="parcel_id" value="<?php echo $inpostparcelsData['parcel_id']; ?>" />
<input type="hidden" name="id" value="<?php echo $inpostparcelsData['id']; ?>" />

<table class="adminform">

    <?php if(in_array($db->f('api_source'), array('PL'))): ?>
    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_COD_AMOUNT') ?>:</td>
        <td><input name="parcel_cod_amount" value="<?php echo $inpostparcelsData['parcel_cod_amount']; ?>" <?php echo $disabledCodAmount; ?> <?php ?>/></td>
    </tr>
    <?php endif; ?>

    <tr>
        <td align="right" valign="top"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_DESCRIPTION') ?>:</td>
        <td><textarea name="parcel_description" rows="10" cols="35" <?php echo $disabledDescription; ?>><?php echo $inpostparcelsData['parcel_description']; ?></textarea></td>
    </tr>

    <?php if(in_array($db->f('api_source'), array('PL'))): ?>
    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_INSURANCE_AMOUNT') ?>:</td>
        <td>
            <select id="parcel_size" name="parcel_insurance_amount" <?php echo $disabledInsuranceAmount; ?>>
                <option value='' <?php if(@$inpostparcelsData['parcel_insurance_amount'] == ''){ echo "selected=selected";} ?>><?php echo $defaultInsuranceAmount; ?></option>
                <?php foreach($parcelInsurancesAmount as $key => $parcelInsuranceAmount): ?>
                <option value='<?php echo $key ?>' <?php if($inpostparcelsData['parcel_insurance_amount'] == $key){ echo "selected=selected";} ?>><?php echo $parcelInsuranceAmount;?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php endif; ?>

    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_RECEIVER_PHONE') ?>:</td>
        <td><input name="parcel_receiver_phone" value="<?php echo $inpostparcelsData['parcel_receiver_phone']; ?>" <?php echo $disabledReceiverPhone; ?>/></td>
    </tr>

    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_RECEIVER_EMAIL') ?>:</td>
        <td><input name="parcel_receiver_email" value="<?php echo $inpostparcelsData['parcel_receiver_email']; ?>" <?php echo $disabledReceiverEmail; ?>/></td>
    </tr>

    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE') ?>:</td>
        <td>
            <select id="parcel_size" name="parcel_size" <?php echo $disabledParcelSize; ?>>
                <option value='' <?php if($inpostparcelsData['parcel_size'] == ''){ echo "selected=selected";} ?>><?php echo $defaultParcelSize;?></option>
                <option value='<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_A') ?>' <?php if($inpostparcelsData['parcel_size'] == $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_A')){ echo "selected=selected";} ?>><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_A') ?></option>
                <option value='<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_B') ?>' <?php if($inpostparcelsData['parcel_size'] == $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_B')){ echo "selected=selected";} ?>><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_B') ?></option>
                <option value='<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_C') ?>' <?php if($inpostparcelsData['parcel_size'] == $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_C')){ echo "selected=selected";} ?>><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SIZE_C') ?></option>
            </select>
        </td>
    </tr>

    <?php if($db->f('parcel_status') != ''): ?>
    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_STATUS') ?>:</td>
        <td><input name="parcel_status" value="<?php echo $inpostparcelsData['parcel_status']; ?>" <?php echo $disabledParcelStatus; ?>/></td>
    </tr>
    <?php endif; ?>

    <?php if(in_array($db->f('api_source'), array('PL'))): ?>
    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SOURCE_MACHINE') ?>:</td>
        <td>
            <select id="shipping_inpostparcels_source" name="parcel_source_machine_id" <?php echo $disabledSourceMachine; ?>>
                <option value='' <?php if(@$inpostparcelsData['parcel_source_machine_id'] == ''){ echo "selected=selected";} ?>><?php echo $defaultSourceMachine;?></option>
                <?php foreach($parcelSourceMachinesId as $key => $parcelSourceMachine): ?>
                <option value='<?php echo $key ?>' <?php if($inpostparcelsData['parcel_source_machine_id'] == $key){ echo "selected=selected";} ?>><?php echo $parcelSourceMachine;?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" id="name_source" name="name_source" disabled="disabled" />
            <input type="hidden" id="box_machine_town_source" name="box_machine_town_source" disabled="disabled" />
            <input type="hidden" id="address_source" name="address_source" disabled="disabled" />
            <a href="#" onclick="openMap(); return false;"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_MAP') ?></a>
            &nbsp|&nbsp<input type="checkbox" name="show_all_machines_source" <?php echo $disabledSourceMachine; ?>> <?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SHOW_TERMINAL') ?>
        </td>
    </tr>
    <?php endif; ?>

    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_TMP_ID') ?>:</td>
        <td><input name="parcel_tmp_id" value="<?php echo $inpostparcelsData['parcel_tmp_id']; ?>" <?php echo $disabledTmpId; ?>/></td>
    </tr>

    <tr>
        <td align="right"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_TARGET_MACHINE') ?>:</td>
        <td>
            <select id="shipping_inpostparcels" name="parcel_target_machine_id" <?php echo $disabledTargetMachine; ?>>
                <option value='' <?php if(@$inpostparcelsData['parcel_target_machine_id'] == ''){ echo "selected=selected";} ?>><?php echo $defaultTargetMachine;?></option>
                <?php foreach($parcelTargetMachinesId as $key => $parcelTargetMachine): ?>
                <option value='<?php echo $key ?>' <?php if($inpostparcelsData['parcel_target_machine_id'] == $key){ echo "selected=selected";} ?>><?php echo $parcelTargetMachine;?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" id="name" name="name" disabled="disabled" />
            <input type="hidden" id="box_machine_town" name="box_machine_town" disabled="disabled" />
            <input type="hidden" id="address" name="address" disabled="disabled" />
            <a href="#" onclick="openMap(); return false;"><?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_MAP') ?></a>
            &nbsp|&nbsp<input type="checkbox" name="show_all_machines" <?php echo $disabledTargetMachine; ?>> <?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SHOW_TERMINAL') ?>
        </td>
    </tr>

</table>
<?php

// Add necessary hidden fields
$formObj->hiddenField( 'id', $id );

//$funcname = !empty($shipping_cid) ? "carrierupdate" : "carrieradd";

// finally close the form:
$formObj->finishForm( 'inpostparcelsUpdate', $modulename.'.inpostparcels_list', $option );
?>
<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('input[type="checkbox"][name="show_all_machines"]').click(function(){
            var machines_list_type = jQuery(this).is(':checked');

            if(machines_list_type == true){
                //alert('all machines');
                var machines = {
                    '' : '<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_MACHINE') ?>..',
                <?php foreach($parcelTargetAllMachinesId as $key => $parcelTargetAllMachineId): ?>
                    '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetAllMachineId) ?>',
                    <?php endforeach; ?>
                };
            }else{
                //alert('criteria machines');
                var machines = {
                    '' : '<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_MACHINE') ?>..',
                <?php foreach($parcelTargetMachinesId as $key => $parcelTargetMachineId): ?>
                    '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetMachineId) ?>',
                    <?php endforeach; ?>
                };
            }

            jQuery('#shipping_inpostparcels option').remove();
            jQuery.each(machines, function(val, text) {
                jQuery('#shipping_inpostparcels').append(
                        jQuery('<option></option>').val(val).html(text)
                );
            });
        });
        jQuery('input[type="checkbox"][name="show_all_machines_source"]').click(function(){
            var machines_list_type = jQuery(this).is(':checked');

            if(machines_list_type == true){
                //alert('all machines');
                var machines = {
                    '' : '<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_MACHINE') ?>..',
                <?php foreach($parcelTargetAllMachinesId as $key => $parcelTargetAllMachineId): ?>
                    '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetAllMachineId) ?>',
                    <?php endforeach; ?>
                };
            }else{
                //alert('criteria machines');
                var machines = {
                    '' : '<?php echo $VM_LANG->_('INPOSTPARCELS_VIEW_SELECT_MACHINE') ?>..',
                <?php foreach($parcelTargetMachinesId as $key => $parcelTargetMachineId): ?>
                    '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetMachineId) ?>',
                    <?php endforeach; ?>
                };
            }

            jQuery('#shipping_inpostparcels_source option').remove();
            jQuery.each(machines, function(val, text) {
                jQuery('#shipping_inpostparcels_source').append(
                        jQuery('<option></option>').val(val).html(text)
                );
            });
        });

    });
</script>