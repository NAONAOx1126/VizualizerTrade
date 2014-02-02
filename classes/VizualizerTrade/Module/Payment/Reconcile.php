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
 * 入金消し込みを行う。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Payment_Reconcile extends Vizualizer_Plugin_Module_Save
{

    function execute($params)
    {
        $post = Vizualizer::request();
        if ($post["reconcile"]) {
            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("trade");

            try {
                // 取引データを取得し、保存する。
                $loader = new Vizualizer_Plugin("Trade");
                $reconcile = $loader->loadModel("Reconcile");
                $bill = $loader->loadModel("Bill");
                $bill->findByPrimaryKey($post["bill_id"]);
                $payment = $loader->loadModel("Payment");
                $payment->findByPrimaryKey($post["payment_id"]);
                $reconcile->reconcile($bill, $payment);

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);

            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }
    }
}
