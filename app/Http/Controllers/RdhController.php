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
use Crawler\Util\Util;

class RdhController extends Controller
{
    private $storageDirectory;
    private $arangoDb;
    private $regexEpe;
    private $importExcelRdh;
    private $util;

    public function __construct(StorageDirectory $storageDirectory,
                                ArangoDb $arangoDb,
                                RegexEpe $regexEpe,
                                Util $util,
                                ImportExcelRdh $importExcelRdh)

    {
        $this->storageDirectory = $storageDirectory;
        $this->arangoDb = $arangoDb;
        $this->regexEpe = $regexEpe;
        $this->importExcelRdh = $importExcelRdh;
        $this->util = $util;
    }


    public function enaRdh()
    {
        $date = Util::getDateIso();

        // Importação dos dados da planilha
        $sheet = 1; // Hidroenergéticas-Subsistemas
        $resultado[$date]['ENA'] = $this->importExcelRdh->rdhEna(
            storage_path('/app/RDH01JAN.xlsx'),
            $sheet
        );

        $this->util->enviaArangoDB('rdh', 'ena', $date, 'diario',  $resultado);

        return response()->json([
            'responsabilidade' => 'Buscar os dados de ENA acessados por e-mail',
            'status' => 'Crawler RDH realizado com sucesso!'
        ]);
    }
}
