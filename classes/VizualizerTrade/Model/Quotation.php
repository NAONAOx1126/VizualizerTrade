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
class VizualizerTrade_Model_Quotation extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("Quotations"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $bill_id 請求ID
     */
    public function findByPrimaryKey($quotation_id)
    {
        $this->findBy(array("quotation_id" => $quotation_id));
    }

    /**
     * この請求の明細を取得する。
     *
     * @return 請求明細
     */
    public function details()
    {
        $loader = new Vizualizer_Plugin("trade");
        $billDetail = $loader->loadModel("QuotationDetail");
        $billDetails = $billDetail->findAllByQuotation($this->quotation_id);
        return $billDetails;
    }

    /**
     * この取引の作業者を取得する。
     *
     * @return 作業者
     */
    public function source()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->setIgnoreOperator(true);
        $companyOperator->findByPrimaryKey($this->source_operator_id);
        return $companyOperator;
    }

    /**
     * この取引の顧客を取得する。
     *
     * @return 顧客
     */
    public function dest()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->setIgnoreOperator(true);
        $companyOperator->findByPrimaryKey($this->dest_operator_id);
        return $companyOperator;
    }

    /**
     * この取引の請求種別を取得する。
     *
     * @return 請求種別
     */
    public function type()
    {
        $loader = new Vizualizer_Plugin("trade");
        $type = $loader->loadModel("Type");
        $type->findByPrimaryKey($this->trade_type);
        return $type;
    }

    /**
     * この取引の請求ステータスを取得する。
     *
     * @return 請求ステータス
     */
    public function status()
    {
        $loader = new Vizualizer_Plugin("trade");
        $status = $loader->loadModel("Status");
        $status->findByPrimaryKey($this->trade_status);
        return $status;
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

    /**
     * 明細から合計金額を計算する
     */
    public function calculate(){
        $total = 0;
        $tax = 0;
        foreach($this->details() as $detail){
            $total += $detail->price * $detail->quantity;
            $intax = $detail->price * $detail->tax_rate / 100 * $detail->quantity;
            switch($detail->tax_type){
                case 1:
                    // 外税の場合は合計に加算
                    $total += $intax;
                case 2:
                    // 内税／外税の場合は税額を加算
                    $tax += $intax;
                    break;
            }
        }
        $this->tax = floor($tax);
        $this->total = floor($total) - $this->discount + $this->adjust;
        $this->subtotal = floor($total) - floor($tax);
        $this->save();
    }

    /**
     * 取引が分割可能な場合、取引を分割する。
     */
    public function split(){
        $loader = new Vizualizer_Plugin("Trade");
        $orgSplit = $loader->loadModel("Split");
        $orgSplit->findBySplit($this->source_operator_id, $this->dest_operator_id, $this->trade_type);
        if($orgSplit->split_id > 0){
                // 分割したデータが無い場合は、新規作成のため取引の情報を連想配列にする。
                $arrBill = $this->toArray();
                unset($arrBill["bill_id"]);
                $arrBill["related_bill_id"] = $this->bill_id;
                $arrBillDetails = array();
                foreach($this->details() as $detail){
                    $arrBillDetail = $detail->toArray();
                    unset($arrBillDetail["bill_detail_id"]);
                    $arrBillDetail["related_bill_detail_id"] = $detail->bill_detail_id;
                    $arrBillDetails[] = $arrBillDetail;
                }

                // フロントから顧客への取引データを作成
                $bill = $loader->loadModel("Bill", $arrBill);
                $bill->worker_operator_id = $orgSplit->contact_operator_id;
                $admin = new Vizualizer_Plugin("Admin");
                if($bill->worker_operator_id > 0){
                    $operator = $admin->loadModel("CompanyOperator");
                    $operator->setIgnoreOperator(true);
                    $operator->findByPrimaryKey($bill->worker_operator_id);
                    $bill->worker_company_id = $operator->company_id;
                }
                if($bill->customer_operator_id > 0){
                    $operator = $admin->loadModel("CompanyOperator");
                    $operator->setIgnoreOperator(true);
                    $operator->findByPrimaryKey($bill->customer_operator_id);
                    $bill->customer_company_id = $operator->company_id;
                }
                $bill->save();
                foreach($arrBillDetails as $arrBillDetail){
                    $detail = $loader->loadModel("BillDetail", $arrBillDetail);

                    $detail->bill_id = $bill->bill_id;
                    $detail->save();
                }

                // 作業者からフロントへの取引データを作成（金額の計算はあとで実施）
                $bill = $loader->loadModel("Bill", $arrBill);
                $bill->customer_operator_id = $orgSplit->contact_operator_id;
                $bill->price_rate = 100 - $orgSplit->contact_mergin_rate;
                $bill->subtotal = floor($this->subtotal * $bill->price_rate / 100);
                $bill->discount = floor($this->discount * $bill->price_rate / 100);
                $bill->tax = floor($this->tax * $bill->price_rate / 100);
                $bill->total = floor($this->total * $bill->price_rate / 100);
                $bill->adjust = floor($this->adjust * $bill->price_rate / 100);
                $bill->payment_total = floor($this->payment_total * $bill->price_rate / 100);
                $admin = new Vizualizer_Plugin("Admin");
                if($bill->worker_operator_id > 0){
                    $operator = $admin->loadModel("CompanyOperator");
                    $operator->setIgnoreOperator(true);
                    $operator->findByPrimaryKey($bill->worker_operator_id);
                    $bill->worker_company_id = $operator->company_id;
                }
                if($bill->customer_operator_id > 0){
                    $operator = $admin->loadModel("CompanyOperator");
                    $operator->setIgnoreOperator(true);
                    $operator->findByPrimaryKey($bill->customer_operator_id);
                    $bill->customer_company_id = $operator->company_id;
                }
                $bill->save();
                foreach($arrBillDetails as $arrBillDetail){
                    $detail = $loader->loadModel("BillDetail", $arrBillDetail);
                    $detail->bill_id = $bill->bill_id;
                    $detail->price = floor($arrBillDetail["price"] * $bill->price_rate / 100);
                    $detail->save();
                }
            }else{
                // 金額を再設定（日付とステータスは分割時に独立した要素となるため、更新時には対象としない）
                foreach($splitted as $split){
                    // 金額を再設定
                    $split->price_rate = "100";
                    $split->subtotal = $this->subtotal;
                    $split->discount = $this->discount;
                    $split->tax = $this->tax;
                    $split->total = $this->total;
                    $split->adjust = $this->adjust;
                    $split->payment_total = $this->payment_total;
                    $split->description = $this->description;
                    foreach($split->details() as $splitDetail){
                        $detail = $loader->loadModel("BillDetail");
                        $detail->findByPrimaryKey($splitDetail->related_bill_detail_id);
                        if($detail->bill_detail_id > 0){
                            $splitDetail->bill_detail_name = $detail->bill_detail_name;
                            $splitDetail->price = $detail->price;
                            $splitDetail->quantity = $detail->quantity;
                            $splitDetail->unit = $detail->unit;
                            $splitDetail->tax_type = $detail->tax_type;
                            $splitDetail->tax_rate = $detail->tax_rate;
                            $splitDetail->save();
                        }else{
                            $splitDetail->delete();
                        }
                    }
                    foreach($this->details() as $detail){
                        $splitDetail = $loader->loadModel("BillDetail");
                        $splitDetail->findByRelated($split->bill_id, $detail->bill_detail_id);
                        if(!($splitDetail->bill_detail_id > 0)){
                            $arrBillDetail = $detail->toArray();
                            unset($arrBillDetail["bill_detail_id"]);
                            $arrBillDetail["bill_id"] = $splitDetail->bill_id;
                            $arrBillDetail["related_bill_detail_id"] = $detail->bill_detail_id;
                            $splitDetail = $loader->loadModel("BillDetail");
                            foreach($arrBillDetail as $name => $value){
                                $splitDetail->$name = $value;
                            }
                            $splitDetail->save();
                        }
                    }

                    if($split->worker_operator_id == $orgSplit->worker_operator_id && $split->customer_operator_id == $orgSplit->contact_operator_id){
                        // 作業者からフロントの取引の場合は金額を計算
                        $split->price_rate = 100 - $orgSplit->contact_mergin_rate;
                        $split->subtotal = floor($this->subtotal * $split->price_rate / 100);
                        $split->discount = floor($this->discount * $split->price_rate / 100);
                        $split->tax = floor($this->tax * $split->price_rate / 100);
                        $split->total = floor($this->total * $split->price_rate / 100);
                        $split->adjust = floor($this->adjust * $split->price_rate / 100);
                        $split->payment_total = floor($this->payment_total * $split->price_rate / 100);
                        foreach($split->details() as $splitDetail){
                            $detail = $loader->loadModel("BillDetail");
                            $detail->findByPrimaryKey($splitDetail->related_trade_detail_id);
                            if($detail->trade_detail_id > 0){
                                $splitDetail->price = floor($detail->price * $split->price_rate / 100);
                                $splitDetail->save();
                            }
                        }
                    }
                    $split->save();
                }
            }
        }
    }
}
