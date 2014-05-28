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
 * 請求情報のデータをExcel形式でダウンロードする。
 *
 * @package VizualizerTrade
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerTrade_Module_Bill_Excel_Download extends Vizualizer_Plugin_Module_DownloadExcel
{

    protected function process($params, $book)
    {
        // 最初に作成されている1番目のシートを選択し、シート取得する。
        $book->setActiveSheetIndex(0);
        $sheet = $book->getActiveSheet();

        // フォントを設定する。
        $sheet->getDefaultStyle()->getFont()->setName('ＭＳ Ｐゴシック');
        $sheet->getDefaultStyle()->getFont()->setSize(11);

        // シートのタイトルを設定
        $sheet->setTitle($params->get("title", "請求リスト"));
        $sheet->setCellValue('A1', $params->get("title", "請求リスト"));

        // 集計表のタイトルを設定
        $sheet->setCellValue('A2', "請求年月");
        $sheet->setCellValue('B2', "入金予定年月");
        $sheet->setCellValue('C2', "入金日");
        $sheet->setCellValue('D2', "請求先名称");
        $sheet->setCellValue('E2', "請求元名称");
        $sheet->setCellValue('F2', "案件名");
        $sheet->setCellValue('G2', "請求金額");
        $sheet->setCellValue('H2', "入金金額");
        $sheet->getStyle('A2:H2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        // 集計表の幅を設定
        $sheet->getColumnDimension('A')->setWidth(13);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(13);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(38);
        $sheet->getColumnDimension('F')->setWidth(65);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);

        // 集計表のデータを設定
        $attr = Vizualizer::attr();
        $bills = $attr[$params->get("data", "bills")];
        $line = 2;
        foreach($bills as $bill){
            $line ++;
            if(!empty($bill->billing_date)){
                $sheet->setCellValue('A'.$line, date("Y年m月", strtotime($bill->billing_date)));
            }
            if(!empty($bill->payment_date)){
                $sheet->setCellValue('B'.$line, date("Y年m月", strtotime($bill->payment_date)));
            }
            if(!empty($bill->complete_date)){
                $sheet->setCellValue('C'.$line, date("Y年m月d日", strtotime($bill->complete_date)));
            }
            $sheet->setCellValue('D'.$line, $bill->worker()->company()->company_name." ".$bill->worker()->operator_name);
            $sheet->setCellValue('E'.$line, $bill->customer()->company()->company_name." ".$bill->customer()->operator_name);
            $sheet->setCellValue('F'.$line, $bill->bill_name);
            $sheet->setCellValue('G'.$line, $bill->total);
            $sheet->getStyleByColumnAndRow(7, $line)->getNumberFormat()->setFormatCode("¥#,##0;¥-#,##0");
            $sheet->setCellValue('H'.$line, $bill->payment_total);
            $sheet->getStyleByColumnAndRow(8, $line)->getNumberFormat()->setFormatCode("¥#,##0;¥-#,##0");
            $sheet->getStyle('A'.$line.':H'.$line)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        }

        // 合計データを設定
        $line ++;
        $sheet->setCellValue('F'.$line, "合計金額");
        $sheet->setCellValue('G'.$line, "= SUM(G3:G".($line - 1).")");
        $sheet->getStyleByColumnAndRow(7, $line)->getNumberFormat()->setFormatCode("¥#,##0;¥-#,##0");
        $sheet->setCellValue('H'.$line, "= SUM(H3:H".($line - 1).")");
        $sheet->getStyleByColumnAndRow(8, $line)->getNumberFormat()->setFormatCode("¥#,##0;¥-#,##0");
        $sheet->getStyle('F'.$line.':H'.$line)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);


        return $book;
    }

    function execute($params)
    {
        $this->executeImpl($params, "Trade");
    }
}
