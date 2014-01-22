CREATE TABLE IF NOT EXISTS `trade_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '入金ID',
  `company_id` int(11) NOT NULL COMMENT '入金先法人ID',
  `payment_date` date NOT NULL COMMENT '入金日',
  `bank_name` varchar(50) NOT NULL COMMENT '振込元銀行名',
  `branch_name` varchar(50) NOT NULL COMMENT '振込元支店名',
  `payment_code` varchar(20) NOT NULL COMMENT '振込依頼人コード',
  `payment_name` varchar(100) NOT NULL COMMENT '振込依頼人名',
  `reconciled_total` int(11) DEFAULT NULL COMMENT '消し込み済み合計',
  `reconciled_flg` tinyint(1) DEFAULT NULL COMMENT '消し込み済みフラグ',
  `reconciled_time` datetime DEFAULT NULL COMMENT '消し込み完了日時',
  `create_time` datetime NOT NULL COMMENT 'データ登録日時',
  `update_time` datetime NOT NULL COMMENT 'データ最終更新日時',
  PRIMARY KEY (`payment_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `trade_payments`
  ADD CONSTRAINT `trade_payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `admin_companys` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE;
