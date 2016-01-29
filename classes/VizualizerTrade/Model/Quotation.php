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
 * 見積のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_Quotation extends VizualizerTrade_Model_Order
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $this->orderType = "quotation";
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("Quotations"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $quotation_id 見積ID
     */
    public function findByPrimaryKey($quotation_id)
    {
        $this->findBy(array("quotation_id" => $quotation_id));
    }

    /**
     * データを請求にコピーする。
     */
    public function toBill()
    {
        if ($this->trade_status == 3) {
            $loader = new Vizualizer_Plugin("Trade");
            $model = $loader->loadModel("Bill");
            $model->findBy(array("quotation_id" => $this->quotation_id));
            if (!($model->bill_id > 0)) {
                // トランザクションの開始
                $connection = Vizualizer_Database_Factory::begin("trade");
                try {
                    // 請求元から窓口への見積をコピーして作成する。
                    $model = $loader->loadModel("Bill");
                    foreach ($this->toArray() as $key => $value) {
                        $model->$key = $value;
                    }
                    $model->save();
                    // エラーが無かった場合、処理をコミットする。
                    Vizualizer_Database_Factory::commit($connection);
                    // トランザクションの開始
                    $connection = Vizualizer_Database_Factory::begin("trade");
                    foreach ($this->details() as $detail) {
                        $modelDetail = $loader->loadModel("BillDetail");
                        foreach ($detail->toArray() as $key => $value) {
                            $modelDetail->$key = $value;
                        }
                        $modelDetail->bill_id = $model->bill_id;
                        $modelDetail->save();
                    }

                    // エラーが無かった場合、処理をコミットする。
                    Vizualizer_Database_Factory::commit($connection);
                } catch (Exception $e) {
                    // エラーが無かった場合、処理をコミットする。
                    Vizualizer_Database_Factory::rollback($connection);
                    throw $e;
                }
            }
        }
    }
}
