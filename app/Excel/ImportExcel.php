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

class ImportExcel
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


/////////////////////////////////////////////////////////////////////////////////////CCEE/////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////ONS/////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////EPE/////////////////////////////////////////////////////////////////////////////////////


    public function epeConsTotal($file, $sheet, $startRow, $takeRows, $date)
    {


    }


/////////////////////////////////////////////////////////////////////////////////////RDH/////////////////////////////////////////////////////////////////////////////////////


}
