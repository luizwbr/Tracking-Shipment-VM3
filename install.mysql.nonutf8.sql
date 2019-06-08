CREATE TABLE IF NOT EXISTS `#__virtuemart_shipment_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) DEFAULT NULL,
  `virtuemart_order_id` int(11) DEFAULT NULL,
  `tracking_code` varchar(50) DEFAULT '0000-00-00',
  `post_date` datetime DEFAULT NULL,
  `estimate_date` datetime NOT NULL,
  `virtuemart_order_item_id` int(10) NULL,
  `nfe` VARCHAR( 30 ) NULL,
  PRIMARY KEY (`id`)  
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
