<?php

namespace Crawler\Http\Controllers;

use Illuminate\Http\Request;
use Crawler\StorageDirectory\StorageDirectory;
use Ixudra\Curl\Facades\Curl;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use Crawler\Model\ArangoDb;
use Carbon\Carbon;
use Crawler\Regex\RegexEpe;
use Crawler\Excel\ImportExcelEpe;
use Crawler\Util\Util;


class EpeConsumoController extends Controller
{
    private $storageDirectory;
    private $arangoDb;
    private $regexEpe;
    private $importExcelEpe;
    private $util;

    public function __construct(StorageDirectory $storageDirectory,
                                ArangoDb $arangoDb,
                                RegexEpe $regexEpe,
                                Util $util,
                                ImportExcelEpe $importExcelEpe)

    {
        $this->storageDirectory = $storageDirectory;
        $this->arangoDb = $arangoDb;
        $this->regexEpe = $regexEpe;
        $this->importExcelEpe = $importExcelEpe;
        $this->util= $util;
    }

    public function getConsumo()
    {
        $carbon = Carbon::now();
        $date = Carbon::now()->format('Y-m-d');
        $ano = $carbon->format('Y');

        $url_base = 'www.epe.gov.br';
        $url = "http://www.epe.gov.br/pt/publicacoes-dados-abertos/publicacoes/Consumo-mensal-de-energia-eletrica-por-classe-regioes-e-subsistemas";

        $response = Curl::to($url)
            ->returnResponseObject()
            ->get();

        if ($response->status == 200) {

            $url_download = $this->regexEpe->pregReplaceString(' ', '%20', $this->regexEpe->capturaDownload($response->content));

            $result_download = Curl::to($url_base.$url_download.'.xls')
                ->setCookieJar('wgvw')
                ->withContentType('application/xls')
                ->download('');

            $resultado[$date]['file'] = $this->storageDirectory->saveDirectory('epe/' . $date . '/', 'MERCADO_MENSAL_PARA_DOWLOAD_COLADO_2004-'.$ano.'.xls', $result_download);

            // Importação dos dados da planilha
            $sheet = 1; // RESIDENCIAL
            $resultado[$date]['data']['Consumo']['Região Geográfica (MWh)'] = $this->importExcelEpe->epeConsReg(
                storage_path('app') . '/' . $resultado[$date]['file'][0],
                $sheet
            );
            $sheet = 1; // RESIDENCIAL
            $resultado[$date]['data']['Consumo']['Subsistema (MWh)'] = $this->importExcelEpe->epeConsSubsist(
                storage_path('app') . '/' . $resultado[$date]['file'][0],
                $sheet
            );

            $this->util->enviaArangoDB('epe', 'consumo', $date, $resultado);

        }
        return response()->json([
            'site' => 'www.epe.gov.br',
            'responsabilidade' => 'Realizar download do arquivo EPE consumo!.',
            'status' => 'O crawler não encontrou o arquivo especificado!'
        ]);

    }


    public function historico_epe()
    {
        $date = Carbon::now()->format('Y-m-d');

        $file = '/var/www/html/crawler-megawhat/storage/app/historico/MERCADO MENSAL PARA DOWNLOAD COLADO.xls';

        $dados['mensal']['data'] = $this->importExcelEpe->epe_historico($file, 1);

        $this->util->enviaArangoDB('epe', 'consumo', $date, $dados);
    }


}
