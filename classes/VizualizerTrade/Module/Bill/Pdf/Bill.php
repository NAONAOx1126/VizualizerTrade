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
        $this->setFontByTTF(VIZUALIZER_SITE_ROOT."/font/GenShinGothic-P-Regular.ttf");

        foreach($billIds as $billId){
            // 帳票に使うデータを取得
            $bill = $this->getData("Trade", "Bill", $billId);
            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("trade");
            try {
                if(empty($bill->billing_date) || $bill->billing_date == "0000-00-00"){
                    $bill->billing_date = date("Y-m-d");
                }
                if($bill->status_id < 6){
                    $bill->status_id = 6;
                }
                $bill->save();
                $bill->calcPaymentDate();
                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }

            $source = $bill->source();
            $sourceCompany = $source->company();
            $dest = $bill->dest();
            $destCompany = $dest->company();

            // ページの開始
            $this->startPage();

            // ロゴを貼付け
            if (!empty($sourceCompany->logo)) {
                $this->image(270, 32, $sourceCompany->logo, 200, 50);
            }

            // タイトルを描画
            $this->text(241, 248, 20, "御　請　求　書", true);

            // 帳票番号を描画
            $this->text(450, 88, 9, "No：".sprintf("%04d", $destCompany->company_id)."-".sprintf("%08d", $bill->quotation_id), true);

            // 作成日を描画
            $this->text(450, 100, 9, "ご請求日：".date("Y年m月d日", strtotime($bill->billing_date)), true);

            // 宛先欄を作成
            $text = "〒".$destCompany->zip1."-".$destCompany->zip2."\r\n\r\n";
            $text .= $destCompany->pref_name().$destCompany->address1."\r\n\r\n";
            if(!empty($destCompany->address2)){
                $text .= $destCompany->address2."\r\n\r\n";
            }
            $text .= $destCompany->company_name;
            if(!empty($dest->operator_name)){
                $text .= "\r\n\r\n\r\n\r\n".$dest->operator_name." 様";
            }else{
                 $text .= " 御中";
            }
            $this->boxtext(75, 56, 260, 120, 10, $text);

            // 差出人欄を作成
            $text = "〒".$sourceCompany->zip1."-".$sourceCompany->zip2."\r\n\r\n";
            $text .= $sourceCompany->pref_name().$sourceCompany->address1."\r\n\r\n";
            if(!empty($sourceCompany->address2)){
                $text .= $sourceCompany->address2."\r\n\r\n";
            }
            $text .= $sourceCompany->company_name;
            $text .= "\r\n\r\n";
            $text .= "電話：".$sourceCompany->tel1."-".$sourceCompany->tel2."-".$sourceCompany->tel3;
            if(!empty($source->operator_name)){
                $text .= "\r\n\r\n\r\n\r\n担当者：".$source->operator_name;
            }
            $text .= "\r\n\r\n\r\n\r\nお振込先：".$sourceCompany->bank_name." ".$sourceCompany->branch_name."\r\n\r\n";
            $text .= "　　　　　　　　".$sourceCompany->account_type." ".$sourceCompany->account_number." ".$sourceCompany->account_name."\r\n";
            $this->boxtext(339, 96, 260, 160, 10, $text);

                    // 合計金額を描画
            $this->text(35, 310, 20, "ご請求金額： ￥".number_format($bill->total)."-", true);

            // 印鑑入力欄を作成
            //$this->rect(491, 98, 50, 50, 0);
            // 印鑑画像を貼付け
            if (!empty($sourceCompany->stamp)) {
                $this->image(440, 132, $sourceCompany->stamp, 46, 46);
            }

            // 請求名を表示
            $this->text(35, 278, 20, "案件名：".$bill->trade_name, true);

            $this->line(35, 323, 555, 323, 1, array(3, 3));

            // 明細タイトル欄を作成
            $this->boxtext(35, 335, 320, 16, 10, "請　求　明　細", true, "center");
            $this->boxtext(355, 335, 80, 16, 10, "価　格", true, "center");
            $this->boxtext(435, 335, 40, 16, 10, "数　量", true, "center");
            $this->boxtext(475, 335, 80, 16, 10, "小　計", true, "center");
            $endOutput = false;
            $details = $bill->details();
            for($i = 0; $i < 17; $i ++){
                if($details->valid()){
                    $detail = $details->current();
                    $details->next();
                    $this->boxtext(35, 351 + 16 * $i, 320, 16, 10, $detail->product_name, true);
                    $this->boxtext(355, 351 + 16 * $i, 80, 16, 10, "￥".number_format($detail->price), true, "right");
                    $this->boxtext(435, 351 + 16 * $i, 40, 16, 10, number_format($detail->quantity), true, "right");
                    $this->boxtext(475, 351 + 16 * $i, 80, 16, 10, "￥".number_format($detail->price * $detail->quantity), true, "right");
                }else{
                    if(!$endOutput){
                        $this->boxtext(35, 351 + 16 * $i, 320, 16, 10, "以　下　余　白", true, "right");
                        $endOutput = true;
                    }else{
                        $this->boxtext(35, 351 + 16 * $i, 320, 16, 10, "", true);
                    }
                    $this->boxtext(355, 351 + 16 * $i, 80, 16, 10, "", true, "right");
                    $this->boxtext(435, 351 + 16 * $i, 40, 16, 10, "", true, "right");
                    $this->boxtext(475, 351 + 16 * $i, 80, 16, 10, "", true, "right");
                }
            }

            $this->line(35, 359 + 16 * $i, 555, 359 + 16 * $i, 1, array(3, 3));

            $this->boxtext(355, 351 + 16 * (++$i), 120, 16, 10, "小　計", true, "center");
            $this->boxtext(475, 351 + 16 * $i, 80, 16, 10, "￥".number_format($bill->subtotal), true, "right");
            $this->boxtext(355, 351 + 16 * (++$i), 120, 16, 10, "消　費　税", true, "center");
            $this->boxtext(475, 351 + 16 * ($i), 80, 16, 10, "￥".number_format($bill->tax), true, "right");
            if ($bill->discount > 0) {
                $this->boxtext(355, 351 + 16 * (++$i), 120, 16, 10, (!empty($bill->discount_title)?$bill->discount_title:"割引金額"), true, "center");
                $this->boxtext(475, 351 + 16 * ($i), 80, 16, 10, "￥".number_format($bill->discount), true, "right");
            }
            if ($bill->adjust > 0) {
                $this->boxtext(355, 351 + 16 * (++$i), 120, 16, 10, (!empty($bill->adjust_title)?$bill->adjust_title:"割引金額"), true, "center");
                $this->boxtext(475, 351 + 16 * ($i), 80, 16, 10, "￥".number_format($bill->adjust), true, "right");
            }
            $this->boxtext(355, 351 + 16 * (++$i), 120, 16, 10, "合　計　金　額", true, "center");
            $this->boxtext(475, 351 + 16 * ($i), 80, 16, 10, "￥".number_format($bill->total), true, "right");
            $this->rect(35, 364 + 16 * (++$i), 516, 440 - 16 * ($i), 1);
            $this->text(40, 379 + 16 * ($i), 10, "備考：\r\n\r\n".str_replace("\r\n", "\r\n\r\n", $bill->description));
        }

        // PDFを出力
        $this->output("Bill", $params->get("result", ""));
    }
}
