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

class ImportExcelEpe
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

    public function epeConsReg($file, $sheet)
    {

        $indices = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];


        $format = function($n){
           if (!is_null($n)){
            return number_format($n, 3, ',', '.');
        }};

        $this->setConfigStartRow(6);

        //Total
        $rowTotal = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(0);
            })
            ->first()
            ->toArray();

        $formatTot = array_map($format, array_slice($rowTotal, 1, 13));

        $data['Total'] = array_combine($indices, $formatTot);

        //Norte
        $rowN = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(2);
            })
            ->first()
            ->toArray();

        $formatN = array_map($format, array_slice($rowN, 1, 13));

        $data['Norte'] = array_combine($indices, $formatN);

        //Nordeste
        $rowNE = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(3);
            })
            ->first()
            ->toArray();

        $formatNE = array_map($format, array_slice($rowNE, 1, 13));

        $data['Nordeste'] = array_combine($indices, $formatNE);

        //Sudeste
        $rowSE = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(4);
            })
            ->first()
            ->toArray();

        $formatSE = array_map($format, array_slice($rowSE, 1, 13));

        $data['Sudeste'] = array_combine($indices, $formatSE);

        //Sul
        $rowS = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(5);
            })
            ->first()
            ->toArray();

        $formatS = array_map($format, array_slice($rowS, 1, 13));

        $data['Sul']= array_combine($indices, $formatS);

        //Centro-Oeste
        $rowCO = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(6);
            })
            ->first()
            ->toArray();

        $formatCO = array_map($format, array_slice($rowCO, 1, 13));

        $data['Centro-Oeste'] = array_combine($indices, $formatCO);

return $data;
    }

    public function epeConsSubsist($file, $sheet)
    {

        $indices = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];


        $format = function($n){
            if (!is_null($n)){
                return number_format($n, 3, ',', '.');
            }};

        $this->setConfigStartRow(6);

        //Total
        $rowTotal = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(0);
            })
            ->first()
            ->toArray();

        $formatTot = array_map($format, array_slice($rowTotal, 1, 13));

        $data['Total'] = array_combine($indices, $formatTot);

        //Sistemas Isolados
        $rowIsol = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(8);
            })
            ->first()
            ->toArray();

        $formatIsol = array_map($format, array_slice($rowIsol, 1, 13));

        $data['Sistemas Isolados'] = array_combine($indices, $formatIsol);

        //Norte
        $rowN = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(9);
            })
            ->first()
            ->toArray();

        $formatN = array_map($format, array_slice($rowN, 1, 13));

        $data['Norte'] = array_combine($indices, $formatN);

        //Nordeste
        $rowNE = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(10);
            })
            ->first()
            ->toArray();

        $formatNE = array_map($format, array_slice($rowNE, 1, 13));

        $data['Nordeste'] = array_combine($indices, $formatNE);

        //Sudeste
        $rowSE = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(11);
            })
            ->first()
            ->toArray();

        $formatSE = array_map($format, array_slice($rowSE, 1, 13));

        $data['Sudeste/Centro-Oeste']= array_combine($indices, $formatSE);

        //Sul
        $rowS = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->skiprows(12);
            })
            ->first()
            ->toArray();

        $formatS = array_map($format, array_slice($rowS, 1, 13));

        $data['Sul'] = array_combine($indices, $formatS);

return $data;

    }

}