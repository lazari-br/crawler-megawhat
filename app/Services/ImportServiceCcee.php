<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 06/08/18
 * Time: 10:15
 */

namespace Crawler\Services;

use Crawler\Excel\ImportExcelCcee;

class ImportServiceCcee
{
    private $importExcelCcee;

    public function __construct(ImportExcelCcee $importExcelCcee)
    {
        $this->importExcelCcee = $importExcelCcee;
    }

    public function importInfoGeral($resultado, $date, $carbon)
    {
        $sheet = 5; // 003 Consumo; Tabela 001
        $startRow = 15;
        $takeRows = 86;
        $resultado['geral'][$date]['data']['Consumo']['no CG por submercado/semana/patamar']['MWh'] = $this->importExcelCcee->cceeConsCGPatMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['no CG por submercado/semana/patamar']['MWm'] = $this->importExcelCcee->cceeConsCGPatMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 5; // 003 Consumo; Tabela 002
        $startRow = 92;
        $takeRows = 98;
        $resultado['geral'][$date]['data']['Consumo']['no CG por classe de agente']['MWh'] = $this->importExcelCcee->cceeConsCGClMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['no CG por classe de agente']['MWm'] = $this->importExcelCcee->cceeConsCGClMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 5; // 003 Consumo; Tabela 003
        $startRow = 104;
        $takeRows = 105;
        $resultado['geral'][$date]['data']['Consumo']['no CG por ambiente de comercialização']['MWh'] = $this->importExcelCcee->cceeConsCGAmbMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['no CG por ambiente de comercialização']['MWm'] = $this->importExcelCcee->cceeConsCGAmbMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 5; // 003 Consumo; Tabela 004
        $startRow = 113;
        $takeRows = 127;
        $resultado['geral'][$date]['data']['Consumo']['consumidores livres no CG por ramo de atividade']['MWh'] = $this->importExcelCcee->cceeConsLivCGRamoMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['consumidores livres no CG por ramo de atividade']['MWm'] = $this->importExcelCcee->cceeConsLivCGRamoMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 5; // 003 Consumo; Tabela 006
        $startRow = 151;
        $takeRows = 226;
        $resultado['geral'][$date]['data']['Consumo']['no PC por submercado/semana/patamar']['MWh'] = $this->importExcelCcee->cceeConsGerCGMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['no PC por submercado/semana/patamar']['MWm'] = $this->importExcelCcee->cceeConsGerCGMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 5; // 003 Consumo; Tabela 007
        $startRow = 232;
        $takeRows = 246;
        $resultado['geral'][$date]['data']['Consumo']['consumidores livres no PC por ramo de atividade']['MWh'] = $this->importExcelCcee->cceeConsLivPCRamoMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['consumidores livres no PC por ramo de atividade']['MWm'] = $this->importExcelCcee->cceeConsLivPCRamoMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 5; // 003 Consumo; Tabela 008
        $startRow = 253;
        $takeRows = 263;
        $resultado['geral'][$date]['data']['Consumo']['autoprodutores no PC por ramo de atividade']['MWh'] = $this->importExcelCcee->cceeConsAutoProdPCRamoMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Consumo']['autoprodutores no PC por ramo de atividade']['MWm'] = $this->importExcelCcee->cceeConsAutoProdPCRamoMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 3 ; // 001 Geração; Tabela 001
        $startRow = 15;
        $takeRows = 27;
        $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por fonte']['MWh'] = $this->importExcelCcee->cceeGerCGFontMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por fonte']['MWm'] = $this->importExcelCcee->cceeGerCGFontMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 3; // 001 Geração; Tabela 007
        $startRow = 111;
        $takeRows = 187;
        $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por submercado/semana/patamar']['MWh'] = $this->importExcelCcee->cceeGerCGPatMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por submercado/semana/patamar']['MWm'] = $this->importExcelCcee->cceeGerCGPatMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 27; // Demais Dados; Tabela 001
        $startRow = 15;
        $takeRows = 21;
        $resultado['geral'][$date]['data']['número de agentes participantes da contabilização por classe'] = $this->importExcelCcee->cceeNumAgClasse(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 7; // 005 Contratos; Tabela 001
        $startRow = 15;
        $takeRows = 21;
        $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por tipo']['MWh'] = $this->importExcelCcee->cceeMontCGTipoMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por tipo']['MWm'] = $this->importExcelCcee->cceeMontCGTipoMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 7; // 005 Contratos; Tabela 003
        $startRow = 58;
        $takeRows = 106;
        $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por classe do comprador e do vendedor']['MWm'] = $this->importExcelCcee->cceeMontCGClasseCompVendMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por classe do comprador e do vendedor']['MWh'] = $this->importExcelCcee->cceeMontCGClasseCompVendMWm(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 24; // 022 Incentivadas; Tabela 003
        $startRow = 29;
        $takeRows = 46;
        $resultado['geral'][$date]['data']['Incentivadas']['montante de contratos de compra']['MWm'] = $this->importExcelCcee->cceeIncentContrCompMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $startRow,
            $takeRows,
            $carbon
        );
        $sheet = 10; // 008 Encargos; Tabela 001, 009
        $resultado['geral'][$date]['data']['ESS']['R$'] = $this->importExcelCcee->cceeEss(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $carbon
        );
        $sheet = 10; // 008 Encargos; Tabela 001, 009
        $resultado['geral'][$date]['data']['ESS']['R$/MWh'] = $this->importExcelCcee->cceeEssPorMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $carbon
        );
        $sheet = 25; // 023 Reserva; Tabela 007, 008
        $resultado['geral'][$date]['data']['EER']['R$'] = $this->importExcelCcee->cceeEer(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $carbon
        );
        $sheet = 25; // 023 Reserva; Tabela 007, 008
        $resultado['geral'][$date]['data']['EER']['R$/MWh'] = $this->importExcelCcee->cceeEerPorMWh(
            storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
            $sheet,
            $carbon
        );

        return $resultado;
    }


    public function importeInfoIndividual($resultado, $date, $carbon)
    {
        $sheet = 3; // 002 Usinas
        $resultado['individual'][$date]['data'] = $this->importExcelCcee->cceeUsinas(
            storage_path('app') . '/' . $resultado['individual'][$date][0],
            $sheet,
            $carbon
        );

        return $resultado;
    }

    public function leiloes($resultado)
    {
        $sheet = 4; //Resultado Consolidado
        $resultado['data'] = $this->importExcelCcee->leilao(
            storage_path('app') . '/' . $resultado['file'][0],
            $sheet);
    }
}