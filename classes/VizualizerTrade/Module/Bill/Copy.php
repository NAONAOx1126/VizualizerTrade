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
class VizualizerTrade_Module_Bill_Copy extends Vizualizer_Plugin_Module_Save
{

    function execute($params)
    {
        $post = Vizualizer::request();
        try {
            $loader = new Vizualizer_Plugin("Trade");
            $model = $loader->loadModel("Bill");
            if ($post["bill_id"] > 0) {
                $model->findByPrimaryKey($post["bill_id"]);
            }
            $newModel = $model->copy();

            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("trade");

            // 請求日、入金予定日、入金額などを削除
            $newModel->quotation_id = null;
            $newModel->billing_date = null;
            $newModel->next_billing_date = null;
            $newModel->payment_date = null;
            $newModel->complete_date = null;
            if ($newModel->trade_status > 4) {
                $newModel->trade_status = 4;
            }
            $newModel->save();
            Vizualizer_Database_Factory::commit($connection);

            $post->set("bill_id", $newModel->bill_id);

            // 登録後に再計算を実施
            $model->calculate();
        } catch (Exception $e) {
            Vizualizer_Database_Factory::rollback($connection);
            throw new Vizualizer_Exception_Database($e);
        }
    }
}
