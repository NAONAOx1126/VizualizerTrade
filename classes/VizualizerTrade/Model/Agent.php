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
 * 代理案件のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_Agent extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("Agents"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $agent_id 代理案件ID
     */
    public function findByPrimaryKey($agent_id)
    {
        $this->findBy(array("agent_id" => $agent_id));
    }

    /**
     * クライアント担当者の担当者情報を取得する
     *
     * @return クライアント担当者
     */
    public function client()
    {
        $loader = new Vizualizer_Plugin("trade");
        $agentOperator = $loader->loadModel("AgentOperator");
        $agentOperator->findByAgentOperatorName($this->client_name);
        if ($agentOperator->agent_operator_id > 0) {
            return $agentOperator->operator();
        } else {
            $loader = new Vizualizer_Plugin("admin");
            $companyOperator = $loader->loadModel("CompanyOperator");
        }
    }

    /**
     * 作業担当者の担当者情報を取得する
     *
     * @return 作業担当者
     */
    public function worker()
    {
        $loader = new Vizualizer_Plugin("trade");
        $agentOperator = $loader->loadModel("AgentOperator");
        $agentOperator->findByAgentOperatorName($this->worker_name);
        if ($agentOperator->agent_operator_id > 0) {
            return $agentOperator->operator();
        } else {
            $loader = new Vizualizer_Plugin("admin");
            $companyOperator = $loader->loadModel("CompanyOperator");
        }
    }

    /**
     * 窓口担当者の担当者情報を取得する
     *
     * @return 窓口担当者
     */
    public function contact()
    {
        $loader = new Vizualizer_Plugin("trade");
        $agentOperator = $loader->loadModel("AgentOperator");
        $agentOperator->findByAgentOperatorName($this->contact_name);
        if ($agentOperator->agent_operator_id > 0) {
            return $agentOperator->operator();
        } else {
            $loader = new Vizualizer_Plugin("admin");
            $companyOperator = $loader->loadModel("CompanyOperator");
        }
    }
}
