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
class VizualizerTrade_Model_Bill extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
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
     * 関連請求IDでデータを取得する。
     *
     * @param $bill_id 請求ID
     */
    public function findAllByRelated($bill_id)
    {
        return $this->findAllBy(array("related_bill_id" => $bill_id));
    }

    /**
     * 指定日に引き継ぎ対象となるでデータを取得する。
     *
     * @param $today 引き継ぎ取得の指定日
     */
    public function findAllByContinue($today)
    {
        // 指定日から指定日の所属する月の朔日を取得
        $month = date("Y-m-01", strtotime($today));

        // 該当の取引を取得
        $select = new Vizualizer_Query_Select($this->access);
        $select->addColumn($this->access->_W);
        $select->addWhere($this->access->continue_interval." > 0");
        $select->addWhere($this->access->billing_date." LIKE CONCAT(SUBSTRING(DATE_SUB(?, INTERVAL ".$this->access->continue_interval." MONTH), 1, 8), '%')", array($month));
        $select->addOrder($this->access->related_bill_id);
        $select->setLimit($this->limit, $this->offset);
        $sqlResult = $select->fetch($this->limit, $this->offset);
        $thisClass = get_class($this);
        $result = new Vizualizer_Plugin_ModelIterator($thisClass, $sqlResult);
        return $result;
    }

    /**
     * この請求の明細を取得する。
     *
     * @return 請求明細
     */
    public function details()
    {
        $loader = new Vizualizer_Plugin("trade");
        $billDetail = $loader->loadModel("BillDetail");
        $billDetails = $billDetail->findAllByBill($this->bill_id);
        return $billDetails;
    }

    /**
     * この取引の作業者を取得する。
     *
     * @return 作業者
     */
    public function worker()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->findByPrimaryKey($this->worker_operator_id);
        return $companyOperator;
    }

    /**
     * この取引の顧客を取得する。
     *
     * @return 顧客
     */
    public function customer()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->findByPrimaryKey($this->customer_operator_id);
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
        $type->findByPrimaryKey($this->type_id);
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
        $status->findByPrimaryKey($this->status_id);
        return $status;
    }

    /**
     * 明細から合計金額を計算する
     */
    public function calculate(){
        $subtotal = 0;
        $tax = 0;
        foreach($this->details() as $detail){
            $subtotal += $detail->price * $detail->quantity;
            $intax = floor($detail->price * $detail->tax_rate / 100) * $detail->quantity;
            switch($detail->tax_type){
                case 2:
                    // 内税の場合は小計から減算
                    $subtotal -= $intax;
                case 1:
                    // 内税／外税の場合は税額を加算
                    $tax += $intax;
                    break;
            }
        }
        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->total = $this->subtotal - $this->discount + $this->tax;
        $this->save();
    }

    /**
     * 取引が分割可能な場合、取引を分割する。
     */
    public function split(){
        $loader = new Vizualizer_Plugin("trade");
        $orgSplit = $loader->loadModel("Split");
        $orgSplit->findByWorkerCustomerType($this->worker_operator_id, $this->customer_operator_id, $this->type_id);
        if($orgSplit->split_id > 0){
            $splitted = $this->findAllByRelated($this->bill_id);

            if(!$splitted->valid()){
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
                $bill->worker_operator_id = $tradeSplit->contact_operator_id;
                $bill->save();
                foreach($arrBillDetails as $arrBillDetail){
                    $detail = $loader->loadModel("BillDetail", $arrBillDetail);

                    $detail->bill_id = $bill->bill_id;
                    $detail->save();
                }

                // 作業者からフロントへの取引データを作成（金額の計算はあとで実施）
                $bill = $loader->loadModel("Bill", $arrBill);
                $bill->customer_operator_id = $split->contact_operator_id;
                $bill->save();
                foreach($arrBillDetails as $arrBillDetail){
                    $detail = $loader->loadModel("BillDetail", $arrBillDetail);
                    $detail->bill_id = $bill->bill_id;
                    $detail->save();
                }
                $splitted = $this->findAllByRelated($this->bill_id);
            }

            // 日付／ステータス／金額を再設定
            foreach($splitted as $split){
                // 日付とステータスと金額を再設定
                $split->price_rate = "100";
                $split->subtotal = $this->subtotal;
                $split->discount = $this->discount;
                $split->tax = $this->tax;
                $split->total = $this->total;
                $split->start_date = $this->start_date;
                $split->planed_date = $this->planed_date;
                $split->ordered_date = $this->ordered_date;
                $split->delivered_date = $this->delivered_date;
                $split->billing_date = $this->billing_date;
                $split->payment_date = $this->payment_date;
                $split->complete_date = $this->complete_date;
                $split->status_id = $this->status_id;
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

                if($split->worker_operator_id == $billSplit->worker_operator_id && $split->customer_operator_id == $tradeSplit->contact_operator_id){
                    // 作業者からフロントの取引の場合は金額を計算
                    $split->price_rate = 100 - $tradeSplit->contact_mergin_rate;
                    $split->subtotal = floor($this->subtotal * $split->price_rate / 100);
                    $split->discount = floor($this->discount * $split->price_rate / 100);
                    $split->tax = floor($this->tax * $split->price_rate / 100);
                    $split->total = floor($this->total * $split->price_rate / 100);
                    foreach($split->details() as $splitDetail){
                        $detail = $loader->loadModel("TradeDetail");
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
