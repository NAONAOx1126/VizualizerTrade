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
 * 請求書のPDFを出力する。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Bill_Pdf_Bill extends Vizualizer_Plugin_Module_Pdf
{

    function execute($params)
    {
        $post = Vizualizer::request();
        if(!is_array($post["bill_ids"])){
            $billIds = explode(",", $post["bill_ids"]);
        }else{
            $billIds = $post["bill_ids"];
        }

        $this->startDocument();

        foreach($billIds as $billId){
            // 帳票に使うデータを取得
            $bill = $this->getData("Admin", "Bill", $billId);
            $worker = $bill->worker();
            $workerCompany = $worker->company();
            $customer = $bill->customer();
            $customerCompany = $customer->company();

            // ページの開始
            $this->startPage();

            // ロゴを貼付け
            $this->image(15, 20, $workerCompany->logo, 200, 50);

            // タイトルを描画
            $this->text(241, 35, 20, "請　　求　　書", true);

            // 帳票番号を描画
            $this->text(450, 56, 9, "No：".sprintf("%04d", $customerCompany->company_id)."-".sprintf("%08d", $bill->bill_id), true);

            // 作成日を描画
            $this->text(450, 68, 9, "ご請求日：".date("Y年m月d日", strtotime($bill->billing_date)), true);
            $this->text(450, 80, 9, "ご入金期限：".date("Y年m月d日", strtotime($bill->payment_date)), true);

            // 宛先欄を作成
            $text = "〒".$customerCompany->zip1."-".$customerCompany->zip2."\r\n";
            $text .= $customerCompany->pref_name().$customerCompany->address1."\r\n";
            if(!empty($customerCompany->address2)){
                $text .= $customerCompany->address2."\r\n";
            }
            $text .= $customerCompany->company_name;
            if(!empty($customer->operator_name)){
                $text .= "\r\n\r\n".$customer->operator_name." 様";
            }else{
                 $text .= " 御中";
            }
            $text .= "\r\n\r\n";
            $text .= "電話番号：".$customerCompany->tel1."-".$customerCompany->tel2."-".$customerCompany->tel3;
            $this->boxtext(35, 70, 260, 120, 10, $text);

            // 差出人欄を作成
            $text = "〒".$workerCompany->zip1."-".$workerCompany->zip2."\r\n";
            $text .= $workerCompany->pref_name().$workerCompany->address1."\r\n";
            if(!empty($workerCompany->address2)){
                $text .= $workerCompany->address2."\r\n";
            }
            $text .= $workerCompany->company_name;
            if(!empty($worker->operator_name)){
                $text .= "\r\n\r\n担当者：".$worker->operator_name;
            }
            $text .= "\r\n\r\n";
            $text .= "電話番号：".$workerCompany->tel1."-".$workerCompany->tel2."-".$workerCompany->tel3."\r\n\r\n";
            $text .= "お振込先：".$workerCompany->bank_name." ".$workerCompany->branch_name."\r\n";
            $text .= "　　　　　　　　".$workerCompany->account_type." ".$workerCompany->account_number." ".$workerCompany->account_name."\r\n";
            $this->boxtext(299, 80, 260, 160, 10, $text);

            // 合計金額を描画
            $this->text(35, 230, 20, "ご請求金額： ￥".number_format($bill->total)."-", true);

            // 印鑑入力欄を作成
            //$this->rect(491, 98, 50, 50, 0);
            // 印鑑画像を貼付け
            $this->image(493, 100, $workerCompany->stamp, 46, 46);

            // 明細タイトル欄を作成
            $this->boxtext(35, 255, 316, 12, 10, "商　品　名", true, "center");
            $this->boxtext(355, 255, 76, 12, 10, "価　格", true, "center");
            $this->boxtext(435, 255, 36, 12, 10, "数　量", true, "center");
            $this->boxtext(475, 255, 76, 12, 10, "小　計", true, "center");
            $endOutput = false;
            $details = $bill->details();
            for($i = 0; $i < 25; $i ++){
                if($details->valid()){
                    $detail = $details->current();
                    $details->next();
                    $this->boxtext(35, 271 + 16 * $i, 316, 12, 10, $detail->bill_detail_name, true);
                    $this->boxtext(355, 271 + 16 * $i, 76, 12, 10, "￥".number_format($detail->price), true, "right");
                    $this->boxtext(435, 271 + 16 * $i, 36, 12, 10, number_format($detail->quantity), true, "right");
                    $this->boxtext(475, 271 + 16 * $i, 76, 12, 10, "￥".number_format($detail->price * $detail->quantity), true, "right");
                }else{
                    if(!$endOutput){
                        $this->boxtext(35, 271 + 16 * $i, 316, 12, 10, "以　下　余　白", true, "right");
                        $endOutput = true;
                    }else{
                        $this->boxtext(35, 271 + 16 * $i, 316, 12, 10, "", true);
                    }
                    $this->boxtext(355, 271 + 16 * $i, 76, 12, 10, "", true, "right");
                    $this->boxtext(435, 271 + 16 * $i, 36, 12, 10, "", true, "right");
                    $this->boxtext(475, 271 + 16 * $i, 76, 12, 10, "", true, "right");
                }
            }
            $this->boxtext(355, 271 + 16 * $i, 116, 12, 10, "小　計", true, "center");
            $this->boxtext(475, 271 + 16 * $i, 76, 12, 10, "￥".number_format($bill->subtotal), true, "right");
            $this->boxtext(355, 271 + 16 * ($i + 1), 116, 12, 10, "消　費　税", true, "center");
            $this->boxtext(475, 271 + 16 * ($i + 1), 76, 12, 10, "￥".number_format($bill->tax), true, "right");
            $this->boxtext(355, 271 + 16 * ($i + 2), 116, 12, 10, "合　計　金　額", true, "center");
            $this->boxtext(475, 271 + 16 * ($i + 2), 76, 12, 10, "￥".number_format($bill->total), true, "right");
            $this->boxtext(35, 724, 516, 100, 10, "備考：\r\n".$bill->description, true);
        }

        // PDFを出力
        $this->output("Bill", $params->get("result", ""));
    }
}
