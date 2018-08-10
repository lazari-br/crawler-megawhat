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
use Crawler\Util\Util;

class ImportExcelEpe
{
    private $excel;
    private $startRow;
    private $util;


    public function __construct(Excel $excel,
                                Util $util)
    {
        $this->excel = $excel;
        $this->util= $util;

    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }

    public function epeConsReg($file, $sheet)
    {

        $indices = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];
        $sistemas = ['Norte', 'Nordeste', 'Sudeste', 'Sul', 'Centro-Oeste'];

        $format = function($n){
           if (!is_null($n)){
            return number_format($n, 3, ',', '.');
        }};

        $rowTotal = $this->util->import(6, $sheet, $file, 9999, 0);
        $formatTot = array_map($format, array_slice($rowTotal, 1, 13));
        $data['Total'] = array_combine($indices, $formatTot);

        foreach ($sistemas as $key => $sistema)
        {
            $row = $this->util->import(6, $sheet, $file, 9999, 2 + $key);
            $format = array_map($format, array_slice($row, 1, 13));
            $data[$sistema] = array_combine($indices, $format);
        }

        return $data;
    }

    public function epeConsSubsist($file, $sheet)
    {
        $indices = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];
        $sistemas = ['Sistemas Isolados', 'Norte', 'Nordeste', 'Sudeste/Centro-Oeste', 'Sul'];

        $format = function($n){
            if (!is_null($n)){
                return number_format($n, 3, ',', '.');
            }};

        $rowTotal = $this->util->import(6, $sheet, $file, 9999, 0);
        $formatTot = array_map($format, array_slice($rowTotal, 1, 13));
        $data['Total'] = array_combine($indices, $formatTot);

        foreach ($sistemas as $key => $sistema)
        {
            $row = $this->util->import(6, $sheet, $file, 9999, 8 + $key);
            $format = array_map($format, array_slice($row, 1, 13));
            $data[$sistema] = array_combine($indices, $format);
        }

        return $data;
    }

}