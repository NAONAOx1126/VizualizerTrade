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
 * 代理案件のデータをアップロードする。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Agent_Upload extends Vizualizer_Plugin_Module_UploadExcel
{

    protected function process($params, $book)
    {
        if ($params->check("label")){
            // 当月のシートを取得
            $sheet = $book->getSheetByName(date("Ym"));

            // シート内のラベルテキストを取得する。
            $label = $sheet->getCell($params->get("label"))->getValue();

            // 行データを取得していく
            $trow = $params->get("title", 2);
            $row = $params->get("start", 3);
            $result = array();
            while ($row <= $sheet->getHighestRow()) {
                $data = array();
                $data["agent_type"] = $label;
                $data["agent_month"] = $sheet->getTitle();
                switch ($label) {
                    case "SEO":
                        $data["client_name"] = $this->getValue($sheet, "B" . $row);
                        $data["agent_name"] = $this->getValue($sheet, "D" . $row);
                        $data["cost1_name"] = $this->getValue($sheet, "F" . $trow);
                        $data["cost1_price"] = round($this->getValue($sheet, "F" . $row));
                        $data["worker_name"] = str_replace("利益", "", $this->getValue($sheet, "H" . $trow));
                        $data["worker_profit"] = round($this->getValue($sheet, "H" . $row));
                        $data["contact_name"] = str_replace("利益", "", $this->getValue($sheet, "I" . $trow));
                        $data["contact_profit"] = round($this->getValue($sheet, "I" . $row));
                        $data["total"] = round($this->getValue($sheet, "E" . $row));
                        break;
                    case "Listing":
                        $data["client_name"] = $this->getValue($sheet, "B" . $row);
                        $data["agent_name"] = $this->getValue($sheet, "F" . $row);
                        $data["cost1_name"] = $this->getValue($sheet, "H" . $trow);
                        $data["cost1_price"] = round($this->getValue($sheet, "H" . $row));
                        $data["worker_name"] = str_replace("利益", "", $this->getValue($sheet, "J" . $trow));
                        $data["worker_profit"] = round($this->getValue($sheet, "J" . $row));
                        $data["contact_name"] = str_replace("利益", "", $this->getValue($sheet, "K" . $trow));
                        $data["contact_profit"] = round($this->getValue($sheet, "K" . $row));
                        $data["total"] = round($this->getValue($sheet, "G" . $row));
                        break;
                    case "逆SEO":
                        $data["client_name"] = $this->getValue($sheet, "B" . $row);
                        $data["agent_name"] = $this->getValue($sheet, "D" . $row);
                        $data["cost1_name"] = $this->getValue($sheet, "F" . $trow);
                        $data["cost1_price"] = round($this->getValue($sheet, "F" . $row));
                        $data["cost2_name"] = $this->getValue($sheet, "G" . $trow);
                        $data["cost2_price"] = round($this->getValue($sheet, "G" . $row));
                        $data["cost3_name"] = $this->getValue($sheet, "I" . $trow);
                        $data["cost3_price"] = round($this->getValue($sheet, "I" . $row));
                        $data["worker_name"] = str_replace("利益", "", $this->getValue($sheet, "J" . $trow));
                        $data["worker_profit"] = round($this->getValue($sheet, "J" . $row));
                        $data["contact_name"] = str_replace("利益", "", $this->getValue($sheet, "K" . $trow));
                        $data["contact_profit"] = round($this->getValue($sheet, "K" . $row));
                        $data["total"] = round($this->getValue($sheet, "E" . $row));
                        break;
                }
                $row ++;
                if(!empty($data["client_name"]) && $data["total"] > 0){
                    $result[] = $data;
                }
            }
        }
        return $result;
    }

    function execute($params)
    {
        $this->executeImpl($params, "Trade", "Agent", "agent");
    }
}
