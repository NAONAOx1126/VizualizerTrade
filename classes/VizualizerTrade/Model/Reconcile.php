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
 * 消し込みのモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_Reconcile extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("Reconciles"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $reconcile_id 消し込みID
     */
    public function findByPrimaryKey($reconcile_id)
    {
        $this->findBy(array("reconcile_id" => $reconcile_id));
    }

    /**
     * この消し込みの請求を取得する。
     *
     * @return 請求
     */
    public function bill()
    {
        $loader = new Vizualizer_Plugin("trade");
        $model = $loader->loadModel("Bill");
        $model->findByPrimaryKey($this->bill_id);
        return $model;
    }

    /**
     * この消し込みの入金を取得する。
     *
     * @return 入金
     */
    public function payment()
    {
        $loader = new Vizualizer_Plugin("trade");
        $model = $loader->loadModel("Payment");
        $model->findByPrimaryKey($this->payment_id);
        return $model;
    }

    /**
     * 消し込み処理を実行する。
     */
    public function reconcile($bill, $payment){
        // 残額を計算する。
        $billTotal = $bill->payment_total - $bill->payed_total;
        $paymentTotal = $payment->payment_total - $payment->reconciled_total;

        // 消し込み額を計算
        if($billTotal > $paymentTotal){
            $reconcilesTotal = $paymentTotal;
        }else{
            $reconcilesTotal = $billTotal;
        }

        if($reconcilesTotal > 0){
            $loader = new Vizualizer_Plugin("trade");
            $model = $loader->loadModel("Reconcile");
            $model->bill_id = $bill->bill_id;
            $model->payment_id = $payment->payment_id;
            $model->reconciled_total = $reconcilesTotal;
            $model->save();
            $bill->payed_total += $reconcilesTotal;
            if($bill->payment_total <= $bill->payed_total){
                $bill->status_id = 8;
                $bill->complete_date = $payment->payment_date;
            }
            $bill->save();
            $payment->reconciled_total += $reconcilesTotal;
            if($payment->payment_total <= $payment->reconciled_total){
                $payment->reconciled_flg = 1;
                $payment->reconciled_time = date("Y-m-d H:i:s");
            }
            $payment->save();
        }
    }
}
