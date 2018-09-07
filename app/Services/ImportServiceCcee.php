<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 06/08/18
 * Time: 10:15
 */

namespace Crawler\Services;

use Crawler\Excel\ImportExcelCcee;
use function Psy\sh;

class ImportServiceCcee
{
    private $importExcelCcee;

    public function __construct(ImportExcelCcee $importExcelCcee)
    {
        $this->importExcelCcee = $importExcelCcee;
    }

    public function importInfoGeral($resultado,
                                    $date,
                                    $sheet_consumo,
                                    $sheet_geracao,
                                    $sheet_demais_dados,
                                    $sheet_contratos,
                                    $sheet_incentivadas,
                                    $sheet_encargos,
                                    $sheet_reservas)
    {
        $path = storage_path('app') . '/' . $resultado['geral'][$date]['file'][0]; // para atualização mensal
//        $path = $historico; // para obtenção do histórico

        // 003 Consumo; Tabela 001
        $resultado['data']['Consumo']['no CG por submercado/semana/patamar'] = $this->importExcelCcee->dado_porSemanaEpatamar(
            $path,
            $sheet_consumo,
            'Consumo no centro de gravidade por submercado/semana/patamar');

        // 003 Consumo; Tabela 002
        $resultado['data']['Consumo']['no CG por classe de agente'] = $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo no centro de gravidade por classe de agente',
            'classe_do_agente');

        // 003 Consumo; Tabela 003

        $resultado['data']['Consumo']['no CG por ambiente de comercialização'] = $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo no centro de gravidade por ambiente de comercialização',
            'ambiente');

        // 003 Consumo; Tabela 004
        $resultado[$date]['data']['Consumo']['consumidores livres no CG por ramo de atividade'] = $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo de consumidores livres e especiais, no centro de gravidade, por ramo de atividade',
            'ramo_de_atividade');

        // 003 Consumo; Tabela 006
        $resultado['data']['Consumo']['no PC por submercado/semana/patamar'] = $this->importExcelCcee->dado_porSemanaEpatamar(
            $path,
            $sheet_consumo,
            'Consumo da geração no centro de gravidade - MW médios');

        // 003 Consumo; Tabela 007
        $resultado['data']['Consumo']['consumidores livres no PC por ramo de atividade'] = $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo de consumidores livres e especiais, no ponto de conexão, por ramo de atividade',
            'ramo_de_atividade');

        // 003 Consumo; Tabela 008
        $resultado['data']['Consumo']['autoprodutores no PC por ramo de atividade'] = $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo de autoprodutores, no ponto de conexão, por ramo de atividade',
            'ramo_de_atividade');

        // 001 Geração; Tabela 001
        $resultado['data']['Geração']['histórico de geração no CG por fonte'] = $this->importExcelCcee->dado_padrao($path, $sheet_geracao,
            'Histórico de geração no centro de gravidade por fonte',
            'fonte_de_geracao');

        // 001 Geração; Tabela 007
        $resultado['data']['Geração']['histórico de geração no CG por submercado/semana/patamar'] = $this->importExcelCcee->dado_porSemanaEpatamar(
            $path,
            $sheet_geracao,
            'Histórico de Geração no centro de gravidade por submercado/semana/patamar');

        // Demais Dados; Tabela 001
        $resultado['data']['número de agentes participantes da contabilização por classe'] = $this->importExcelCcee->dado_sem_conta($path, $sheet_demais_dados,
            'Número de agentes participantes da contabilização por classe',
            'classe');

        // 005 Contratos; Tabela 001
        $resultado['data']['Dados de Contrato']['montates no CG por tipo'] = $this->importExcelCcee->dado_padrao($path, $sheet_contratos,
            'Montantes de contratos no centro de gravidade por tipo',
            'tipo_de_contrato');

        // 005 Contratos; Tabela 003
        $resultado['data']['Dados de Contrato']['montates no CG por classe do comprador e do vendedor'] = $this->importExcelCcee->montante_de_contrato_por_comprador_e_vendedor(
            $path,
            $sheet_contratos,
            'Montantes de contratos no centro de gravidade por classe do comprador e do vendedor');

        // 022 Incentivadas; Tabela 003
        $resultado['data']['Incentivadas']['montante de contratos de compra'] = $this->importExcelCcee->montante_de_contrato_por_modalidade_e_desconto(
            $path,
            $sheet_incentivadas,
            'Montantes de contratos de compra de Energia Incentivada e convencional especial de consumidores livres, especiais e autoprodutores');

        // 008 Encargos; Tabela 001, 009
        $resultado['data']['ESS'] = $this->importExcelCcee->ess($path, $sheet_encargos,
            'Recebimentos de encagos de serviços do sistema por tipo',
            ' Consumo de Referência para Pagamentos de Encargos de Serviços do Sistema');

        // 023 Reserva; Tabela 007, 008
        $resultado['data']['EER']['R$'] = $this->importExcelCcee->eer($path, $sheet_reservas,
            'Encargo de Energia de Reserva - R$',
            'Consumo de Referência para Pagamento de Encargo de Energia de Reserva - MW'
        );

        return $resultado;
    }

    public function importInfoIndividual($resultado, $sheet, $date)
    {
        // 002 Usinas
        $resultado['data'] = $this->importExcelCcee->geracao_usinas(
            storage_path('app') . '/' . $resultado['individual'][$date][0],
            $sheet,
            'Informações de garantia física, capacidade e geração das usinas por mês');

        return $resultado;
    }

    public function historico_infoMercado_individual_2015($file, $sheet, $date)
    {
        $resultado['data'] = $this->importExcelCcee->geracao_usinas_2015(
            $file,
            $sheet,
            'Informações de garantia física, capacidade e geração das usinas por mês');

        return $resultado;
    }

    public function historico_infoMercado_individual_2013e2014($file, $sheet, $date)
    {
        $resultado['data'] = $this->importExcelCcee->historico_infoMercado_2013e2014(
            $file,
            $sheet,
            'Informações de usinas');

        return $resultado;
    }

    public function leiloes($resultado)
    {
        //Resultado Consolidado
        $sheet = 4;
        $resultado = $this->importExcelCcee->leilao(
//            storage_path('app') . '/' . $resultado['file'][0],
            $resultado,
            $sheet);

        return $resultado;
    }
}