<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 



global $VM_LANG;

$langvars = array (
	'TEST_LANG' => 'INPOSTPARCELS EN',
    'INPOSTPARCELS_LINK' => "InPost Parcels",
    'INPOSTPARCELS_NAME' => "InPost Parcel Lockers 24/7",
    'INPOSTPARCELS_MOB_PREFIX' => "(07)",
    'INPOSTPARCELS_SHIPPING_NAME' => "Shipping name",
    'INPOSTPARCELS_MACHINE' => "Target machine",
    'INPOSTPARCELS_MOBILE' => "Mobile",
    'INPOSTPARCELS_WEIGHT' => "Weight",
    'INPOSTPARCELS_COST' => "Cost",
    'INPOSTPARCELS_PACKAGE_FEE' => "Package Fee",
    'INPOSTPARCELS_TAX' => "Tax",
    'INPOSTPARCELS_DEFAULT_SELECT' => "no terminals in your city",
    'INPOSTPARCELS_SHOW_TERMINALS' => "Show terminals in other cities",
    'INPOSTPARCELS_ORDER' => "Order number:",
    'INPOSTPARCELS_MAX_WEIGHT_IS' => "Max weight is",
    'INPOSTPARCELS_MAX_DIMENSION_IS' => "Max dimension is",
    'INPOSTPARCELS_ACTUAL' => "Actual",
    'INPOSTPARCELS_SELECT_MACHINE' => "Select machine",
    'INPOSTPARCELS_SHOW_TERMINAL' => "Show terminals in other cities",
    'INPOSTPARCELS_MOB_EXAMPLE' => "Mobile e.g. 523045856 *",
    'INPOSTPARCELS_MAP' => "Map",

    'INPOSTPARCELS_VALID_SELECT' => "Select target machine",
    'INPOSTPARCELS_VALID_MOBILE' => "Mobile is invalid. Correct is e.g. 111222333. /^[1-9]{1}\d{8}$/",
    'INPOSTPARCELS_VALID_EMAIL' => "Please correct email in shipping address or user profile",

    'INPOSTPARCELS_MSG_STICKER_1' => " sticker(s) cannot be generated",
    'INPOSTPARCELS_MSG_STICKER_2' => "The sticker(s) cannot be generated",
    'INPOSTPARCELS_MSG_STICKER_3' => " sticker(s) have been generated",
    'INPOSTPARCELS_MSG_PARCEL_1' => " parcel status cannot be refresh",
    'INPOSTPARCELS_MSG_PARCEL_2' => " The parcel status cannot be refresh",
    'INPOSTPARCELS_MSG_PARCEL_3' => "parcel status have been refresh",
    'INPOSTPARCELS_MSG_PARCEL_4' => "parcel status cannot be cancel",
    'INPOSTPARCELS_MSG_PARCEL_5' => "The parcel status cannot be cancel",
    'INPOSTPARCELS_MSG_PARCEL_6' => " parcel status have been cancel",
    'INPOSTPARCELS_MSG_PARCEL_7' => "Parcel ID is empty",
    'INPOSTPARCELS_MSG_PARCEL_MODIFIED' => "Parcel modified",
    'INPOSTPARCELS_MSG_ERROR_WRITING_CONFIG_FILE' => "Error writing to configuration file",

    'INPOSTPARCELS_VIEW_PARCEL_LIST' => "Parcel list",
    'INPOSTPARCELS_VIEW_ORDER_ID' => "Order ID",
    'INPOSTPARCELS_VIEW_PARCEL_ID' => "Parcel ID",
    'INPOSTPARCELS_VIEW_STATUS' => "Status",
    'INPOSTPARCELS_VIEW_MACHINE_ID' => "Machine ID",
    'INPOSTPARCELS_VIEW_STICKER_CREATION_DATE' => "Sticker creation date",
    'INPOSTPARCELS_VIEW_CREATION_DATE' => "Creation date",
    'INPOSTPARCELS_VIEW_EDIT_PARCEL' => "Edit parcel",
    'INPOSTPARCELS_VIEW_EDIT_ORDER' => "Edit order",
    'INPOSTPARCELS_VIEW_PARCEL_STATUS' => "Parcel status",
    'INPOSTPARCELS_VIEW_BUTTON_1' => "Parcel stickers in pdf format",
    'INPOSTPARCELS_VIEW_BUTTON_2' => "Parcel refresh status",
    'INPOSTPARCELS_VIEW_BUTTON_3' => "Parcel cancel",
    'INPOSTPARCELS_VIEW_BUTTON_4' => "Save",
    'INPOSTPARCELS_VIEW_ERR_1' => "Item does not exist",
    'INPOSTPARCELS_VIEW_DEFAULT_SELECT'=> 'no terminals in your city',
    'INPOSTPARCELS_VIEW_SHOW_TERMINAL'=>'Show terminals in other cities ',
    'INPOSTPARCELS_VIEW_MOB_EXAMPLE' => "Mobile e.g. 523045856 *",
    'INPOSTPARCELS_VIEW_MOB_TITLE' => "mobile /^[1-9]{1}\d{8}$/",
    'INPOSTPARCELS_VIEW_MAP' => "Map",
    'INPOSTPARCELS_VIEW_SELECT_MACHINE' => "Select machine",
    'INPOSTPARCELS_VIEW_SIZE_A' => "A",
    'INPOSTPARCELS_VIEW_SIZE_B' => "B",
    'INPOSTPARCELS_VIEW_SIZE_C' => "C",
    'INPOSTPARCELS_VIEW_ACTIONS' => "Actions",
    'INPOSTPARCELS_VIEW_CREATE_PARCEL' => "Create parcel",
    'INPOSTPARCELS_VIEW_SELECT_INSURANCE' => 'Select insurance',
    'INPOSTPARCELS_VIEW_COD_AMOUNT' => 'Cod amount',
    'INPOSTPARCELS_VIEW_DESCRIPTION' => 'Description',
    'INPOSTPARCELS_VIEW_INSURANCE_AMOUNT' => 'Insurance amount',
    'INPOSTPARCELS_VIEW_RECEIVER_PHONE' => 'Receiver phone',
    'INPOSTPARCELS_VIEW_RECEIVER_EMAIL' => 'Receiver email',
    'INPOSTPARCELS_VIEW_SIZE' => 'Size',
    'INPOSTPARCELS_VIEW_STATUS' => 'Status',
    'INPOSTPARCELS_VIEW_SOURCE_MACHINE' => 'Source machine',
    'INPOSTPARCELS_VIEW_TMP_ID' => 'Tmp id',
    'INPOSTPARCELS_VIEW_TARGET_MACHINE' => 'Target machine',

    'INPOSTPARCELS_CONFIG_INFO_API_URL' => "Api url from inpostparcels.",
    'INPOSTPARCELS_CONFIG_INFO_API_KEY' => "Api key from inpostparcels.",
    'INPOSTPARCELS_CONFIG_INFO_PRICE' => "Sending price.",
    'INPOSTPARCELS_CONFIG_INFO_MAX_WEIGHT' => "Total weight of items in checkout.",
    'INPOSTPARCELS_CONFIG_INFO_MAX_DIMENSION_A' => "Max dimension of items in checkout.",
    'INPOSTPARCELS_CONFIG_INFO_MAX_DIMENSION_B' => "Max dimension of items in checkout.",
    'INPOSTPARCELS_CONFIG_INFO_MAX_DIMENSION_C' => "Max dimension of items in checkout.",
    'INPOSTPARCELS_CONFIG_INFO_ALLOWED_COUNTRY' => "Allowed country.",
    'INPOSTPARCELS_CONFIG_INFO_SHOP_CITIES' => "Shop cities.",

    'INPOSTPARCELS_CONFIG_API_URL' => "Api url",
    'INPOSTPARCELS_CONFIG_API_KEY' => "Api key",
    'INPOSTPARCELS_CONFIG_PRICE' => "Price",
    'INPOSTPARCELS_CONFIG_MAX_WEIGHT' => "Max weight",
    'INPOSTPARCELS_CONFIG_MAX_DIMENSION_A' => "Max dimension a",
    'INPOSTPARCELS_CONFIG_MAX_DIMENSION_B' => "Max dimension b",
    'INPOSTPARCELS_CONFIG_MAX_DIMENSION_C' => "Max dimension C",
    'INPOSTPARCELS_CONFIG_ALLOWED_COUNTRY' => "Allowed country",
    'INPOSTPARCELS_CONFIG_SHOP_CITIES' => "Shop cities",

    'INPOSTPARCELS_CONFIG_DEFAULT_API_URL' => "http://api-uk.easypack24.net/",
    'INPOSTPARCELS_CONFIG_DEFAULT_INFO_PRICE' => "14",
    'INPOSTPARCELS_CONFIG_DEFAULT_MAX_WEIGHT' => "25",
    'INPOSTPARCELS_CONFIG_DEFAULT_MAX_DIMENSION_A' => "8x38x64",
    'INPOSTPARCELS_CONFIG_DEFAULT_MAX_DIMENSION_B' => "19x38x64",
    'INPOSTPARCELS_CONFIG_DEFAULT_MAX_DIMENSION_C' => "41x38x64",


); $VM_LANG->initModule( 'inpostparcels', $langvars );

?>