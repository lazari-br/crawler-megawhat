<?php

namespace Crawler\Http\Controllers;

use Carbon\Carbon;
use Crawler\Regex\RegexEletrobras;
use Crawler\Util\Util;
use Illuminate\Http\Request;
use Crawler\StorageDirectory\StorageDirectory;
use Ixudra\Curl\Facades\Curl;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use Crawler\Model\ArangoDb;
use Crawler\Excel\ImportExcel;


class EletroBrasController extends Controller
{
    private $storageDirectory;
    private $arangoDb;
    private $regexEletrobras;
    private $importExcel;
    private $util;



    public function __construct(StorageDirectory $storageDirectory,
                                ArangoDb $arangoDb,
                                RegexEletrobras $regexEletrobras,
                                Util $util,
                                ImportExcel $importExcel)
    {
        $this->storageDirectory = $storageDirectory;
        $this->arangoDb = $arangoDb;
        $this->regexEletrobras = $regexEletrobras;
        $this->importExcel = $importExcel;
        $this->util= $util;

    }

    public function getCde()
    {
        $carbon = Carbon::now();
        $ano = $carbon->year;
        $date = $carbon->format('Y-m-d');

        $url_base = 'http://eletrobras.com';

        $url = $url_base.'/pt/FundosSetoriaisCDE/Forms/AllItems.aspx';
        $response = Curl::to($url)
            ->returnResponseObject()
            ->setCookieFile('down')
            ->get();

        $url_movimentacao = $this->regexEletrobras->capturaUrlMovimentacao($response->content);
        $mount_url_dowload = $url_base.$url_movimentacao;

        $url_download = Curl::to($mount_url_dowload)
            ->setCookieFile('down')
            ->allowRedirect(true)
            ->withContentType('application/xlsx')
            ->download('');


        if ($response->status == 200) {

            $resultado = $this->storageDirectory->saveDirectory('eletrobras/'.$ano.'/', 'CDE-'.$ano.'-Movimentação_Finaceira.xlsx', $url_download);

            $this->util->enviaArangoDB('eletrobras', 'cde', $date, $resultado);

            return response()->json([
                'site' => 'http://eletrobras.com',
                'responsabilidade' => 'Realiza o download cde movimentação financeira!',
                'status' => 'Crawler realizado com sucesso!'
            ]);

        }
        return response()->json([
            'site' => 'http://eletrobras.com',
            'responsabilidade' => 'Realiza o download cde movimentação financeira!',
            'status' => 'O crawler não encontrou o arquivo especificado!'
        ]);
    }

}
