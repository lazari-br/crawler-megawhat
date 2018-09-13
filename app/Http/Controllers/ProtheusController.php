<?php

namespace Crawler\Http\Controllers;

use Carbon\Carbon;
use Crawler\StorageDirectory\StorageDirectory;
use Crawler\Util\Util;
use Crawler\Util\UtilProtheus;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Goutte\Client;
use Crawler\Model\ArangoDb;
use Crawler\Regex\RegexProtheus;



class ProtheusController extends Controller
{
    private $storageDirectory;
    private $client;
    private $arangoDb;
    private $util;
    private $utilProtheus;
    private $regexProtheus;


    public function __construct(StorageDirectory $storageDirectory,
                                Client $client,
                                ArangoDb $arangoDb,
                                Util $util,
                                UtilProtheus $utilProtheus,
                                RegexProtheus $regexProtheus
    )
    {
        $this->storageDirectory = $storageDirectory;
        $this->client = $client;
        $this->arangoDb = $arangoDb;
        $this->util = $util;
        $this->utilProtheus = $utilProtheus;
        $this->regexProtheus = $regexProtheus;
    }

    function pld()
    {
        $date = Util::getDateIso();
        $url_base = 'http://192.168.0.46:3295/ws/RWSA02.apw';
        $submercados = ['N' => 'Norte',
                        'NE'=> 'Nordeste',
                        'S' => 'Sul',
                        'SE/CO' => 'Sudeste/Centro-Oeste'];

        $headersChave = ['Content-Type: text/xml;charset=UTF-8','SOAPAction: http://192.168.0.46:3295/ws/GETCHAVE'];
        $rawChave = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://192.168.0.46:3295/ws/"><soapenv:Header/><soapenv:Body><ws:GETCHAVE><ws:CUSER>comercbearned</ws:CUSER><ws:CSENHA>C0m3rc@bearned</ws:CSENHA></ws:GETCHAVE></soapenv:Body></soapenv:Envelope>';
        $headersPld = ['Content-Type: text/xml;charset=UTF-8', 'SOAPAction: http://192.168.0.46:3295/ws/GETPLD'];

        $resultChave = $this->utilProtheus->curlProtheus($url_base, $headersChave, $rawChave);
        $chave = $this->regexProtheus->getChave($resultChave);

        $dataInicio = $this->utilProtheus->setInicioProtheus();
        $dataFim = $this->utilProtheus->setFimProtheus();

        foreach ($submercados as $sub => $submercado) {
            $rawPld = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://192.168.0.46:3295/ws/"><soapenv:Header/><soapenv:Body><ws:GETPLD><ws:RECGETPLD><ws:ANOMES>cqewrbet</ws:ANOMES><ws:CCHAVE>' . $chave . '</ws:CCHAVE><!--Optional:--><ws:CLIENTE></ws:CLIENTE><ws:CSENHA>C0m3rc@bearned</ws:CSENHA><ws:CUSER>comercbearned</ws:CUSER><!--Optional:--><ws:DDTAFIM>' . $dataFim . '</ws:DDTAFIM><!--Optional:--><ws:DDTAINI>' . $dataInicio . '</ws:DDTAINI><!--Optional:--><ws:LOJA></ws:LOJA><!--Optional:--><ws:SUBMERCADO>' . $sub . '</ws:SUBMERCADO><!--Optional:--><ws:TIPO>3</ws:TIPO></ws:RECGETPLD></ws:GETPLD></soapenv:Body></soapenv:Envelope>';

            $resultPld = $this->utilProtheus->curlProtheus($url_base, $headersPld, $rawPld);
            $pld = $this->regexProtheus->getPld($resultPld);

            $data[] = [
                'inicio' => Carbon::createFromFormat('Ymd', $dataInicio)->format('d/m/Y'),
                'fim' => Carbon::createFromFormat('Ymd', $dataFim)->format('d/m/Y'),
                'subsistema' => $submercado,
                'valor' => $this->util->formata_valores(['pld' => $pld])
            ];
        }

        $this->util->enviaArangoDB('protheus', 'pld', $date, 'mensal', $data);
    }

    public function historico_pld_protheus()
    {
        set_time_limit(-1);
        $rowData = $this->util->import(1, 0, '/var/www/html/crawler-megawhat/storage/app/historico/historico_pld.xlsx');
        $data =[];

        foreach ($rowData as $key => $linha) {
            $data[$key] = [
                'subsistema' => trim($rowData[$key]['submercado']),
                'inicio' => Carbon::createFromFormat('d/m/Y', $rowData[$key]['inicio'])->format('d/m/Y'),
                'fim' => Carbon::createFromFormat('d/m/Y', $rowData[$key]['fim'])->format('d/m/Y'),
                'valor' => [
                    'pesado' => $rowData[$key]['valor_pesado'],
                    'medio' => $rowData[$key]['valor_medio'],
                    'leve' => $rowData[$key]['valor_leve']
                ]
            ];
        }

        $this->util->enviaArangoDB('protheus', 'pld', Util::getDateIso(), 'semanal', $data);
    }

}