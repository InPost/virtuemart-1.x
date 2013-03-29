<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

mm_showMyFileName( __FILE__ );
global $page, $ps_easypack_status;
$conf =& JFactory::getConfig();
$db_prefix = $conf->getValue('config.dbprefix');
$show = vmGet( $_REQUEST, "show", "" );

require_once( CLASSPATH . "pageNavigation.class.php" );
require_once( CLASSPATH . "htmlTools.class.php" );
require_once(CLASSPATH ."shipping/easypack24/helpers/easypack24Helper.php");


$list  = "SELECT * FROM ".$db_prefix."order_shipping_easypack24 WHERE ";
$count = "SELECT count(*) as num_rows FROM ".$db_prefix."order_shipping_easypack24 WHERE ";
$q = "(id > 0) AND ";

if (!empty($keyword)) {
    $q .= "(parcel_id LIKE '%$keyword%' ";
    $q .= "OR parcel_target_machine_id LIKE '%$keyword%' ";
    $q .= "OR parcel_detail LIKE '%$keyword%' ";
    $q .= "OR parcel_target_machine_detail LIKE '%$keyword%' ";
    $q .= ") AND ";
}
if (!empty($show)) {
    $q .= "parcel_status = '$show' AND ";
}

$q .= "order_id > 0 ";
$q .= "ORDER BY order_id DESC ";
$list .= $q . " LIMIT $limitstart, " . $limit;
$count .= $q;

//echo $list;

$db->query($count);
$db->next_record();
$num_rows = $db->f("num_rows");

// Create the Page Navigation
$pageNav = new vmPageNav( $num_rows, $limitstart, $limit );

// Create the List Object with page navigation
$listObj = new listFactory( $pageNav );

// print out the search field and a list heading
$listObj->writeSearchHeader('Parcel list', VM_THEMEURL."images/administration/dashboard/inpost.jpeg", $modulename, "easypack24_list");


?>
<div align="center">
    <?php
    foreach (easypack24Helper::getParcelStatus() as $parcel_status) { ?>
        <a href="<?php $sess->purl($_SERVER['PHP_SELF']."?page=$modulename.easypack24_list&show=".$parcel_status) ?>">
            <b><?php echo $parcel_status?></b></a>
        |
        <?php
    }
    ?>
    <a href="<?php $sess->purl($_SERVER['PHP_SELF']."?page=$modulename.easypack24_list&show=")?>"><b>
        <?php echo $VM_LANG->_('PHPSHOP_ALL') ?></b></a>
</div>
<br />
<?php


// start the list table
$listObj->startTable();

// these are the columns in the table
$columns = Array(  "#" => "width=\"20\"",
    "<input type=\"checkbox\" name=\"toggle\" value=\"\" onclick=\"checkAll(".$num_rows.")\" />" => "width=\"20\"",
    'ID' => '',
    'Order ID' => '',
    'Parcel ID' => '',
    'Status' => '',
    'Machine ID' => '',
    'Sticker creation date' => '',
    'Creation date' => ''
);
$listObj->writeTableHeader( $columns );

$db->query($list);
$i = 0;
while ($db->next_record()) {

    $listObj->newRow();

    // The row number
    $listObj->addCell( $pageNav->rowNumber( $i ) );

    // The Checkbox
    $listObj->addCell( vmCommonHTML::idBox( $i, $db->f("id"), false, "id" ) );

    $listObj->addCell( $db->f("id"));

    $url = $_SERVER['PHP_SELF']."?page=order.order_print&limitstart=$limitstart&keyword=".urlencode($keyword)."&order_id=". $db->f("order_id");
    $tmp_cell = "<a href=\"" . $sess->url($url) . "\">".sprintf("%08d", $db->f("order_id"))."</a><br />";
    $listObj->addCell( $tmp_cell );

    $url = $_SERVER['PHP_SELF'] . "?page=$modulename.easypack24_form&limitstart=$limitstart&keyword=".urlencode($keyword)."&id=". $db->f("id");
    $tmp_cell = "<a href=\"" . $sess->url($url) . "\">". $db->f("parcel_id")."</a>";
    $listObj->addCell( $tmp_cell );

    $listObj->addCell( $db->f("parcel_status"));

    $listObj->addCell( $db->f("parcel_target_machine_id"));

    $listObj->addCell( $db->f("sticker_creation_date")!=''?$db->f("sticker_creation_date"):' ');

    $listObj->addCell( $db->f("creation_date"));

    $i++;
}
$listObj->writeTable();

$listObj->endTable();

$listObj->writeFooter( $keyword );

?>
<!--</form>-->