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

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];
        $sistemas = ['Norte', 'Nordeste', 'Sudeste', 'Sul', 'Centro-Oeste'];

        $rowData = array_slice($this->util->import(6, $sheet, $file, 9999, 0), 2, 5);

        foreach ($rowData as $key => $array){
            $dataSistema[] = array_combine($meses, array_slice($array, 1));
        }
        $data = array_combine($sistemas, $dataSistema);

        return $data;
    }

    public function epeConsSubsist($file, $sheet)
    {
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];
        $sistemas = ['Sistemas Isolados', 'Norte', 'Nordeste', 'Sudeste/Centro-Oeste', 'Sul'];

        $rowData = array_slice($this->util->import(6, $sheet, $file, 9999, 0), 8, 5);

        foreach ($rowData as $key => $array){
            $dataSistema[] = array_combine($meses, array_slice($array, 1));
        }
        $data = array_combine($sistemas, $dataSistema);

        return $data;
    }

    public function epe_historico($file, $sheet)
    {
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Total'];

        $data = [];
        $indice = '';
        $ano = '';
        $rowData = $this->util->import(7, $sheet, $file);

        foreach ($rowData as $key => $info) {

            if (!array_values($rowData[$key])[1]) {
                if ($rowData[$key]['total_residencial'] &&
                    stripos($rowData[$key]['total_residencial'], '*') === false)
                {
                    $indice = $rowData[$key]['total_residencial'];
                }
            }
            if (!$rowData[$key]['total_residencial'] &&
                $rowData[$key]['total_residencial'] !== '') {
                if (is_numeric(preg_replace('(\*)', '', array_values($rowData[$key])[1])))
                {
                    $ano = preg_replace('(\*)', '', array_values($rowData[$key])[1]);
                }
            }

            if (array_values($rowData[$key])[2] &&
                array_values($rowData[$key])[2] !== 'FEV') {
                $data[$ano][$indice][$rowData[$key]['total_residencial']] = array_combine($meses, $this->util->formata_valores(array_slice($rowData[$key], 1, 13)));
            }
            unset($data['']);
        }

        return $data;
    }

}