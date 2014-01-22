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
 * 請求のデータを保存する。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Bill_Save extends Vizualizer_Plugin_Module_Save
{

    function execute($params)
    {
        $post = Vizualizer::request();
        if ($post["add"] || $post["save"]) {
            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("trade");

            try {
                // 取引データを取得し、保存する。
                $loader = new Vizualizer_Plugin("Trade");
                $model = $loader->loadModel("Bill");
                $primary_key = "bill_id";
                if (!empty($post[$this->key_prefix . $primary_key])) {
                    $model->findByPrimaryKey($post[$this->key_prefix . $primary_key]);
                    if (!($model->$primary_key > 0)) {
                        $model = $loader->loadModel("Bill", array($primary_key => $post[$this->key_prefix . $primary_key]));
                    }
                }
                foreach ($post as $key => $value) {
                    if (!empty($this->key_prefix)) {
                        if (substr($key, 0, strlen($this->key_prefix)) == $this->key_prefix) {
                            $key = preg_replace("/^" . $this->key_prefix . "/", "", $key);
                            $model->$key = $value;
                        }
                    } else {
                        $model->$key = $value;
                    }
                }
                // 支払日は請求日と顧客の締め支払いから自動計算する。
                if(!empty($model->billing_date)){
                    // 締め支払いを計算するための顧客情報を取得
                    $customer = $loader->loadModel("CompanyOperator");
                    $customer->findByPrimaryKey($model->customer_operator_id);
                    $company = $customer->company();
                    // 請求月の計算をするための起算日を取得する。
                    $baseDate = date("Y-m-01", strtotime($model->billing_date));
                    // 支払い月の起算日を取得する
                    if(date("d", strtotime($model->billing_date)) <= $company->limit_day){
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
                    $model->payment_date = $paymentDate;
                }
                $model->save();
                if (!empty($this->key_prefix)) {
                    $post->set($this->key_prefix . $primary_key, $model->$primary_key);
                } else {
                    $post->set($primary_key, $model->$primary_key);
                }

                $bill = $model;
                $details = $model->details();
                $inputs = array();
                foreach($post["details"] as $detail){
                    if(!empty($detail["bill_detail_name"]) && is_numeric($detail["price"]) && $detail["quantity"] > 0){
                        $inputs[] = $detail;
                    }
                }

                $index = 0;
                foreach ($details as $model) {
                    $primary_key = "bill_detail_id";
                    if ($index < count($inputs)) {
                        foreach ($inputs[$index] as $key => $value) {
                            if (!empty($this->key_prefix)) {
                                if (substr($key, 0, strlen($this->key_prefix)) == $this->key_prefix) {
                                    $key = preg_replace("/^" . $this->key_prefix . "/", "", $key);
                                    $model->$key = $value;
                                }
                            } else {
                                $model->$key = $value;
                            }
                        }
                        $model->save();
                        if (!empty($this->key_prefix)) {
                            $inputs[$index][$this->key_prefix . $primary_key] = $model->$primary_key;
                        } else {
                            $inputs[$index][$primary_key] = $model->$primary_key;
                        }
                        $index ++;
                    } else {
                        $model->delete();
                    }
                }
                if ($index <= count($inputs)) {
                    $primary_key = "bill_detail_id";
                    for ($i = $index; $i < count($inputs); $i ++) {
                        $model = $loader->loadModel("BillDetail");
                        $model->bill_id = $bill->bill_id;
                        foreach ($inputs[$i] as $key => $value) {
                            if (!empty($this->key_prefix)) {
                                if (substr($key, 0, strlen($this->key_prefix)) == $this->key_prefix) {
                                    $key = preg_replace("/^" . $this->key_prefix . "/", "", $key);
                                    $model->$key = $value;
                                }
                            } else {
                                $model->$key = $value;
                            }
                        }
                        $model->save();
                        if (!empty($this->key_prefix)) {
                            $inputs[$i][$this->key_prefix . $primary_key] = $model->$primary_key;
                        } else {
                            $inputs[$i][$primary_key] = $model->$primary_key;
                        }
                    }
                }
                $post->set("details", $inputs);

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);

                // トランザクションの開始
                $connection = Vizualizer_Database_Factory::begin("trade");

                // 登録後に再計算を実施
                $bill->calculate();

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);

                // トランザクションの開始
                $connection = Vizualizer_Database_Factory::begin("trade");

                // 登録後に分割可能な場合は分割を実施
                $bill->split();

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }
    }
}
