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


class EpeConsumoController extends Controller
{
    private $storageDirectory;
    private $arangoDb;
    private $regexEpe;
    private $importExcelEpe;

    public function __construct(StorageDirectory $storageDirectory,
                                ArangoDb $arangoDb,
                                RegexEpe $regexEpe,
                                ImportExcelEpe $importExcelEpe)

    {
        $this->storageDirectory = $storageDirectory;
        $this->arangoDb = $arangoDb;
        $this->regexEpe = $regexEpe;
        $this->importExcelEpe = $importExcelEpe;
    }

    public function getConsumo()
    {
        $carbon = Carbon::now();
        $date = $carbon->format('m-Y');
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

                $sheet,
                $carbon
            );
            $sheet = 1; // RESIDENCIAL
            $resultado[$date]['data']['Consumo']['Subsistema (MWh)'] = $this->importExcelEpe->epeConsSubsist(
                storage_path('app') . '/' . $resultado[$date]['file'][0],

                $sheet,
                $carbon
            );

            try {
                if ($this->arangoDb->collectionHandler()->has('epe')) {

                    $this->arangoDb->documment()->set('consumo', $resultado);
                    $this->arangoDb->documentHandler()->save('epe', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('epe');
                    $this->arangoDb->documment()->set('consumo', $resultado);
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }
            return response()->json([
                'site' => 'www.epe.gov.br',
                'responsabilidade' => 'Realizar download do arquivo EPE consumo!.',
                'status' => 'Crawler EPE realizado com sucesso!'
            ]);

        }
        return response()->json([
            'site' => 'www.epe.gov.br',
            'responsabilidade' => 'Realizar download do arquivo EPE consumo!.',
            'status' => 'O crawler não encontrou o arquivo especificado!'
        ]);

    }


}
