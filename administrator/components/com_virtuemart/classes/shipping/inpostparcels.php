<script type="text/javascript" src="<?php echo URL?>/administrator/components/com_virtuemart/classes/shipping/inpostparcels/js/jquery-1.6.4.min.js"></script>
<!--<script type="text/javascript" src="--><?php //echo URL?><!--/administrator/components/com_virtuemart/classes/shipping/inpostparcels/js/inpostparcels/noconflict.js"></script>-->
<script type="text/javascript" src="https://geowidget.inpost.co.uk/dropdown.php?field_to_update=name&field_to_update2=address&user_function=user_function"></script>

<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

require_once(CLASSPATH ."shipping/inpostparcels/helpers/inpostparcelsHelper.php");

inpostparcelsHelper::setLang();

class inpostparcels {

	var $classname = "inpostparcels";


	function list_rates( &$d ) {
		global $total, $tax_total, $CURRENCY_DISPLAY, $vmLogger, $VM_LANG;

        //echo $VM_LANG->_('TEST_LANG');

		$cart = $_SESSION['cart'];

		require_once(CLASSPATH ."shipping/".$this->classname.".cfg.php");

		if ( $_SESSION['auth']['show_price_including_tax'] != 1 ) {
			$taxrate = 1;
			$order_total = $total + $tax_total;
		}
		else {
			$taxrate = $this->get_tax_rate() + 1;
			$order_total = $total;
		}

		$dbu = new ps_DB;
		$q  = "SELECT user_id, country, zip,user_email, city, phone_2 FROM #__{vm}_user_info WHERE user_info_id = '". $d["ship_to_info_id"] . "'";
		$dbu->query($q);

        if (!$dbu->next_record()) {
		}

        $phone_from_db = $dbu->f("phone_2");
        if(!preg_match('/^[1-9]{1}\d{8}$/', $phone_from_db)){
            $phone_from_db = null;
        }
		$Order_Destination_Postcode = $dbu->f("zip");

		$Order_WeightKG = $d['weight'] ;
		$Order_Weight = $Order_WeightKG * 1000;
		$Order_Handling_Fee = PRICE;
		$i=0;

        $Total_Shipping_Handling =$Order_Handling_Fee;
        // $shipping_rate_id = urlencode($id_string);
        //$_SESSION[$shipping_rate_id] = 1;
        $shipping = urlencode( $this->classname."|inpostparcels||".number_format($Total_Shipping_Handling,2)."|8");


        // check countries
        $dbe = new ps_DB;
        $q = "SELECT country_3_code, country_2_code FROM #__{vm}_country";
        $dbe->query($q);
        $all_countries_2 = $dbe->loadAssocList('country_2_code');
        $all_countries_3 = $dbe->loadAssocList('country_3_code');

        if(strlen($dbu->f('country')) == 2){
            $country = $all_countries_2[$dbu->f('country')]['country_2_code'];
        }else{
            $country = $all_countries_3[$dbu->f('country')]['country_2_code'];
        }

        if($country == 'GB'){$country = 'UK';}

        if($country != ALLOWED_COUNTRY){
            return false;
        }



        // check weight
        if(@$d['weight'] != 0 && $d['weight'] > MAX_WEIGHT){
            ?>
                <input type="radio" name="shipping_rate_id" DISABLED id="inpostparcels" value="<?php $shipping; ?>"> <?php echo $VM_LANG->_('INPOSTPARCELS_NAME') ?>: <?php echo $CURRENCY_DISPLAY->getFullValue($Total_Shipping_Handling) ?> ( <font color="red"> <?php echo $VM_LANG->_('INPOSTPARCELS_MAX_WEIGHT_IS') ?>: <?php echo MAX_WEIGHT ?>, <?php echo $VM_LANG->_('INPOSTPARCELS_ACTUAL') ?>: <?php echo $d['weight'] ?> </font>);
            <?php
            return true;
        }

        // check dimensions ( multiple product )
        $product_ids = array();
        $max_dimension = array();
        $is_dimension = true;
        if(!empty($cart)){
            foreach($cart as $key => $value){
                if(is_array($value) && isset($value['product_id'])){
                    $product_ids[] = $value['product_id'];
                }
            }
        }
        $q  = "SELECT product_width as width, product_height as height, product_length AS depth FROM #__{vm}_product WHERE product_id IN (". implode($product_ids, ',') . ")";
        $dbv = new ps_DB;
        $dbv->query($q);
        while( $dbv->next_record() ) {
            $product_dimensions[] = (float)$dbv->f("width").'x'.(float)$dbv->f("height").'x'.(float)$dbv->f("depth");
        }

        $calculateDimension = inpostparcelsHelper::calculateDimensions($product_dimensions,
            array(
                'MAX_DIMENSION_A' => MAX_DIMENSION_A,
                'MAX_DIMENSION_B' => MAX_DIMENSION_B,
                'MAX_DIMENSION_C' => MAX_DIMENSION_C
            )
        );

        if(!$calculateDimension['isDimension']){
                ?>
                <input type="radio" name="shipping_rate_id" DISABLED id="inpostparcels" value="<?php $shipping; ?>"> <?php echo $VM_LANG->_('INPOSTPARCELS_NAME') ?>: <?php echo $CURRENCY_DISPLAY->getFullValue($Total_Shipping_Handling) ?> ( <font color="red"> <?php echo $VM_LANG->_('INPOSTPARCELS_MAX_DIMENSION_IS') ?>: <?php echo MAX_DIMENSION_C ?></font>);
                <?php
                    return true;
        }
        $_SESSION['inpostparcels']['parcel_size'] = $calculateDimension['parcelSize'];

        if($dbu->f("user_email") != ''){
            $_SESSION['inpostparcels']['user_email'] = $dbu->f("user_email");
        }else{
            $dbi = new ps_DB;
            $q  = "SELECT user_email FROM #__{vm}_user_info WHERE user_id = '". $dbu->f("user_id") . "' and user_email IS NOT NULL";
            $dbi->query($q);
            $_SESSION['inpostparcels']['user_email'] = $dbi->f("user_email");
        }

        // get machines
        require_once(CLASSPATH ."shipping/inpostparcels/helpers/inpostparcelsHelper.php");
        $allMachines = inpostparcelsHelper::connectInpostparcels(
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
                if($machine->address->post_code == $dbu->f("zip")){
                    $machines[$key] = $machine;
                    continue;
                }elseif($machine->address->city == $dbu->f("city")){
                    $machines[$key] = $machine;
                }
                $_SESSION['inpostparcels']['parcelTargetAllMachinesId'] = $parcelTargetAllMachinesId;
                $_SESSION['inpostparcels']['parcelTargetAllMachinesDetail'] = $parcelTargetAllMachinesDetail;
            }
        }

        $parcelTargetMachinesId = array();
        $parcelTargetMachinesDetail = array();
        $defaultSelect = $VM_LANG->_('INPOSTPARCELS_SELECT_MACHINE');
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
            $_SESSION['inpostparcels']['parcelTargetMachinesId'] = $parcelTargetMachinesId;
        }else{
            $defaultSelect = $VM_LANG->_('INPOSTPARCELS_DEFAULT_SELECT');
        }

        $is_checked = false;
        if(preg_match('/^inpostparcels/', @$d["shipping_rate_id"], $matches)){
            $checked = "checked=\"checked\"";
            $is_checked = true;
        }else{
            $checked = "";
        };

        ?>
        <input type="radio" name="shipping_rate_id" id="inpostparcels" <?php echo $checked; ?> value="<?php $shipping; ?>"> <?php echo $VM_LANG->_('INPOSTPARCELS_NAME') ?>: <?php echo $CURRENCY_DISPLAY->getFullValue($Total_Shipping_Handling) ?>
        <br>&nbsp; &nbsp; &nbsp; &nbsp; <select id="shipping_inpostparcels" onChange="choose_from_dropdown()" name="shipping_inpostparcels[parcel_target_machine_id]">
        <option value='' <?php if(@$_POST['shipping_inpostparcels']['parcel_target_machine_id'] == ''){ echo "selected=selected";} ?>><?php echo $defaultSelect;?></option>
            <?php foreach(@$parcelTargetMachinesId as $key => $parcelTargetMachineId): ?>
                <option value='<?php echo $key ?>' <?php if(@$_POST['shipping_inpostparcels']['parcel_target_machine_id'] == $key){ echo "selected=selected";} ?>><?php echo @$parcelTargetMachineId;?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" id="name" name="name" disabled="disabled" />
        <input type="hidden" id="box_machine_town" name="box_machine_town" disabled="disabled" />
        <input type="hidden" id="address" name="address" disabled="disabled" />
        <br>&nbsp; &nbsp; &nbsp; &nbsp;
        <a href="#" onclick="openMap(); return false;"><?php echo $VM_LANG->_('INPOSTPARCELS_MAP') ?></a>&nbsp|&nbsp<input type="checkbox" name="show_all_machines"> <?php echo $VM_LANG->_('INPOSTPARCELS_SHOW_TERMINAL') ?>
        <br>
        <br>&nbsp; &nbsp; &nbsp; &nbsp;<b><?php echo $VM_LANG->_('INPOSTPARCELS_MOB_EXAMPLE') ?>: </b>
        <br>&nbsp; &nbsp; &nbsp; &nbsp;(07)<input type='text' onChange="choose_from_dropdown()" name='shipping_inpostparcels[receiver_phone]' title="mobile /^[1-9]{1}\d{8}$/" id="inpostparcels_phone" title="mobile /^[1-9]{1}\d{8}$/" value='<?php echo @$_POST['shipping_inpostparcels']['receiver_phone']?@$_POST['shipping_inpostparcels']['receiver_phone']:$phone_from_db; ?>' />

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

                document.getElementById('inpostparcels').value = 'inpostparcels%7Cinpostparcels%7C'+address[0]+'/mob:'+document.getElementById('inpostparcels_phone').value+'%7C<?php echo number_format($Total_Shipping_Handling,2)."%7C8";?>';
            }

            function choose_from_dropdown() {
                document.getElementById('inpostparcels').value = 'inpostparcels%7Cinpostparcels%7C'+document.getElementById('shipping_inpostparcels').value+'/mob:'+document.getElementById('inpostparcels_phone').value+'%7C<?php echo number_format($Total_Shipping_Handling,2)."%7C8";?>';
            }

            jQuery(document).ready(function(){
                jQuery('input[type="checkbox"][name="show_all_machines"]').click(function(){
                    var machines_list_type = jQuery(this).is(':checked');

                    if(machines_list_type == true){
                        //alert('all machines');
                        var machines = {
                            '' : '<?php echo $VM_LANG->_('INPOSTPARCELS_SELECT_MACHINE') ?>..',
                            <?php foreach($_SESSION['inpostparcels']['parcelTargetAllMachinesId'] as $key => $parcelTargetAllMachineId): ?>
                                '<?php echo $key ?>' : '<?php echo addslashes($parcelTargetAllMachineId) ?>',
                                <?php endforeach; ?>
                        };
                    }else{
                        //alert('criteria machines');
                        var machines = {
                            '' : '<?php echo $VM_LANG->_('INPOSTPARCELS_SELECT_MACHINE') ?>..',
                            <?php foreach($_SESSION['inpostparcels']['parcelTargetMachinesId'] as $key => $parcelTargetMachineId): ?>
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
                <?php
                if($is_checked){
                    ?>
                    jQuery('input[type="radio"][name="shipping_rate_id"]').attr("checked", false);
                    jQuery('input[type="radio"][id="inpostparcels"]').attr("checked", true);
                    <?php
                }
                if(isset($_POST['shipping_inpostparcels']['parcel_target_machine_id']) && $_POST['shipping_rate_id'] == ''){
                    ?>
                    jQuery('input[type="radio"][name="shipping_rate_id"]').attr("checked", false);
                    jQuery('input[type="radio"][id="inpostparcels"]').attr("checked", true);
                    <?php
                }
                ?>
            });

        </script>

        <?php
		$_SESSION[$shipping] = 1;

		//echo $html;
		//echo('</table>');
		return true;
	}

    function save_rate_info(&$d) {
        global $vmLogger;

        if(!isset($_SESSION['inpostparcels']['parcelTargetAllMachinesDetail'])){
            return;
        }

        $parcel_target_machine_id = explode("|", urldecode(urldecode($d['shipping_rate_id'])) );
        $parcel_target_machine_id = explode("/", $parcel_target_machine_id[2]);

        $order_id = @$d['order_id'];
        $parcel_detail = array(
            'description' => 'Order number:'.$order_id,
            'receiver' => array(
                'email' => @$_SESSION['inpostparcels']['user_email'],
                'phone' => str_replace('mob:', '', @$parcel_target_machine_id[1]),
            ),
            'size' => @$_SESSION['inpostparcels']['parcel_size'],
            'tmp_id' => inpostparcelsHelper::generate(4, 15),
            'target_machine' => @$parcel_target_machine_id[0]
        );

        switch (inpostparcelsHelper::getCurrentApi()){
            case 'PL':
                $parcel_detail['cod_amount'] = ($d['payment_method_id'] == 2)? sprintf("%.2f" ,$d['order_total']) : '';
                break;
        }

        $parcel_target_machine_id = @$parcel_target_machine_id[0];
        $parcel_target_machine_detail = @$_SESSION['inpostparcels']['parcelTargetAllMachinesDetail'][$parcel_target_machine_id];

        $fields = array(
            'order_id' => $order_id,
            'parcel_detail' => json_encode($parcel_detail),
            'parcel_target_machine_id' => $parcel_target_machine_id,
            'parcel_target_machine_detail' => json_encode($parcel_target_machine_detail),
            'api_source' => inpostparcelsHelper::getCurrentApi()
        );

        $conf =& JFactory::getConfig();
        $db = new ps_DB;
        $db->buildQuery('INSERT', $conf->getValue('config.dbprefix').'order_shipping_inpostparcels', $fields );
        $db->query();

        $dba = new ps_DB;
        $q  = "SELECT COUNT(*) as count FROM #__{vm}_order_user_info WHERE order_id = '". $order_id . "' AND address_type='ST'";
        $dba->query($q);

        $street_address = $parcel_target_machine_detail['address']['street'].' '.$parcel_target_machine_detail['address']['building_number'];
        if(@$parcel_target_machine_detail['address']['flat_number'] != ''){
            $street_address .= '/'.$parcel_target_machine_detail['address']['flat_number'];
        }

        if($dba->f('count') == 0){
            $dbi = new ps_DB;
            $q  = "SELECT * FROM #__{vm}_user_info WHERE user_id = '". $d['user_id'] . "'";
            $dbi->query($q);

            $user_info_st = array();
            $user_info_bt = array();
            while( $dbi->next_record() ) {
                //echo $dbi->f('address_type');
                if($dbi->f('address_type') == 'ST'){
                    $user_info_st = $dbi->loadAssocList('user_id');
                    break;
                }else{
                    $user_info_bt = $dbi->loadAssocList('user_id');
                }
            }

            $user_info = !empty($user_info_st)?$user_info_st:$user_info_bt;
            $user_info = $user_info[$d['user_id']];

            $fields = array(
                'order_id' => $order_id,
                'user_id' => $d['user_id'],
                'address_type' => 'ST',
                'address_type_name' => $user_info['address_type_name'],
                'company' => $user_info['company'],
                'title' => $user_info['title'],
                'last_name' => $user_info['last_name'],
                'first_name' => $user_info['first_name'],
                'middle_name' => $user_info['middle_name'],
                'phone_1' => $user_info['phone_1'],
                'phone_2' => $user_info['phone_2'],
                'fax' => $user_info['fax'],
                'address_1' => $street_address,
                'address_2' => $user_info['address_2'],
                'city' => $parcel_target_machine_detail['address']['city'],
                'state' => $parcel_target_machine_detail['address']['province'],
                'country' => $user_info['country'],
                'zip' => $parcel_target_machine_detail['address']['post_code'],
                'user_email' => $user_info['user_email'],
                'bank_account_nr' => $user_info['bank_account_nr'],
                'bank_name' => $user_info['bank_name'],
                'bank_sort_code' => $user_info['bank_sort_code'],
                'bank_iban' => $user_info['bank_iban'],
                'bank_account_holder' => $user_info['bank_account_holder'],
                'bank_account_type' => $user_info['bank_account_type']
            );

            $db = new ps_DB;
            $db->buildQuery( 'INSERT ', '#__{vm}_order_user_info', $fields);
            $db->query();
        }else{
            $fields = array(
                'address_1' => $street_address,
                'city' => $parcel_target_machine_detail['address']['city'],
                'zip' => $parcel_target_machine_detail['address']['post_code'],
                'state' => $parcel_target_machine_detail['address']['province']
            );
            $db = new ps_DB;
            $db->buildQuery( 'UPDATE ', '#__{vm}_order_user_info', $fields, "WHERE order_id='".$order_id."' AND address_type='ST");
            $db->query();
        }
    }

	function get_rate( &$d ) {
        $shipping_rate_id = $d["shipping_rate_id"];
        $is_arr = explode("|", urldecode(urldecode($shipping_rate_id)) );
        $order_shipping = $is_arr[3];

        return $order_shipping;
	}


	function get_tax_rate() {
        global $d;
  		require_once(CLASSPATH ."shipping/".$this->classname.".cfg.php");
		if( intval(tax)== 0 )
		return( 0 );
		else {
			require_once( CLASSPATH. "ps_tax.php" );
			$tax_rate = ps_tax::get_taxrate_by_id( intval(tax) );
			return $tax_rate;
		}
	}

	function validate( $d ) {
        global $vmLogger, $VM_LANG;
		$shipping_rate_id = $d["shipping_rate_id"];

        if( !preg_match('/inpostparcels/', $shipping_rate_id)) {
            return false;
        }

        if(isset($d['shipping_inpostparcels']['parcel_target_machine_id'])){
            $_SESSION['inpostparcels']['shipping_inpostparcels']['parcel_target_machine_id'] = $d['shipping_inpostparcels']['parcel_target_machine_id'];
        }

        if(@$_SESSION['inpostparcels']['shipping_inpostparcels']['parcel_target_machine_id'] == ''){
            $vmLogger->err($VM_LANG->_('INPOSTPARCELS_VALID_SELECT'));
            return false;
        }

        if(@$_SESSION['inpostparcels']['user_email'] == ''){
            vmError('', $VM_LANG->_('INPOSTPARCELS_VALID_EMAIL'));
            return false;
        }

        if(isset($d['shipping_inpostparcels']['receiver_phone'])){
            $_SESSION['inpostparcels']['shipping_inpostparcels']['receiver_phone'] = $d['shipping_inpostparcels']['receiver_phone'];
        }

        if(!preg_match('/^[1-9]{1}\d{8}$/', $_SESSION['inpostparcels']['shipping_inpostparcels']['receiver_phone'])){
            $vmLogger->err( $VM_LANG->_('INPOSTPARCELS_VALID_MOBILE') ) ;
            return false;
        }

        return true;
	}

    function show_configuration() {
		global $VM_LANG;
			require_once(CLASSPATH ."shipping/".$this->classname.".cfg.php");
        ?>
        <table>
            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_API_URL') ?>:</strong></td>
                <td><input type="text" name="API_URL" class="inputbox" value="<?php echo API_URL != ''?API_URL:$VM_LANG->_('INPOSTPARCELS_CONFIG_DEFAULT_API_URL'); ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_INFO_API_URL')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_API_KEY') ?>:</strong></td>
                <td><input type="text" name="API_KEY" class="inputbox" value="<?php echo API_KEY; ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_INFO_API_KEY')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_PRICE') ?>:</strong></td>
                <td><input type="text" name="PRICE" class="inputbox" value="<?php echo PRICE != ''?PRICE:$VM_LANG->_('INPOSTPARCELS_CONFIG_DEFAULT_PRICE'); ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_INFO_PRICE')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_WEIGHT') ?>:</strong></td>
                <td><input type="text" name="MAX_WEIGHT" class="inputbox" value="<?php echo MAX_WEIGHT != ''?MAX_WEIGHT:$VM_LANG->_('INPOSTPARCELS_CONFIG_DEFAULT_MAX_WEIGHT'); ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_WEIGHT')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_DIMENSION_A') ?>:</strong></td>
                <td><input type="text" name="MAX_DIMENSION_A" class="inputbox" value="<?php echo MAX_DIMENSION_A != ''?MAX_DIMENSION_A:$VM_LANG->_('INPOSTPARCELS_CONFIG_DEFAULT_MAX_DIMENSION_A'); ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_DIMENSION_A')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_DIMENSION_B') ?>:</strong></td>
                <td><input type="text" name="MAX_DIMENSION_B" class="inputbox" value="<?php echo MAX_DIMENSION_B != ''?MAX_DIMENSION_B:$VM_LANG->_('INPOSTPARCELS_CONFIG_DEFAULT_MAX_DIMENSION_B'); ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_DIMENSION_B')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_DIMENSION_C') ?>:</strong></td>
                <td><input type="text" name="MAX_DIMENSION_C" class="inputbox" value="<?php echo MAX_DIMENSION_C != ''?MAX_DIMENSION_C:$VM_LANG->_('INPOSTPARCELS_CONFIG_DEFAULT_MAX_DIMENSION_C'); ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_MAX_DIMENSION_C')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_ALLOWED_COUNTRY') ?>:</strong></td>
                <td><input type="text" name="ALLOWED_COUNTRY" class="inputbox" value="<?php echo ALLOWED_COUNTRY; ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_ALLOWED_COUNTRY')) ?></td>
            </tr>

            <tr>
                <td><strong><?php echo $VM_LANG->_('INPOSTPARCELS_CONFIG_SHOP_CITIES') ?>:</strong></td>
                <td><input type="text" name="SHOP_CITIES" class="inputbox" value="<?php echo SHOP_CITIES; ?>" /></td>
                <td><?php echo mm_ToolTip($VM_LANG->_('INPOSTPARCELS_CONFIG_SHOP_CITIES')) ?></td>
            </tr>

	    </table>
        <?php
        return true;
	}

	function configfile_writeable() {
		return is_writeable( CLASSPATH."shipping/".$this->classname.".cfg.php" );
	}

	function write_configuration( &$d ) {
	    global $vmLogger, $VM_LANG;

		$my_config_array = array(
            "API_URL" => $d['API_URL'],
		    "API_KEY" => $d['API_KEY'],
            "PRICE" => $d['PRICE'],
            "MAX_WEIGHT" => $d['MAX_WEIGHT'],
            "MAX_DIMENSION_A" => $d['MAX_DIMENSION_A'],
            "MAX_DIMENSION_B" => $d['MAX_DIMENSION_B'],
            "MAX_DIMENSION_C" => $d['MAX_DIMENSION_C'],
            "ALLOWED_COUNTRY" => $d['ALLOWED_COUNTRY'],
            "SHOP_CITIES" => $d['SHOP_CITIES']
		);
		$config = "<?php\n";
		$config .= "if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); \n\n";
		foreach( $my_config_array as $key => $value ) {
            $value = str_replace("'", "\'", $value );
            $config .= "define ('$key', '$value');\n";
		}
		$config .= "?>";
		if ($fp = fopen(CLASSPATH ."shipping/".$this->classname.".cfg.php", "w")) {
			fputs($fp, $config, strlen($config));
			fclose ($fp);
			return true;
		}
		else {
			$vmLogger->err( $VM_LANG->_('INPOSTPARCELS_MSG_ERROR_WRITING_CONFIG_FILE') );
			return false;
		}
	}

}
?>