CREATE TABLE IF NOT EXISTS `#YOUR_PREFIX_order_shipping_easypack24` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`order_id` int(11) NOT NULL,
	`parcel_id` varchar(200) NOT NULL default '',
	`parcel_status` varchar(200) NOT NULL default '',
	`parcel_detail` text NOT NULL default '',
	`parcel_target_machine_id` varchar(200) NOT NULL default '',
	`parcel_target_machine_detail` text NOT NULL default '',
    `sticker_creation_date` TIMESTAMP NULL DEFAULT NULL,
    `creation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `#YOUR_PREFIX_vm_function` (`function_id`, `module_id`, `function_name`, `function_class`, `function_method`, `function_description`, `function_perms`) VALUES
(195, 12844, 'easypack24MassStickers', 'ps_easypack24', 'mass_stickers', '', 'admin,storeadmin'),
(196, 12844, 'easypack24Update', 'ps_easypack24', 'update', '', 'admin,storeadmin'),
(197, 12844, 'easypack24MassRefreshStatus', 'ps_easypack24', 'mass_refresh_status', '', 'storeadmin,admin'),
(198, 12844, 'easypack24MassCancel', 'ps_easypack24', 'mass_cancel', '', 'storeadmin,admin');


INSERT INTO `#YOUR_PREFIX_vm_module` (`module_id`, `module_name`, `module_description`, `module_perms`, `module_publish`, `list_order`) VALUES
(12844, 'easypack24', 'InPost Parcel Lockers', 'storeadmin,admin', 'Y', 1);
