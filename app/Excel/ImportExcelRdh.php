<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09/05/18
 * Time: 13:57
 */

namespace Crawler\Excel;

use Carbon\Carbon;
use Maatwebsite\Excel\Excel;

class ImportExcelRdh
{
    private $excel;
    private $startRow;


    public function __construct(Excel $excel)
    {
        $this->excel = $excel;

    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }

    public function rdhEna($file, $sheet)
    {

        $subs = function ($n){
            return str_replace("_", " até ", $n);
        };
        $monta = function($n){
            return substr($n, 0, 2).'/'.substr($n, 2, 2).substr($n, 4, 8).'/'.substr($n, 12, 2);
        };

        //Datas
        $this->setConfigStartRow(6);
        $rowDataSemanas = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(6);
            })
            ->first()
            ->toArray();

        $dataSemanas= array_slice($rowDataSemanas, 1, 4);
        $rowSemanas = array_keys($dataSemanas);

        $periodo = array_map($monta, array_map($subs, $rowSemanas));

        //Sudeste e Centro-Oeste
        $this->setConfigStartRow(6);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(7);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['SE/CO'] ['MWmed'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(6);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(8)
                    ->skipRows(1);;
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['SE/CO'] ['% MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(6);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(9)
                    ->skipRows(2);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['SE/CO'] ['Armaz % MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(6);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(10)
                    ->skipRows(3);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['SE/CO'] ['Queda MWmed'] = array_combine($periodo, $valores);

        //Sul
        $this->setConfigStartRow(14);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(15);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['S'] ['MWmed'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(14);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(16)
                    ->skipRows(1);;
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['S'] ['% MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(14);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(17)
                    ->skipRows(2);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['S'] ['Armaz % MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(14);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(18)
                    ->skipRows(3);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['S'] ['Queda MWmed'] = array_combine($periodo, $valores);

        //Nordeste
        $this->setConfigStartRow(22);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(23);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['NE'] ['MWmed'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(22);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(24)
                    ->skipRows(1);;
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['NE'] ['% MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(22);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(25)
                    ->skipRows(2);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['NE'] ['Armaz % MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(22);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(26)
                    ->skipRows(3);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['NE'] ['Queda MWmed'] = array_combine($periodo, $valores);

        //Norte
        $this->setConfigStartRow(30);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(31);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['N'] ['MWmed'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(30);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(32)
                    ->skipRows(1);;
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['N'] ['% MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(30);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(33)
                    ->skipRows(2);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['N'] ['Armaz % MLT'] = array_combine($periodo, $valores);

        $this->setConfigStartRow(30);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(34)
                    ->skipRows(3);
            })
            ->first()
            ->toArray();

        $valores = array_slice($rowData, 1, 4);
        $data ['N'] ['Queda MWmed'] = array_combine($periodo, $valores);

        return $data;
    }

}