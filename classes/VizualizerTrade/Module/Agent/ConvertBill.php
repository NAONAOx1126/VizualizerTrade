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
 * SEOのデータを請求のデータに変換する。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Agent_ConvertBill extends Vizualizer_Plugin_Module_Save
{

    function execute($params)
    {
        $post = Vizualizer::request();
        // トランザクションの開始
        $connection = Vizualizer_Database_Factory::begin("trade");

        try {
            // 取引データを取得し、保存する。
            $loader = new Vizualizer_Plugin("Trade");
            $agent = $loader->loadModel("Agent");
            $agents = $agent->findAllBy(array("bill_flg" => "0"));
            $billIds = array();
            foreach($agents as $agent){
                $key1 = $agent->agent_type.":".$agent->agent_month.":1-".$agent->client_name.":".$agent->contact_name.":".$agent->worker_name;
                $key2 = $agent->agent_type.":".$agent->agent_month.":2-".$agent->client_name.":".$agent->contact_name.":".$agent->worker_name;
                // 請求データが作成済みか調べる
                if(!array_key_exists($key1, $billIds)){
                    // 請求データが無い場合は請求データを作成
                    $bill1 = $loader->loadModel("Bill");
                    $bill2 = $loader->loadModel("Bill");
                    $bill1->bill_name = $bill2->bill_name = $agent->client_name."（".$agent->agent_type."）";
                    switch($agent->agent_type){
                        case "Listing":
                            $bill1->type_id = $bill2->type_id = "3";
                            break;
                        case "SEO":
                            $bill1->type_id = $bill2->type_id = "1";
                            break;
                        case "逆SEO":
                            $bill1->type_id = $bill2->type_id = "4";
                            break;
                    }
                    // クライアントの担当者を取得
                    $agentOperator = $loader->loadModel("AgentOperator");
                    $agentOperator->findByAgentOperatorName($this->trim($agent->client_name));
                    if($agentOperator->agent_operator_id > 0){
                        $operator = $agentOperator->operator();
                        $bill1->customer_company_id = $operator->company()->company_id;
                        $bill1->customer_operator_id = $operator->operator_id;
                    }else{
                        continue;
                    }
                    // 窓口担当者を取得
                    $agentOperator = $loader->loadModel("AgentOperator");
                    $agentOperator->findByAgentOperatorName($this->trim($agent->contact_name));
                    if($agentOperator->agent_operator_id > 0){
                        $operator = $agentOperator->operator();
                        $bill1->worker_company_id = $bill2->customer_company_id = $operator->company()->company_id;
                        $bill1->worker_operator_id = $bill2->customer_operator_id = $operator->operator_id;
                    }else{
                        continue;
                    }
                    // 作業担当者を取得
                    $agentOperator = $loader->loadModel("AgentOperator");
                    $agentOperator->findByAgentOperatorName($this->trim($agent->worker_name));
                    if($agentOperator->agent_operator_id > 0){
                        $operator = $agentOperator->operator();
                        $bill2->worker_company_id = $operator->company()->company_id;
                        $bill2->worker_operator_id = $operator->operator_id;
                    }else{
                        continue;
                    }
                    $bill1->status_id = $bill2->status_id = 1;
                    $bill1->save();
                    $bill2->save();
                    $billIds[$key1] = $bill1->bill_id;
                    $billIds[$key2] = $bill2->bill_id;
                }
                // 明細データを作成
                $detail1 = $loader->loadModel("BillDetail");
                $detail1->bill_id = $billIds[$key1];
                $detail1->bill_detail_name = $agent->client_name." ".$agent->agent_name;
                $detail1->price = $agent->total;
                $detail1->quantity = 1;
                $detail1->tax_type = 1;
                $detail1->tax_rate = 5;
                $detail1->save();
                print_r($detail1->toArray());echo "<br>";
                $detail2 = $loader->loadModel("BillDetail");
                $detail2->bill_id = $billIds[$key2];
                $detail2->bill_detail_name = $agent->client_name." ".$agent->agent_name;
                $detail2->price = $agent->total - $agent->contact_profit;
                $detail2->quantity = 1;
                $detail2->tax_type = 1;
                $detail2->tax_rate = 5;
                $detail2->save();
                print_r($detail2->toArray());echo "<br>";

                // 請求データ移行済みフラグをONにする
                $agent->bill_flg = 1;
                $agent->save();
            }

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($connection);

            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("trade");

            // 合計金額を再計算
            foreach($billIds as $billId){
                echo $billId."<br>";
                $bill = $loader->loadModel("Bill");
                $bill->findByPrimaryKey($billId);
                $bill->calculate();
                print_r($bill->toArray());echo "<br>";

            }

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($connection);
        } catch (Exception $e) {
            Vizualizer_Database_Factory::rollback($connection);
            throw new Vizualizer_Exception_Database($e);
        }
    }
}
