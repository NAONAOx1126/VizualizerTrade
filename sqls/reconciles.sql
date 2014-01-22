CREATE TABLE IF NOT EXISTS `trade_reconciles` (
  `reconcile_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消し込みID',
  `bill_id` int(11) NOT NULL COMMENT '請求ID',
  `payment_id` int(11) NOT NULL COMMENT '入金ID',
  `reconciled_total` int(11) NOT NULL COMMENT '消し込み金額',
  `create_time` datetime NOT NULL COMMENT 'データ登録日時',
  `update_time` datetime NOT NULL COMMENT 'データ最終更新日時',
  PRIMARY KEY (`reconcile_id`),
  KEY `bill_id` (`bill_id`),
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `trade_reconciles`
  ADD CONSTRAINT `trade_reconciles_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `trade_payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `trade_reconciles_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `trade_bills` (`bill_id`) ON DELETE CASCADE ON UPDATE CASCADE;
