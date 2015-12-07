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
 * 取引分割設定のモデルです。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Model_Split extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("trade");
        parent::__construct($loader->loadTable("Splits"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $split_id 請求分割ID
     */
    public function findByPrimaryKey($split_id)
    {
        $this->findBy(array("split_id" => $split_id));
    }

    /**
     * 作業者IDと顧客IDでデータを取得する。
     *
     * @param $worker_operator_id 作業者ID
     * @param $customer_operator_id 顧客ID
     * @param $type_id 請求種別ID
     */
    public function findByWorkerCustomerType($worker_operator_id, $customer_operator_id, $type_id){
        $this->findBy(array("worker_operator_id" => $worker_operator_id, "customer_operator_id" => $customer_operator_id, "type_id" => $type_id));
    }

    /**
     * 請求IDでデータを取得する。
     *
     * @param $bill_id 請求ID
     */
    public function findAllByBill($bill_id)
    {
        return $this->findAllBy(array("bill_id" => $bill_id));
    }

    /**
     * 作業者IDでデータを取得する。
     *
     * @param $worker_operator_id 作業者ID
     */
    public function findAllByWorker($worker_operator_id)
    {
        return $this->findAllBy(array("worker_operator_id" => $worker_operator_id));
    }


    /**
     * 窓口IDでデータを取得する。
     *
     * @param $contact_operator_id コード
     */
    public function findAllByContact($contact_operator_id)
    {
        return $this->findAllBy(array("contact_operator_id" => $contact_operator_id));
    }

    /**
     * 顧客IDでデータを取得する。
     *
     * @param $customer_operator_id 役割コード
     */
    public function findAllByCustomer($customer_operator_id)
    {
        return $this->findAllBy(array("customer_operator_id" => $customer_operator_id));
    }

    /**
     * 請求種別IDでデータを取得する。
     *
     * @param $type_id 請求種別ID
     */
    public function findAllByType($type_id)
    {
        return $this->findAllBy(array("type_id" => $type_id));
    }

    /**
     * この取引の作業者を取得する。
     *
     * @return 作業者
     */
    public function worker()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->setIgnoreOperator(true);
        $companyOperator->findByPrimaryKey($this->worker_operator_id);
        return $companyOperator;
    }

    /**
     * この取引の窓口を取得する。
     *
     * @return 窓口
     */
    public function contact()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->setIgnoreOperator(true);
        $companyOperator->findByPrimaryKey($this->contact_operator_id);
        return $companyOperator;
    }

    /**
     * この取引の顧客を取得する。
     *
     * @return 顧客
     */
    public function customer()
    {
        $loader = new Vizualizer_Plugin("admin");
        $companyOperator = $loader->loadModel("CompanyOperator");
        $companyOperator->setIgnoreOperator(true);
        $companyOperator->findByPrimaryKey($this->customer_operator_id);
        return $companyOperator;
    }

    /**
     * この取引の取引種別を取得する。
     *
     * @return 顧客
     */
    public function type()
    {
        $loader = new Vizualizer_Plugin("trade");
        $type = $loader->loadModel("Type");
        $type->findByPrimaryKey($this->type_id);
        return $type;
    }
}
