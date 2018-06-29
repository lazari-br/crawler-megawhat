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
use Crawler\Excel\ImportExcelRdh;

class RdhController extends Controller
{
    private $storageDirectory;
    private $arangoDb;
    private $regexEpe;
    private $importExcelRdh;

    public function __construct(StorageDirectory $storageDirectory,
                                ArangoDb $arangoDb,
                                RegexEpe $regexEpe,
                                ImportExcelRdh $importExcelRdh)

    {
        $this->storageDirectory = $storageDirectory;
        $this->arangoDb = $arangoDb;
        $this->regexEpe = $regexEpe;
        $this->importExcelRdh = $importExcelRdh;
    }


    public function enaRdh()
    {
        $carbon = Carbon::now();
        $date = $carbon->format('m-Y');

        // Importação dos dados da planilha
        $sheet = 1; // Hidroenergéticas-Subsistemas
        $resultado[$date]['ENA'] = $this->importExcelRdh->rdhEna(
            storage_path('/app/RDH01JAN.xlsx'),

            $sheet,
            $carbon
        );

        try {
            if ($this->arangoDb->collectionHandler()->has('rdh')) {

                $this->arangoDb->documment()->set('rdh_diario', $resultado);
                $this->arangoDb->documentHandler()->save('rdh', $this->arangoDb->documment());

            } else {

                // create a new collection
                $this->arangoDb->collection()->setName('rdh');
                $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                $this->arangoDb->documment()->set('rdh_diario', $resultado);
                $this->arangoDb->documentHandler()->save('rdh', $this->arangoDb->documment());
            }
        } catch (ArangoConnectException $e) {
            print 'Connection error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoClientException $e) {
            print 'Client error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoServerException $e) {
            print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
        }

        return response()->json([
            'responsabilidade' => 'Buscar os dados de ENA acessados por e-mail',
            'status' => 'Crawler RDH realizado com sucesso!'
        ]);
    }
}
