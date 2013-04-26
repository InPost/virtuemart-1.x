<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

require_once(CLASSPATH ."shipping/inpostparcels/helpers/inpostparcelsHelper.php");
require_once(CLASSPATH ."shipping/inpostparcels.cfg.php");

class vm_ps_inpostparcels {


    function mass_stickers(&$d) {
        global $vmLogger, $VM_LANG;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $parcelsIds = vmGet($d,"id");
        inpostparcelsHelper::setLang();

        $countSticker = 0;
        $countNonSticker = 0;
        $pdf = null;
        $parcelsCode = array();
        $parcelsToPay = array();

        foreach ($parcelsIds as $key => $id) {
            $dbu = new ps_DB;
            $q  = "SELECT * FROM ".$db_prefix."order_shipping_inpostparcels WHERE id = '". $id . "'";
            $dbu->query($q);

            if($dbu->f("parcel_id") != ''){
                $parcelsCode[$id] = $dbu->f("parcel_id");
                if($dbu->f("sticker_creation_date") == ''){
                    $parcelsToPay[$id] = $dbu->f("parcel_id");
                }
            }else{
                continue;
            }
        }

        if(empty($parcelsCode)){
            $vmLogger->err($VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_7'));
        }else{
            if(!empty($parcelsToPay)){
                $parcelApiPay = inpostparcelsHelper::connectInpostparcels(array(
                    'url' => API_URL.'parcels/'.implode(';', $parcelsToPay).'/pay',
                    'token' => API_KEY,
                    'methodType' => 'POST',
                    'params' => array(
                    )
                ));

                if(@$parcelApiPay['info']['http_code'] != '204'){
                    $countNonSticker = count($parcelsIds);
                    if(!empty($parcelApiPay['result'])){
                        foreach(@$parcelApiPay['result'] as $key => $error){
                            $vmLogger->err('Parcel '.$key.' '.$error);
                        }
                    }
                    #return;
                }
            }

            $parcelApi = inpostparcelsHelper::connectInpostparcels(array(
                'url' => API_URL.'stickers/'.implode(';', $parcelsCode),
                'token' => API_KEY,
                'methodType' => 'GET',
                'params' => array(
                    'format' => 'Pdf',
                    'type' => 'normal'
                )
            ));
        }

        if(@$parcelApi['info']['http_code'] != '200'){
            $countNonSticker = count($parcelsIds);
            if(!empty($parcelApi['result'])){
                foreach(@$parcelApi['result'] as $key => $error){
                    $vmLogger->err('Parcel '.$key.' '.$error);
                }
            }
        }else{
            foreach ($parcelsIds as $parcelId) {
                $fields = array(
                    'parcel_status' => 'Prepared',
                    'sticker_creation_date' => date('Y-m-d H:i:s')
                );
                if(isset($parcelsToPay[$parcelId])){
                    $db = new ps_DB;
                    $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_inpostparcels', $fields,  "WHERE id='".$parcelId."'");
                    $db->query();
                }
                $countSticker++;
            }
            $pdf = base64_decode(@$parcelApi['result']);
        }

        if ($countNonSticker) {
            if ($countNonSticker) {
                $vmLogger->err($countNonSticker.$VM_LANG->_('INPOSTPARCELS_MSG_STICKER_1'));
            } else {
                $vmLogger->err($VM_LANG->_('INPOSTPARCELS_MSG_STICKER_2'));
            }
        }
        if ($countSticker) {
            $vmLogger->info($countSticker.$VM_LANG->_('INPOSTPARCELS_MSG_STICKER_3'));
        }

        if(!is_null($pdf)){
            header('Content-type', 'application/pdf');
            header('Content-Disposition: attachment; filename=stickers_'.date('Y-m-d_H-i-s').'.pdf');
            print_r($pdf);
        }
    }

    function mass_refresh_status(&$d) {
        global $vmLogger, $VM_LANG;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $parcelsIds = vmGet($d,"id");
        inpostparcelsHelper::setLang();

        $countRefreshStatus = 0;
        $countNonRefreshStatus = 0;

        $parcelsCode = array();
        foreach ($parcelsIds as $key => $id) {
            $dbu = new ps_DB;
            $q  = "SELECT * FROM ".$db_prefix."order_shipping_inpostparcels WHERE id = '". $id . "'";
            $dbu->query($q);

            if($dbu->f("parcel_id") != ''){
                $parcelsCode[$id] = $dbu->f("parcel_id");
            }else{
                continue;
            }
        }

        if(empty($parcelsCode)){
            $vmLogger->err($VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_7'));
        }else{
            $parcelApi = inpostparcelsHelper::connectInpostparcels(array(
                'url' => API_URL.'parcels/'.implode(';', $parcelsCode),
                'token' => API_KEY,
                'methodType' => 'GET',
                'params' => array()
            ));
        }

        if(@$parcelApi['info']['http_code'] != '200'){
            $countNonRefreshStatus = count($parcelsIds);
            if(!empty($parcelApi['result'])){
                foreach(@$parcelApi['result'] as $key => $error){
                    $vmLogger->err('Parcel '.$key.' '.$error);
                }
            }
        }else{
            if(!is_array(@$parcelApi['result'])){
                @$parcelApi['result'] = array(@$parcelApi['result']);
            }
            foreach (@$parcelApi['result'] as $parcel) {
                $fields = array(
                    'parcel_status' => @$parcel->status
                );
                $db = new ps_DB;
                $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_inpostparcels', $fields,  "WHERE parcel_id='".@$parcel->id."'");
                $db->query();
                $countRefreshStatus++;
            }
        }

        if ($countNonRefreshStatus) {
            if ($countNonRefreshStatus) {
                $vmLogger->err($countNonRefreshStatus.$VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_1'));
            } else {
                $vmLogger->err($countNonRefreshStatus.$VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_2'));
            }
        }
        if ($countRefreshStatus) {
            $vmLogger->info($countRefreshStatus.$VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_3'));
        }
    }

    function mass_cancel(&$d) {
        global $vmLogger, $VM_LANG;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $parcelsIds = vmGet($d,"id");
        inpostparcelsHelper::setLang();

        $countCancel = 0;
        $countNonCancel = 0;

        $parcelsCode = array();
        foreach ($parcelsIds as $key => $id) {
            $dbu = new ps_DB;
            $q  = "SELECT * FROM ".$db_prefix."order_shipping_inpostparcels WHERE id = '". $id . "'";
            $dbu->query($q);

            if($dbu->f("parcel_id") != ''){
                $parcelsCode[$id] = $dbu->f("parcel_id");
            }else{
                continue;
            }
        }

        if(empty($parcelsCode)){
            $vmLogger->err($VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_7'));
        }else{
            foreach($parcelsCode as $id => $parcelId){
                $parcelApi = inpostparcelsHelper::connectInpostparcels(array(
                    'url' => API_URL.'parcels',
                    'token' => API_KEY,
                    'methodType' => 'PUT',
                    'params' => array(
                        'id' => $parcelId,
                        'status' => 'cancelled'
                    )
                ));

                if(@$parcelApi['info']['http_code'] != '204'){
                    $countNonCancel = count($parcelsIds);
                    if(!empty($parcelApi['result'])){
                        foreach(@$parcelApi['result'] as $key => $error){
                            if(is_array($error)){
                                foreach($error as $subKey => $subError){
                                    $vmLogger->err('Parcel '.$parcelId.' '.$subError);
                                }
                            }else{
                                $vmLogger->err('Parcel '.$parcelId.' '.$error);
                            }
                        }
                    }
                }else{
                    foreach (@$parcelApi['result'] as $parcel) {
                        $fields = array(
                            'parcel_status' => @$parcel->status
                        );
                        $db = new ps_DB;
                        $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_inpostparcels', $fields,  "WHERE parcel_id='".@$parcel->id."'");
                        $db->query();
                        $countCancel++;
                    }
                }
            }
        }

        if ($countNonCancel) {
            if ($countNonCancel) {
                $vmLogger->err($countNonCancel.$VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_4'));
            } else {
                $vmLogger->err($VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_5'));
            }
        }
        if ($countCancel) {
            $vmLogger->info($countNonCancel.$VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_6'));
        }
    }

    function update(&$d) {
        global $vmLogger, $VM_LANG;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $id = vmGet($d,"id");
        inpostparcelsHelper::setLang();

        try {
            $postData = $d;
            $db = new ps_DB;
            $q = "SELECT * FROM ".$db_prefix."order_shipping_inpostparcels WHERE id='$id'";
            $db->query($q);
            $db->next_record();

            $parcelTargetMachineDetailDb = json_decode($db->f("parcel_target_machine_detail"));
            $parcelDetailDb = json_decode($db->f("parcel_detail"));

            if($db->f('parcel_id') != ''){
                // update Inpost parcel
                $params = array(
                    'url' => API_URL.'parcels',
                    'token' => API_KEY,
                    'methodType' => 'PUT',
                    'params' => array(
                        'description' => !isset($postData['parcel_description']) || $postData['parcel_description'] == @$parcelDetailDb->description?null:$postData['parcel_description'],
                        'id' => $postData['parcel_id'],
                        'size' => !isset($postData['parcel_size']) || $postData['parcel_size'] == @$parcelDetailDb->size?null:$postData['parcel_size'],
                        'status' => !isset($postData['parcel_status']) || $postData['parcel_status'] == $db->f('parcel_status')?null:$postData['parcel_status'],
                        //'target_machine' => !isset($postData['parcel_target_machine_id']) || $postData['parcel_target_machine_id'] == $db->f('parcel_target_machine_id')?null:$postData['parcel_target_machine_id']
                    )
                );
            }else{
                // create Inpost parcel e.g.
                $params = array(
                    'url' => API_URL.'parcels',
                    'token' => API_KEY,
                    'methodType' => 'POST',
                    'params' => array(
                        'description' => @$postData['parcel_description'],
                        'description2' => 'virtuemart-1.x-'.inpostparcelsHelper::getVersion(),
                        'receiver' => array(
                            'phone' => @$postData['parcel_receiver_phone'],
                            'email' => @$postData['parcel_receiver_email']
                        ),
                        'size' => @$postData['parcel_size'],
                        'tmp_id' => @$postData['parcel_tmp_id'],
                        'target_machine' => @$postData['parcel_target_machine_id']
                    )
                );

                switch($db->f('api_source')){
                    case 'PL':
                        $insurance_amount = $_SESSION['inpostparcels']['parcelInsurancesAmount'];
                        $params['params']['cod_amount'] = @$postData['parcel_cod_amount'];
                        if(@$postData['parcel_insurance_amount'] != ''){
                            $params['params']['insurance_amount'] = @$postData['parcel_insurance_amount'];
                        }
                        $params['params']['source_machine'] = @$postData['parcel_source_machine_id'];
                        break;
                }
            }

            $parcelApi = inpostparcelsHelper::connectInpostparcels($params);

            if(@$parcelApi['info']['http_code'] != '204' && @$parcelApi['info']['http_code'] != '201'){
                if(!empty($parcelApi['result'])){
                    foreach(@$parcelApi['result'] as $key => $error){
                        if(is_array($error)){
                            foreach($error as $subKey => $subError){
                                $vmLogger->err('Parcel '.$key.' '.$postData['parcel_id'].' '.$subError);
                            }
                        }else{
                            $vmLogger->err('Parcel '.$key.' '.$error);
                        }
                    }
                }
                return false;
            }else{
                if($db->f('parcel_id') != ''){
                    $parcelDetail = $parcelDetailDb;
                    $parcelDetail->description = $postData['parcel_description'];
                    $parcelDetail->size = $postData['parcel_size'];
                    $parcelDetail->status = $postData['parcel_status'];

                    $fields = array(
                        'parcel_status' => isset($postData['parcel_status'])?$postData['parcel_status']:$db->f('parcel_status'),
                        'parcel_detail' => json_encode($parcelDetail),
                        'variables' => json_encode(array())
                    );

                    $dbi = new ps_DB;
                    $dbi->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_inpostparcels', $fields,  "WHERE id='".$id."'");
                    $dbi->query();
                }else{
//                    $parcelApi = inpostparcelsHelper::connectInpostparcels(
//                        array(
//                            'url' => $parcelApi['info']['redirect_url'],
//                            'token' => constant('MODULE_SHIPPING_INPOSTPARCELS_API_KEY'),
//                            'ds' => '&',
//                            'methodType' => 'GET',
//                            'params' => array(
//                            )
//                        )
//                    );

                    $fields = array(
                        'parcel_id' => $parcelApi['result']->id,
                        'parcel_status' => 'Created',
                        'parcel_detail' => json_encode($params['params']),
                        'parcel_target_machine_id' => isset($postData['parcel_target_machine_id'])?$postData['parcel_target_machine_id']:$db->f('parcel_target_machine_id'),
                        'parcel_target_machine_detail' => $db->f('parcel_target_machine_detail'),
                        'variables' => json_encode(array())
                    );

                    if($db->f('parcel_target_machine_id') != $postData['parcel_target_machine_id']){
                        $parcelApi = inpostparcelsHelper::connectInpostparcels(
                            array(
                                'url' => API_URL.'machines/'.$postData['parcel_target_machine_id'],
                                'token' => API_KEY,
                                'methodType' => 'GET',
                                'params' => array(
                                )
                            )
                        );

                        $fields['parcel_target_machine_detail'] = json_encode($parcelApi['result']);
                    }
                    $dbi = new ps_DB;
                    $dbi->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_inpostparcels', $fields,  "WHERE id='".$id."'");
                    $dbi->query();
                }
            }
            $vmLogger->info($VM_LANG->_('INPOSTPARCELS_MSG_PARCEL_MODIFIED'));
            return;
        } catch (Exception $e) {
            $vmLogger->err($e->getMessage());
            return;
        }
    }
}

// Check if there is an extended class in the Themes and if it is allowed to use them
// If the class is called outside Virtuemart, we have to make sure to load the settings
// Thomas Kahl - Feb. 2009
if (!defined('VM_ALLOW_EXTENDED_CLASSES') && file_exists(dirname(__FILE__).'/../virtuemart.cfg.php')) {
	include_once(dirname(__FILE__).'/../virtuemart.cfg.php');
}
// If settings are loaded, extended Classes are allowed and the class exisits...
if (defined('VM_ALLOW_EXTENDED_CLASSES') && defined('VM_THEMEPATH') && VM_ALLOW_EXTENDED_CLASSES && file_exists(VM_THEMEPATH.'user_class/'.basename(__FILE__))) {
	// Load the theme-user_class as extended
	include_once(VM_THEMEPATH.'user_class/'.basename(__FILE__));
} else {
	// Otherwise we have to use the original classname to extend the core-class
	class ps_inpostparcels extends vm_ps_inpostparcels {}
}


$ps_inpostparcels = new ps_inpostparcels;
?>