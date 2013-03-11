<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

require_once(CLASSPATH ."shipping/easypack24/helpers/easypack24Helper.php");
require_once(CLASSPATH ."shipping/easypack24.cfg.php");

class vm_ps_easypack24 {


    function mass_stickers(&$d) {
        global $vmLogger;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $parcelsIds = vmGet($d,"id");

        $countSticker = 0;
        $countNonSticker = 0;
        $pdf = null;
        $parcelsCode = array();

        foreach ($parcelsIds as $key => $id) {
            $dbu = new ps_DB;
            $q  = "SELECT * FROM ".$db_prefix."order_shipping_easypack24 WHERE id = '". $id . "'";
            $dbu->query($q);

            if($dbu->f("parcel_id") != ''){
                $parcelsCode[$id] = $dbu->f("parcel_id");
            }else{
                continue;
            }
        }

        if(empty($parcelsCode)){
            $vmLogger->err('Parcel ID is empty');
        }else{

            $parcelApiPay = easypack24Helper::connectEasypack24(array(
                'url' => API_URL.'parcels/'.implode(';', $parcelsCode).'/pay',
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

            $parcelApi = easypack24Helper::connectEasypack24(array(
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
                $db = new ps_DB;
                $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_easypack24', $fields,  "WHERE parcel_id='".$parcelId."'");
                $db->query();
                $countSticker++;
            }
            $pdf = base64_decode(@$parcelApi['result']);
        }

        if ($countNonSticker) {
            if ($countNonSticker) {
                $vmLogger->err($countNonSticker.' sticker(s) cannot be generated');
            } else {
                $vmLogger->err('The sticker(s) cannot be generated');
            }
        }
        if ($countSticker) {
            $vmLogger->info($countSticker.' sticker(s) have been generated.');
        }

        if(!is_null($pdf)){
            header('Content-type', 'application/pdf');
            header('Content-Disposition: attachment; filename=stickers_'.date('Y-m-d_H-i-s').'.pdf');
            print_r($pdf);
        }
    }

    function mass_refresh_status(&$d) {
        global $vmLogger;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $parcelsIds = vmGet($d,"id");

        $countRefreshStatus = 0;
        $countNonRefreshStatus = 0;

        $parcelsCode = array();
        foreach ($parcelsIds as $key => $id) {
            $dbu = new ps_DB;
            $q  = "SELECT * FROM ".$db_prefix."order_shipping_easypack24 WHERE id = '". $id . "'";
            $dbu->query($q);

            if($dbu->f("parcel_id") != ''){
                $parcelsCode[$id] = $dbu->f("parcel_id");
            }else{
                continue;
            }
        }

        if(empty($parcelsCode)){
            $vmLogger->err('Parcel ID is empty');
        }else{
            $parcelApi = easypack24Helper::connectEasypack24(array(
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
                $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_easypack24', $fields,  "WHERE parcel_id='".@$parcel->id."'");
                $db->query();
                $countRefreshStatus++;
            }
        }

        if ($countNonRefreshStatus) {
            if ($countNonRefreshStatus) {
                $vmLogger->err($countNonRefreshStatus.' parcel status cannot be refresh');
            } else {
                $vmLogger->err($countNonRefreshStatus.' The parcel status cannot be refresh');
            }
        }
        if ($countRefreshStatus) {
            $vmLogger->info($countRefreshStatus.' parcel status have been refresh.');
        }
    }

    function mass_cancel(&$d) {
        global $vmLogger;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $parcelsIds = vmGet($d,"id");

        $countCancel = 0;
        $countNonCancel = 0;

        $parcelsCode = array();
        foreach ($parcelsIds as $key => $id) {
            $dbu = new ps_DB;
            $q  = "SELECT * FROM ".$db_prefix."order_shipping_easypack24 WHERE id = '". $id . "'";
            $dbu->query($q);

            if($dbu->f("parcel_id") != ''){
                $parcelsCode[$id] = $dbu->f("parcel_id");
            }else{
                continue;
            }
        }

        if(empty($parcelsCode)){
            $vmLogger->err('Parcel ID is empty');
        }else{
            foreach($parcelsCode as $id => $parcelId){
                $parcelApi = easypack24Helper::connectEasypack24(array(
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
                        $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_easypack24', $fields,  "WHERE parcel_id='".@$parcel->id."'");
                        $db->query();
                        $countCancel++;
                    }
                }
            }
        }

        if ($countNonCancel) {
            if ($countNonCancel) {
                $vmLogger->err($countNonCancel.' parcel status cannot be cancel');
            } else {
                $vmLogger->err('The parcel status cannot be cancel');
            }
        }
        if ($countCancel) {
            $vmLogger->info($countNonCancel.' parcel status have been cancel.');
        }
    }

    function update(&$d) {
        global $vmLogger;
        $conf =& JFactory::getConfig();
        $db_prefix = $conf->getValue('config.dbprefix');
        $id = vmGet($d,"id");

        try {
            $postData = $d;
            $db = new ps_DB;
            $q = "SELECT * FROM ".$db_prefix."order_shipping_easypack24 WHERE id='$id'";
            $db->query($q);
            $db->next_record();

            $parcelTargetMachineDetailDb = json_decode($db->f("parcel_target_machine_detail"));
            $parcelDetailDb = json_decode($db->f("parcel_detail"));

            // update Inpost parcel
            $params = array(
                'url' => API_URL.'parcels',
                'token' => API_KEY,
                'methodType' => 'PUT',
                'params' => array(
                    'description' => !isset($postData['parcel_description']) || $postData['parcel_description'] == @$parcelDetailDb->description?null:$postData['parcel_description'],
                    'id' => $postData['parcel_id'],
                    'size' => !isset($postData['parcel_size']) || $postData['parcel_size'] == @$parcelDetailDb->size?null:$postData['parcel_size'],
                    'status' => !isset($postData['parcel_status']) || $postData['parcel_status'] == $db->f("parcel_status")?null:$postData['parcel_status'],
                    //'target_machine' => !isset($postData['parcel_target_machine_id']) || $postData['parcel_target_machine_id'] == $db->f("parcel_target_machine_id")?null:$postData['parcel_target_machine_id']
                )
            );
            $parcelApi = easypack24Helper::connectEasypack24($params);

            if(@$parcelApi['info']['http_code'] != '204'){
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
                return;
            }else{
                $fields = array(
                    'parcel_status' => isset($postData['parcel_status'])?$postData['parcel_status']:$db->f("parcel_status"),
                    'parcel_detail' => json_encode(array(
                        'description' => $postData['parcel_description'],
                        'receiver' => array(
                            'email' => $parcelDetailDb->receiver->email,
                            'phone' => $parcelDetailDb->receiver->phone
                        ),
                        'size' => isset($postData['parcel_size'])?$postData['parcel_size']:@$parcelDetailDb->size,
                        'tmp_id' => $parcelDetailDb->tmp_id,
                    ))
                );
                $db = new ps_DB;
                $db->buildQuery( 'UPDATE ', $db_prefix.'order_shipping_easypack24', $fields,  "WHERE id='".$id."'");
                $db->query();
            }
            $vmLogger->info('Parcel modified');
            return;
        } catch (Exception $e) {
            $vmLogger->err($e->getMessage());
            //Mage::getSingleton('adminhtml/session')->setEasypackData($);
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
	class ps_easypack24 extends vm_ps_easypack24 {}
}


$ps_easypack24 = new ps_easypack24;
?>