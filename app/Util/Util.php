<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 30/04/18
 * Time: 14:26
 */

namespace Crawler\Util;

use Carbon\Carbon;
use Crawler\Model\ArangoDb;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use Ixudra\Curl\Facades\Curl;
use Maatwebsite\Excel\Excel;
use Crawler\Regex\RegexProtheus;



class Util
{
    private $arangoDb;
    private $excel;
    private $regexProtheus;

    public function __construct(ArangoDb $arangoDb,
                                Excel $excel,
                                RegexProtheus $regexProtheus)
    {
        $this->arangoDb = $arangoDb;
        $this->excel = $excel;
        $this->regexProtheus = $regexProtheus;
    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }

    public static function getDateIso()
    {
        $date = Carbon::now();
        return $date->format('Y-m-d');
    }

    public static function getDateBrSubDays($format,$day)
    {
        if($format == 'br')
        {
            $date = Carbon::now()->subDay($day);
            return $date->format('d-m-Y');
        }
        if($format == 'us')
        {
            $date = Carbon::now()->subDay($day);
            return $date->format('Y-m-d');
        }

    }

    public static function getMesAno($mes_ano)
    {
      $date = Carbon::createFromFormat('m/Y',$mes_ano);

      return $date->format('Y-m');
    }

    public function enviaArangoDB($fonte, $info, $date, $periodicidade, $dados)
    {
        if (isset($dados)) {

            try {
                if ($this->arangoDb->collectionHandler()->has($fonte)) {

                    $this->arangoDb->documment()->set($info, ['generated' => $date, $periodicidade => $dados]);
                    $this->arangoDb->documentHandler()->save($fonte, $this->arangoDb->documment());
                } else {
                    $this->arangoDb->collection()->setName($fonte);
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());

                    $this->arangoDb->documment()->set($info, ['generated' => $date, $periodicidade => $dados]);
                    $this->arangoDb->documentHandler()->save($fonte, $this->arangoDb->documment());
                }

            } catch
            (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }
        } else {
            return response()->json(['error' => 'Os dados não foram capturados']);
        }
    }

    public function download($url, $formato)
    {
        $download = Curl::to($url)
            ->setCookieJar('down')
            ->allowRedirect(true)
            ->withContentType('application/' . $formato)
            ->download('');
        return $download;
    }

    public function import($inicio, $sheet, $file, $fim = null, $skip = null)
    {
        $this->setConfigStartRow($inicio);
        $import = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use($fim, $skip) {
                $reader->limitRows($fim)
                       ->skipRows($skip);
            })
            ->get()
            ->toArray();

        return $import;
    }

    public function importCondicional($linha, $sheet, $file)
    {
        $data = [];
        $this->setConfigStartRow($linha);
        $import = $this->import($linha, $sheet, $file, $linha);

        $chave = array_keys($import[0])[0];

        while ($import[0][$chave]){
            $data[$linha] = $import;
            $linha++;

            $import = \Excel::selectSheetsByIndex($sheet)
                ->load($file, function ($reader) use($linha) {
                    $reader->limitRows($linha);
                })
                ->get()
                ->toArray();
        }
dd($data);
        return $import;
    }

    public  function celulaMesclada($array, $coluna, $numLinha)
    {
        foreach ($array as $key => $item)
        {
            if ($array[$key][$coluna] === null) {
                $array[$key][$coluna] = $array[$key - $numLinha][$coluna];
            }
        }
        return $array;
    }

    public  function diasAno($ano)
    {
        $diasAno = 365;
        if ($ano === 1){
            $diasAno = 366;
        }
        return $diasAno;
    }

    public function dateEdit($date)
    {
        $dataEdit = \DateTime::createFromFormat('d-m-y', $date);
        return $dataEdit->format('d/m/Y');
    }


    public function mesMMMportugues()
    {
        $mes = date('M');

        if ($mes === 'Jan'){
            return 'JAN';
        } elseif ($mes === 'Fev') {
            return 'FEV';
        } elseif ($mes === 'Mar') {
            return 'MAR';
        } elseif ($mes === 'Apr') {
            return 'ABR';
        } elseif ($mes === 'May') {
            return 'MAI';
        } elseif ($mes === 'Jun') {
            return 'JUN';
        } elseif ($mes === 'Jul') {
            return 'JUL';
        } elseif ($mes === 'Aug') {
            return 'AGO';
        } elseif ($mes === 'Sep') {
            return 'SET';
        } elseif ($mes === 'Oct') {
            return 'OUT';
        } elseif ($mes === 'Nov') {
            return 'NOV';
        } elseif ($mes === 'Dec') {
            return 'DEZ';
        }
    }

    public function mesMesportugues($mes)
    {

        if ($mes === '01'){
            return 'janeiro';
        } elseif ($mes === '02') {
            return 'fevereiro';
        } elseif ($mes === '03') {
            return 'marco';
        } elseif ($mes === '04') {
            return 'abril';
        } elseif ($mes === '05') {
            return 'maio';
        } elseif ($mes === '06') {
            return 'junho';
        } elseif ($mes === '07') {
            return 'julho';
        } elseif ($mes === '08') {
            return 'agosto';
        } elseif ($mes === '09') {
            return 'setembro';
        } elseif ($mes === '10') {
            return 'outubro';
        } elseif ($mes === '11') {
            return 'novembro';
        } elseif ($mes === '12') {
            return 'dezembro';
        }
    }

    public function mesMesXXportugues($mes)
    {

        if ($mes === 'Janeiro'){
            return 1;
        } elseif ($mes === 'Fevereiro') {
            return 2;
        } elseif ($mes === 'Março') {
            return 3;
        } elseif ($mes === 'Abril') {
            return 4;
        } elseif ($mes === 'Maio') {
            return 5;
        } elseif ($mes === 'Junho') {
            return 6;
        } elseif ($mes === 'Julho') {
            return 7;
        } elseif ($mes === 'Agosto') {
            return 8;
        } elseif ($mes === 'Setembro') {
            return 9;
        } elseif ($mes === 'Outubro') {
            return 10;
        } elseif ($mes === 'Novembro') {
            return 11;
        } elseif ($mes === 'Dezembro') {
            return 12;
        }
    }

    public function formata_valores_mwh($arr)
    {
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::parse()->daysInMonth,
            'Março' => 31,
            'Abril' => 30,
            'Maio' => 31,
            'Junho' => 30,
            'Julho' => 31,
            'Agosto' => 31,
            'Setembro' => 30,
            'Outubro' => 31,
            'Novembro' => 30,
            'Dezembro' => 31
        ];

        $arrPatamar = [];
        array_walk($arr, function ($value, $key) use ($daysInMonths, &$arrPatamar) {
            $total = $value;
            if (!is_null($value)) {
                $total_round = round($value * 24 * $daysInMonths[$key], 3);
                $total = number_format($total_round, 10, ",", ".");
            }
            $arrPatamar[$key] = $total;
        });

        return $arrPatamar;
    }

    public function formata_valores_mwmed($arr)
    {
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::parse()->daysInMonth,
            'Março' => 31,
            'Abril' => 30,
            'Maio' => 31,
            'Junho' => 30,
            'Julho' => 31,
            'Agosto' => 31,
            'Setembro' => 30,
            'Outubro' => 31,
            'Novembro' => 30,
            'Dezembro' => 31
        ];

        $arrPatamar = [];
        array_walk($arr, function ($value, $key) use ($daysInMonths, &$arrPatamar) {
            $total = $value;
            if (!is_null($value)) {
                $total_round = round($value /(24 * $daysInMonths[$key]), 3);
                $total = number_format($total_round, 10, ",", ".");
            }
            $arrPatamar[$key] = $total;
        });

        return $arrPatamar;
    }

    public function formata_valores ($arr)
    {
        $arrPatamar = [];
        array_walk($arr, function ($value, $key) use (&$arrPatamar) {
            $total = $value;
            if (!is_null($value)) {
                $total = number_format((float)$value, 10, ",", ".");
            }
            $arrPatamar[$key] = $total;
        });

        return $arrPatamar;
    }

}