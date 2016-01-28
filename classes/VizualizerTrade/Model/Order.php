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
 * 見積／請求のモデルのベースクラスです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_Order extends Vizualizer_Plugin_Model
{
    // オブジェクトの種別
    protected $orderType;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($model, $values = array())
    {
        parent::__construct($model, $values);
    }

    /**
     * データを登録する。
     */
    public function save()
    {
        // オペレータIDから法人IDを自動取得する。
        $admin = new Vizualizer_Plugin("Admin");
        if($this->source_operator_id > 0){
            $operator = $admin->loadModel("CompanyOperator");
            $operator->findByPrimaryKey($this->source_operator_id);
            $this->source_company_id = $operator->company_id;
        } else {
            $this->source_company_id = "";
            $post->set("source_company_id", "");
        }
        if($this->dest_operator_id > 0){
            $operator = $admin->loadModel("CompanyOperator");
            $operator->findByPrimaryKey($this->dest_operator_id);
            $this->dest_company_id = $operator->company_id;
        } else {
            $this->dest_company_id = $operator->company_id;
        }

        // 保存を実行する。
        parent::save();
    }

    /**
     * コピーデータを新しく作成する。
     * @param string $type
     * @return order
     */
    public function copy() {
        // 請求元から窓口への見積をコピーして作成する。
        $modelName = strtoupper(substr($this->orderType, 0, 1)).strtolower(substr($this->orderType, 1));
        $model = $loader->loadModel($modelName);
        $modelPrimaryKey = $this->orderType."_id";
        foreach ($this->toArray() as $key => $value) {
            if ($key != $modelPrimaryKey) {
                $model->$key = $value;
            }
        }
        $model->save();
        foreach ($this->details() as $detail) {
            $modelDetail = $loader->loadModel($modelName."Detail");
            foreach ($detail->toArray() as $key => $value) {
                if ($key != $type."_detail_id") {
                    $modelDetail->$key = $value;
                }
            }
            $modelDetail->$modelPrimaryKey = $model->$modelPrimaryKey;
            $modelDetail->save();
        }

        return $model;
    }

    /**
     * この見積の明細を取得する。
     *
     * @return 見積明細
     */
    public function details()
    {
        $loader = new Vizualizer_Plugin("trade");
        $modelName = strtoupper(substr($this->orderType, 0, 1)).strtolower(substr($this->orderType, 1));
        $modelPrimaryKey = $this->orderType."_id";
        $quotationDetail = $loader->loadModel($modelName."Detail");
        $quotationDetails = $quotationDetail->findAllBy(array($modelPrimaryKey => $this->$modelPrimaryKey));
        return $quotationDetails;
    }

    /**
     * 請求元を取得する。
     *
     * @return 請求元
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
        $split = $loader->loadModel("Split");
        $split->findBySplit($this->source_operator_id, $this->dest_operator_id, $this->trade_type);
        if($split->split_id > 0){
            $model = $this->copy();
            // 請求先を窓口に変更
            $model->dest_operator_id = $split->contact_operator_id;
            $model->save();
            foreach ($model->details() as $detail) {
                $detail->price = floor($detail->price * (100 - $split->contact_mergin_rate));
                $detail->save();
            }
            // 登録後に再計算を実施
            $model->calculate();

            // コピー元の請求元を窓口に変更
            $this->source_operator_id = $split->contact_operator_id;
            $this->save();
        }
    }
}
