<script type="text/javascript" src="<?php echo URL?>/administrator/components/com_virtuemart/classes/shipping/easypack24/js/jquery-1.6.4.min.js"></script>
<script type="text/javascript" src="<?php echo URL?>/administrator/components/com_virtuemart/classes/shipping/easypack24/js/easypack24/noconflict.js"></script>
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
        document.getElementById('shipping_easypack24').value = box_machine_name;
        var shipping_easypack24 = document.getElementById('shipping_easypack24');

        for(i=0;i<shipping_easypack24.length;i++){
            if(shipping_easypack24.options[i].value == document.getElementById('name').value){
                shipping_easypack24.selectedIndex = i;
                is_value = 1;
            }
        }

        if (is_value == 0){
            shipping_easypack24.options[shipping_easypack24.options.length] = new Option(box_machine_name+','+box_machine_town+','+box_machine_street, box_machine_name);
            shipping_easypack24.selectedIndex = shipping_easypack24.length-1;
        }
    }
</script>

<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
mm_showMyFileName( __FILE__ );

require_once(CLASSPATH ."shipping/easypack24/helpers/easypack24Helper.php");
require_once(CLASSPATH ."shipping/easypack24.cfg.php");

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
  $q = "SELECT * FROM ".$db_prefix."order_shipping_easypack24 WHERE id='$id'";
  $db->query($q);
  $db->next_record();
}


if ($db->f("id") || $id == 0) {

    $parcelTargetMachineDetailDb = json_decode($db->f("parcel_target_machine_detail"));
    $parcelDetailDb = json_decode($db->f("parcel_detail"));

    $allMachines = easypack24Helper::connectEasypack24(
        array(
            'url' => API_URL.'machines',
            'token' => API_KEY,
            'methodType' => 'GET',
            'params' => array(
            )
        )
    );

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
        //Mage::getSingleton('checkout/session')->setParcelTargetAllMachinesDetail($parcelTargetAllMachinesDetail);
    }

    $parcelTargetMachinesId = array();
    $parcelTargetMachinesDetail = array();
    $defaultSelect = 'Select Machine..';
    if(is_array(@$machines) && !empty($machines)){
        foreach($machines as $key => $machine){
            $parcelTargetMachinesId[$machine->id] = $machine->id.', '.@$machine->address->city.', '.@$machine->address->street;
            $parcelTargetMachinesDetail[$machine->id] = array(
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
        }
    }else{
        $defaultMachine = 'no terminals in your city';
    }

    $easypack24Data = array(
        'id' => $db->f("id"),
        'parcel_target_machine_id' => $db->f("parcel_target_machine_id"),
        'parcel_description' => @$parcelDetailDb->description,
        'parcel_size' => @$parcelDetailDb->size,
        'parcel_status' => $db->f("parcel_status"),
        'parcel_id' => $db->f("parcel_id")
    );

    $defaultParcelSize = @$parcelDetailDb->size;

    $disabledMachines = 'disabled';
    if($db->f("parcel_status") != 'Created' || $db->f("parcel_status") == ''){
        $disabledParcelSize = 'disabled';
    }
} else {
    $vmLogger->err('Item does not exist');
}

?><br />
<input type="hidden" name="parcel_id" value="<?php echo $easypack24Data['parcel_id']; ?>" />
<input type="hidden" name="id" value="<?php echo $easypack24Data['id']; ?>" />

<table class="adminform">
    <tr>
        <td>
            <select id="shipping_easypack24" name="parcel_target_machine_id" <?php echo $disabledMachines; ?>>
                <option value='' <?php if(@$easypack24Data['parcel_target_machine_id'] == ''){ echo "selected=selected";} ?>><?php echo $defaultMachine;?></option>
                <?php foreach($parcelTargetMachinesId as $key => $parcelTargetMachine): ?>
                <option value='<?php echo $key ?>' <?php if($easypack24Data['parcel_target_machine_id'] == $key){ echo "selected=selected";} ?>><?php echo $parcelTargetMachine;?></option>
                <?php endforeach; ?>
            </select>
            <?php if($disabledMachines != 'disabled'): ?>
            <input type="hidden" id="name" name="name" disabled="disabled" />
            <input type="hidden" id="box_machine_town" name="box_machine_town" disabled="disabled" />
            <input type="hidden" id="address" name="address" disabled="disabled" />
            <a href="#" onclick="openMap(); return false;">Map</a>
            &nbsp|&nbsp<input type="checkbox" name="show_all_machines"> Show terminals in other cities
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td><textarea name="parcel_description" rows="10" cols="35"><?php echo $easypack24Data['parcel_description']; ?></textarea></td>
    </tr>
    <tr>
        <td>
            <select id="parcel_size" name="parcel_size" <?php echo $disabledParcelSize; ?>>
                <option value='' <?php if($easypack24Data['parcel_size'] == ''){ echo "selected=selected";} ?>><?php echo $defaultParcelSize;?></option>
                <option value='A' <?php if($easypack24Data['parcel_size'] == 'A'){ echo "selected=selected";} ?>>A</option>
                <option value='B' <?php if($easypack24Data['parcel_size'] == 'B'){ echo "selected=selected";} ?>>B</option>
                <option value='C' <?php if($easypack24Data['parcel_size'] == 'C'){ echo "selected=selected";} ?>>C</option>
            </select>
        </td>
    </tr>
    <tr>
        <td><input class="input-text required-entry" name="parcel_status" value="<?php echo $easypack24Data['parcel_status']; ?>" <?php ?>/></td>
    </tr>
</table>
<?php

// Add necessary hidden fields
$formObj->hiddenField( 'id', $id );

//$funcname = !empty($shipping_cid) ? "carrierupdate" : "carrieradd";

// finally close the form:
$formObj->finishForm( 'easypack24Update', $modulename.'.easypack24_list', $option );
?>
<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('input[type="checkbox"][name="show_all_machines"]').click(function(){
            var machines_list_type = jQuery(this).is(':checked');

            if(machines_list_type == true){
                //alert('all machines');
                var machines = {
                    '' : 'Select Machine..',
                <?php foreach($parcelTargetAllMachinesId as $key => $parcelTargetAllMachineId): ?>
                    '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetAllMachineId) ?>',
                    <?php endforeach; ?>
                };
            }else{
                //alert('criteria machines');
                var machines = {
                    '' : 'Select Machine..',
                <?php foreach($parcelTargetMachinesId as $key => $parcelTargetMachineId): ?>
                    '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetMachineId) ?>',
                    <?php endforeach; ?>
                };
            }

            jQuery('#shipping_easypack24 option').remove();
            jQuery.each(machines, function(val, text) {
                jQuery('#shipping_easypack24').append(
                        jQuery('<option></option>').val(val).html(text)
                );
            });
        });
    });
</script>