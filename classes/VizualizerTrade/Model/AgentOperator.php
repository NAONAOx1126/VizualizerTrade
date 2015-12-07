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
 * 代理担当者のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_AgentOperator extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("AgentOperators"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $agent_operator_id 代理担当者ID
     */
    public function findByPrimaryKey($agent_operator_id)
    {
        $this->findBy(array("agent_operator_id" => $agent_operator_id));
    }

    /**
     * 担当者IDでデータを取得する
     *
     * @param $operator_id 担当者Id
     */
    public function findByOperatorId($operator_id)
    {
        $this->findBy(array("operator_id" => $operator_id));
    }

    /**
     * 代理担当者名でデータを取得する
     *
     * @param $agent_opeartor_name 代理担当者名
     */
    public function findByAgentOperatorName($agent_operator_name)
    {
        $this->findBy(array("agent_operator_name" => $agent_operator_name));
    }

    /**
     * 代理担当者の担当者を取得する。
     *
     * @return 代理担当者
     */
    public function operator()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->setIgnoreOperator(true);
        $companyOperator->findByPrimaryKey($this->operator_id);
        return $companyOperator;
    }
}
