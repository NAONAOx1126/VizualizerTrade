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
 * 入金のデータをアップロードする。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Payment_Upload extends Vizualizer_Plugin_Module_Upload
{

    protected function checkTitle($data)
    {
        // 暫定的に特にチェックしない
        return true;
    }

    protected function check($line, $model, $data)
    {
        $post = Vizualizer::request();
        if($data[0] == "2"){
            $model->company_id = $post["company_id"];
            $model->payment_date = date("Y-m-d", strtotime(str_replace(".", "-", $data[1])));
            $model->bank_name = $data[5];
            $model->branch_name = $data[6];
            $model->payment_code = $data[7];
            $model->payment_name = $data[4];
            $model->payment_total = $data[9];
            return $model;
        }
        return null;
    }

    function execute($params)
    {
        $this->executeImpl($params, "Trade", "Payment", "payment");
    }
}
