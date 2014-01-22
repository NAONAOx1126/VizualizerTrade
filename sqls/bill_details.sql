CREATE TABLE IF NOT EXISTS `trade_bill_details` (
  `bill_detail_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '案件明細ID',
  `related_bill_detail_id` int(11) NOT NULL COMMENT '関連取引明細ID',
  `bill_id` int(11) NOT NULL COMMENT '案件ID',
  `bill_detail_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT '案件明細名',
  `price` int(11) NOT NULL COMMENT '単価',
  `quantity` int(11) NOT NULL COMMENT '数量',
  `unit` int(11) NOT NULL COMMENT '単位',
  `tax_type` int(11) NOT NULL COMMENT '消費税区分',
  `tax_rate` int(11) NOT NULL COMMENT '消費税率',
  `create_time` datetime NOT NULL COMMENT 'データ登録日時',
  `update_time` datetime NOT NULL COMMENT 'データ最終更新日時',
  PRIMARY KEY (`bill_detail_id`),
  KEY `trade_id` (`bill_id`),
  KEY `related_trade_detail_id` (`related_bill_detail_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='請求明細テーブル';

ALTER TABLE `trade_bill_details`
  ADD CONSTRAINT `trade_bill_details_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `trade_bills` (`bill_id`) ON DELETE CASCADE ON UPDATE CASCADE;
