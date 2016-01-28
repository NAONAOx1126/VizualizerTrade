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
 * 請求のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_Bill extends VizualizerTrade_Model_Order
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $this->orderType = "bill";
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("Bills"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $bill_id 請求ID
     */
    public function findByPrimaryKey($bill_id)
    {
        $this->findBy(array("bill_id" => $bill_id));
    }

    /**
     * この請求の消し込みを取得する。
     *
     * @return 消し込み
     */
    public function reconciles()
    {
        $loader = new Vizualizer_Plugin("trade");
        $reconcile = $loader->loadModel("Reconcile");
        $reconciles = $reconcile->findAllByBill($this->bill_id);
        return $reconciles;
    }

    public function calcPaymentDate(){
        // 支払日は請求日と顧客の締め支払いから自動計算する。
        if(!empty($this->billing_date)){
            // 締め支払いを計算するための顧客情報を取得
            $loader = new Vizualizer_Plugin("Admin");
            $customer = $loader->loadModel("CompanyOperator");
            $customer->setIgnoreOperator(true);
            $customer->findByPrimaryKey($this->customer_operator_id);
            $company = $customer->company();
            // 請求月の計算をするための起算日を取得する。
            $baseDate = date("Y-m-01", strtotime($this->billing_date));
            // 支払い月の起算日を取得する
            if(date("d", strtotime($this->billing_date)) <= $company->limit_day){
                $paymentBaseDate = date("Y-m-d", strtotime("+".$company->payment_month."month", strtotime($baseDate)));
            }else{
                $paymentBaseDate = date("Y-m-d", strtotime("+".($company->payment_month + 1)."month", strtotime($baseDate)));
            }
            // 支払い期限日を取得する
            if($company->payment_day < 99){
                $paymentDate = date("Y-m-".sprintf("%02d", $company->payment_day), strtotime($paymentBaseDate));
            }else{
                $paymentDate = date("Y-m-t", strtotime($paymentBaseDate));
            }
            $this->payment_date = $paymentDate;
            $this->save();
        }
    }
}
