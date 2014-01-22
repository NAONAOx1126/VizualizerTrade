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
 * 継続案件の複製を行う月次バッチです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Batch_Continue extends Vizualizer_Plugin_Batch
{
    private $today;

    public function getName(){
        return "Trade Continue";
    }

    public function getFlows(){
        return array("getContinueTrades", "copyTrades");
    }

    /**
     * 継続案件のうち、今月コピーが必要な案件を抽出する。
     * @param $params バッチ自体のパラメータ
     * @param $data バッチで引き回すデータ
     * @return バッチで引き回すデータ
     */
    protected function getContinueTrades($params, $data){
        // パラメータから日付を取得
        $this->today = "";
        if(count($params) >= 4){
            $this->today = $params[3];
        }
        if(preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $this->today) == 0){
            // 日付が正しくない場合は本日の日付を利用
            $this->today = date("Y-m-d");
        }
        // 日付を補正
        $this->today = date("Y-m-d", strtotime($this->today));
        // 継続対象案件を取得する。
        $loader = new Vizualizer_Plugin("Admin");
        $model = $loader->loadModel("Trade");
        $trades = $model->findAllByContinue($this->today);

        // データに保存して返す
        $data["trades"] = $trades;
        return $data;
    }

    /**
     * 継続案件のうち、今月コピーが必要な案件を抽出する。
     * @param $params バッチ自体のパラメータ
     * @param $data バッチで引き回すデータ
     * @return バッチで引き回すデータ
     */
    protected function copyTrades($params, $data){
        // トランザクションの開始
        $connection = Vizualizer_Database_Factory::begin("admin");

        try {
            $tradeIdMap = array();
            $detailIdMap = array();

            foreach($data["trades"] as $trade){
                // コピーデータの作成
                $loader = new Vizualizer_Plugin("Admin");
                $model = $loader->loadModel("Trade");
                foreach($trade->toArray() as $key => $value){
                    $model->$key = $value;
                }
                // 納品日と請求日を当月の締め日に設定
                $company = $trade->customer()->company();
                if($company->limit_day < 99){
                    $model->delivered_date = $model->billing_date = date("Y-m-".sprintf("%02d", $company->limit_day), strtotime($this->today));
                }else{
                    $model->delivered_date = $model->billing_date = date("Y-m-t", strtotime($this->today));
                }
                // 支払日を自動計算
                $paymentMonth = strtotime("+".$company->payment_month." month", strtotime($model->billing_date));
                if($company->payment_day < 99){
                    $model->payment_date = date("Y-m-".sprintf("%02d", $company->payment_day), $paymentMonth);
                }else{
                    $model->payment_date = date("Y-m-t", $paymentMonth);
                }
                if($model->related_trade_id > 0){
                    $model->related_trade_id = $tradeIdMap[$model->related_trade_id];
                }
                $model->save();
                $tradeIdMap[$trade->trade_id] = $model->trade_id;

                // 明細データをコピー
                foreach($trade->details() as $detail){
                    $sub = $loader->loadModel("TradeDetail");
                    foreach($detail->toArray() as $key => $value){
                        $sub->$key = $value;
                    }
                    // 取引IDをコピー
                    $sub->trade_id = $model->trade_id;
                    if($sub->related_trade_detail_id > 0){
                        $sub->related_trade_detail_id = $detailIdMap[$detail->related_trade_detail_id];
                    }
                    $sub->save();
                    $detailIdMap[$detail->trade_detail_id] = $sub->trade_detail_id;
                }
            }

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($connection);
        } catch (Exception $e) {
            Vizualizer_Database_Factory::rollback($connection);
            throw new Vizualizer_Exception_Database($e);
        }
    }
}
