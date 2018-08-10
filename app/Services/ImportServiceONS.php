<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 06/08/18
 * Time: 10:15
 */

namespace Crawler\Services;

use Crawler\Excel\ImportExcelOns;

class ImportServiceONS
{
    private $importExcelOns;
    
    public function __construct(ImportExcelOns $importExcelOns)
    {
        $this->importExcelOns = $importExcelOns;
    }
    
    public function importSdroSemanal($url_download, $date_format, $carbon)
    {
        $sheet = 7; // 07-Motivo de Dispacho Térmico
        $startRow = 6;
        $takeRows = 200;
        $resultado[$date_format]['Motivo do dispacho termoelétrico']['MWh'] = $this->importExcelOns->onsMotDispMWh(
            storage_path('app') . '/' . $url_download[$date_format]['url_download_semanal'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 12; // 12-Grandezas Hidroenergéticas
        $resultado[$date_format]['ENA']['MWm'] = $this->importExcelOns->onsEnaSemanalMWm(
            storage_path('app') . '/' . $url_download[$date_format]['url_download_semanal'][0],
            $sheet
        );
        $sheet = 12; // 12-Grandezas Hidroenergéticas
        $resultado[$date_format]['ENA']['% MLT'] = $this->importExcelOns->onsEnaSemanalPerc(
            storage_path('app') . '/' . $url_download[$date_format]['url_download_semanal'][0],
            $sheet
        );

        return $resultado;

    }

    public function importSdroDiario($url_download, $date_format, $carbon)
    {
        $sheet = 8; // 08-Produção Hidráulica
        $startRow = 4;
        $takeRows = 9;
        $url_download[$date_format]['data']['diário']['Produção']['Hidráulica']['GWh'] = $this->importExcelOns->onsProdHidGWh(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 8; // 08-Produção Hidráulica
        $startRow = 12;
        $takeRows = 17;
        $url_download[$date_format]['data']['diário']['Produção']['Hidráulica'] ['MWmed'] = $this->importExcelOns->onsProdHidMWm(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 8; // 08-Produção Hidráulica
        $startRow = 23;
        $takeRows = 300;
        $url_download[$date_format]['data']['diário']['Produção'] ['Hidráulica'] ['por Usina'] = $this->importExcelOns->onsProdHidUsina(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 9; // 09-Produção Térmica
        $startRow = 4;
        $takeRows = 8;
        $url_download[$date_format]['data']['diário']['Produção']['Térmica']['GWh'] = $this->importExcelOns->onsProdTerGWh(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 9; // 09-Produção Térmica
        $startRow = 11;
        $takeRows = 15;
        $url_download[$date_format]['data']['diário']['Produção']['Térmica'] ['MWmed'] = $this->importExcelOns->onsProdTerMWm(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 9; // 09-Produção Térmica
        $startRow = 21;
        $takeRows = 300;
        $url_download[$date_format]['data']['diário']['Produção'] ['Térmica'] ['por Usina'] = $this->importExcelOns->onsProdTerUsina(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 10; // 10-Produção Eólica
        $startRow = 4;
        $takeRows = 8;
        $url_download[$date_format]['data']['diário']['Produção']['Eólica']['GWh'] = $this->importExcelOns->onsProdEolGWh(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 10; // 10-Produção Eólica
        $startRow = 11;
        $takeRows = 15;
        $url_download[$date_format]['data']['diário']['Produção']['Eólica'] ['MWmed'] = $this->importExcelOns->onsProdEolMWm(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 10; // 10-Produção Eólica
        $startRow = 21;
        $takeRows = 300;
        $url_download[$date_format]['data']['diário']['Produção'] ['Eólica'] ['por Usina'] = $this->importExcelOns->onsProdEolUsina(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 11; // 11-Produção Solar
        $startRow = 4;
        $takeRows = 8;
        $url_download[$date_format]['data']['diário']['Produção']['Solar']['GWh'] = $this->importExcelOns->onsProdSolGWh(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 11; // 11-Produção Solar
        $startRow = 11;
        $takeRows = 15;
        $url_download[$date_format]['data']['diário']['Produção']['Solar'] ['MWmed'] = $this->importExcelOns->onsProdSolMWm(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 11; // 11-Produção Solar
        $startRow = 21;
        $takeRows = 300;
        $url_download[$date_format]['data']['diário']['Produção'] ['Solar'] ['por Usina'] = $this->importExcelOns->onsProdSolUsina(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 21; // 21-Energia Natural Afluente
        $startRow = 4;
        $takeRows = 7;
        $url_download[$date_format]['data']['diário']['ENA'] = $this->importExcelOns->onsEna(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 21; // 21-Energia Natural Afluente
        $startRow = 4;
        $takeRows = 7;
        $url_download[$date_format]['data']['diário']['ENA'] = $this->importExcelOns->onsEnaTotal(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 15; // 15-Carga Diária por Subsistema
        $startRow = 6;
        $takeRows = 10;
        $url_download[$date_format]['data']['diário']['Carga']['GWh'] = $this->importExcelOns->onsCargaGWh(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 15; // 15-Carga Diária por Subsistema
        $startRow = 13;
        $takeRows = 17;
        $url_download[$date_format]['data']['diário']['Carga']['MWmed'] = $this->importExcelOns->onsCargaMWm(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 20; // 20-Variação Energia Armazenada
        $startRow = 5;
        $takeRows = 7;
        $url_download[$date_format]['data']['diário']['EAR (MWmês)'] = $this->importExcelOns->onsEar(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 20; // 20-Variação Energia Armazenada
        $startRow = 5;
        $takeRows = 7;
        $url_download[$date_format]['data']['diário']['EAR (MWmês)'] = $this->importExcelOns->onsEarTotal(
            storage_path('app') . '/' . $url_download[$date_format]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );

        return $url_download;
    }

    public function importPmoCdre($pathNsimulada, $pathCronograma)
    {
        //Memorial de Cálculo das Usinas Não Simuladas Individualmente
        $sheet = 2; // Existentes_CCEE
        $resultado['data']['Não Simulada']['Existentes'] = $this->importExcelOns->pmoNaoSimuladasExistente($pathNsimulada, $sheet);
        $sheet = 3; // Expansão (440-2011 e 476-2012)
        $resultado['data']['Não Simulada']['Expansão'] = $this->importExcelOns->pmoNaoSimuladasExpansao($pathNsimulada, $sheet);

        //Cronograma_Reunião_DMSE
        $resultado ['data']['Não Simulada']['Expansão']['UFV'] = $this->importExcelOns->pmoUsina($pathCronograma, 0);
        $resultado ['data']['Não Simulada']['Expansão']['UEE'] = $this->importExcelOns->pmoUsina($pathCronograma, 1);
        $resultado ['data']['Não Simulada']['Expansão']['BIO'] = $this->importExcelOns->pmoUsinaComb($pathCronograma, 2);
        $resultado ['data']['Não Simulada']['Expansão']['UTE'] = $this->importExcelOns->pmoUsinaComb($pathCronograma, 3);
        $resultado ['data']['Não Simulada']['Expansão']['PCH'] = $this->importExcelOns->pmoUsina($pathCronograma, 4);
        $resultado ['data']['Não Simulada']['Expansão']['UHE'] = $this->importExcelOns->pmoUsina($pathCronograma, 5);

        return $resultado;
    }
    
}