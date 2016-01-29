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
                $loader = new Vizualizer_Plugin("Trade");
                $model = $loader->loadModel("Bill");
                if ($post["bill_id"] > 0) {
                    $model->findByPrimaryKey($post["bill_id"]);
                }
                foreach ($post as $key => $value) {
                    $model->$key = $value;
                }
                // 取引データを取得し、保存する。
                $model->save();

                $details = $model->details();
                foreach($details as $detail) {
                    $detail->delete();
                }
                $details = $post["details"];
                foreach($details as $index => $data){
                    if(!empty($data["product_name"]) && is_numeric($data["price"]) && $data["quantity"] > 0){
                        $detail = $loader->loadModel("BillDetail");
                        $detail->bill_id = $model->bill_id;
                        foreach ($data as $key => $value) {
                            $detail->$key = $value;
                        }
                        $detail->save();
                        $details[$index]["bill_id"] = $detail->bill_id;
                        $details[$index]["bill_detail_id"] = $detail->bill_detail_id;
                    }
                }
                $post->set("details", $details);

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);

                $post->set("bill_id", $model->bill_id);

                // 登録後に再計算を実施
                $model->calculate();

                // 登録後に分割可能な場合は分割を実施
                $model->split();
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }
    }
}
