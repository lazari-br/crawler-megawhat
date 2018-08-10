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

    public function enviaBanco($fonte, $info, $date, $dados)
    {
        try {
            if ($this->arangoDb->collectionHandler()->has($fonte)) {

                $this->arangoDb->documment()->set($info, [$date => $dados]);
                $this->arangoDb->documentHandler()->save($fonte, $this->arangoDb->documment());
            } else {
                $this->arangoDb->collection()->setName($fonte);
                $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());

                $this->arangoDb->documment()->set($info, [$date => $dados]);
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

}