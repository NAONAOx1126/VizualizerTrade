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
 * 請求明細のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_QuotationDetail extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("QuotationDetails"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $quotation_detail_id 見積明細ID
     */
    public function findByPrimaryKey($quotation_detail_id)
    {
        $this->findBy(array("quotation_detail_id" => $quotation_detail_id));
    }

    /**
     * 請求IDでデータを取得する。
     *
     * @param $quotation_id 見積ID
     */
    public function findAllByQuotation($quotation_id){
        return $this->findAllBy(array("quotation_id" => $quotation_id));
    }

    /**
     * この請求明細の見積を取得する。
     *
     * @return 見積
     */
    public function quotation()
    {
        $loader = new Vizualizer_Plugin("trade");
        $quotation = $loader->loadModel("Quotation");
        $quotation->findByPrimaryKey($this->quotation_id);
        return $quotation;
    }
}
