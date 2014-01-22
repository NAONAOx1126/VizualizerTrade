<?php

/**
 * Copyright (C) 2012 Vizualizer All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Naohisa Minagawa <info@vizualizer.jp>
 * @copyright Copyright (c) 2010, Vizualizer
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 * @since PHP 5.3
 * @version   1.0.0
 */

/**
 * 請求明細のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_BillDetail extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("BillDetails"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $trade_detail_id 請求明細ID
     */
    public function findByPrimaryKey($bill_detail_id)
    {
        $this->findBy(array("bill_detail_id" => $bill_detail_id));
    }

    /**
     * 関連請求明細と現請求でデータを取得する。
     *
     * @param $bill_id 請求ID
     * @param $bill_detail_id 関連請求明細ID
     */
    public function findByRelated($bill_id, $bill_detail_id)
    {
        $this->findBy(array("bill_id" => $bill_id, "related_bill_detail_id" => $bill_detail_id));
    }

    /**
     * 請求IDでデータを取得する。
     *
     * @param $bill_id 請求ID
     */
    public function findAllByBill($bill_id){
        return $this->findAllBy(array("bill_id" => $bill_id));
    }

    /**
     * この請求明細の請求を取得する。
     *
     * @return 請求
     */
    public function bill()
    {
        $loader = new Vizualizer_Plugin("trade");
        $bill = $loader->loadModel("Bill");
        $bill->findByPrimaryKey($this->bill_id);
        return $bill;
    }
}
