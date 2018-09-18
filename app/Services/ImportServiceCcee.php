<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 06/08/18
 * Time: 10:15
 */

namespace Crawler\Services;

use Crawler\Excel\ImportExcelCcee;
use Crawler\Util\Util;
use function Psy\sh;

class ImportServiceCcee
{
    private $importExcelCcee;
    private $util;

    public function __construct(ImportExcelCcee $importExcelCcee,
                                Util $util)
    {
        $this->importExcelCcee = $importExcelCcee;
        $this->util = $util;
    }

    public function importInfoGeral($historico,
                                    $date,
                                    $sheet_consumo,
                                    $sheet_geracao,
                                    $sheet_demais_dados,
                                    $sheet_contratos,
                                    $sheet_incentivadas,
                                    $sheet_encargos,
                                    $sheet_reservas,
                                    $ano)
    {
//        $path = storage_path('app') . '/' . $resultado['geral'][$date]['file'][0]; // para atualização mensal
        $path = $historico; // para obtenção do histórico

         //003 Consumo; Tabela 001
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_porSemanaEpatamar(
            $path,
            $sheet_consumo,
            'Consumo no centro de gravidade por submercado/semana/patamar',
            array(
                'ano' => $ano,
                'abertura' => 'no CG por submercado/semana/patamar'
            ))]);

         //003 Consumo; Tabela 002
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo no centro de gravidade por classe de agente',
            'classe_do_agente',
            array(
                'ano' => $ano,
                'abertura' => 'no CG por classe de agente'
            ))]);

         //003 Consumo; Tabela 003
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo no centro de gravidade por ambiente de comercialização',
            'ambiente',
            array(
                'ano' => $ano,
                'abertura' => 'no CG por ambiente de comercialização'
            ))]);

        //003 Consumo; Tabela 004
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo de consumidores livres e especiais, no centro de gravidade, por ramo de atividade',
            'ramo_de_atividade',
            array(
                'ano' => $ano,
                'abertura' => 'consumidores livres no CG por ramo de atividade'
            ))]);

        // 003 Consumo; Tabela 006
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_porSemanaEpatamar(
            $path,
            $sheet_consumo,
            'Consumo da geração no centro de gravidade - MW médios',
            array(
                'ano' => $ano,
                'abertura' => 'no PC por submercado/semana/patamar'
            ))]);

        // 003 Consumo; Tabela 007
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo de consumidores livres e especiais, no ponto de conexão, por ramo de atividade',
            'ramo_de_atividade',
            array(
                'ano' => $ano,
                'abertura' => 'consumidores livres no PC por ramo de atividade'
            ))]);

        // 003 Consumo; Tabela 008
        $this->util->enviaArangoDB('ccee', 'consumo', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_consumo,
            'Consumo de autoprodutores, no ponto de conexão, por ramo de atividade',
            'ramo_de_atividade',
            array(
                'ano' => $ano,
                'abertura' => 'autoprodutores no PC por ramo de atividade'
            ))]);

        // 001 Geração; Tabela 001
        $this->util->enviaArangoDB('ccee', 'geracao', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_geracao,
            'Histórico de geração no centro de gravidade por fonte',
            'fonte_de_geracao',
            array(
                'ano' => $ano,
                'abertura' => 'no centro de gravidade por fonte'
            ))]);

        // 001 Geração; Tabela 007
        $this->util->enviaArangoDB('ccee', 'geracao', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_porSemanaEpatamar(
            $path,
            $sheet_geracao,
            'Histórico de Geração no centro de gravidade por submercado/semana/patamar',
            array(
                'ano' => $ano,
                'abertura' => 'no centro de gravidade por submercado/semana/patamar'
            ))]);

        // Demais Dados; Tabela 001
        $this->util->enviaArangoDB('ccee', 'agentes', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_sem_conta($path, $sheet_demais_dados,
            'Número de agentes participantes da contabilização por classe',
            'classe',
            array(
                'ano' => $ano,
                'abertura' => 'número de agentes participantes da contabilização por classe'
            ))]);

        // 005 Contratos; Tabela 001
        $this->util->enviaArangoDB('ccee', 'contratos', $date, 'mensal', ['data' =>
            $this->importExcelCcee->dado_padrao($path, $sheet_contratos,
            'Montantes de contratos no centro de gravidade por tipo',
            'tipo_de_contrato',
            array(
                'ano' => $ano,
                'abertura' => 'montates no CG por tipo'
            ))]);

        // 005 Contratos; Tabela 003
        $this->util->enviaArangoDB('ccee', 'contratos', $date, 'mensal', ['data' =>
            $this->importExcelCcee->montante_de_contrato_por_comprador_e_vendedor(
            $path,
            $sheet_contratos,
            'Montantes de contratos no centro de gravidade por classe do comprador e do vendedor',
            array(
                'ano' => $ano,
                'abertura' => 'montates no CG por classe do comprador e do vendedor'
            ))]);

        // 022 Incentivadas; Tabela 003
        $this->util->enviaArangoDB('ccee', 'incentivadas', $date, 'mensal', ['data' =>
            $this->importExcelCcee->montante_de_contrato_por_modalidade_e_desconto(
            $path,
            $sheet_incentivadas,
            'Montantes de contratos de compra de Energia Incentivada e convencional especial de consumidores livres, especiais e autoprodutores',
            array(
                'ano' => $ano,
                'abertura' => 'montante de contratos de compra'
            ))]);

        // 008 Encargos; Tabela 001, 009
        $this->util->enviaArangoDB('ccee', 'ees', $date, 'mensal', ['data' =>
            $this->importExcelCcee->ess($path, $sheet_encargos,
            'Recebimentos de encagos de serviços do sistema por tipo',
            ' Consumo de Referência para Pagamentos de Encargos de Serviços do Sistema',
            array(
                'ano' => $ano,
            ))]);

        // 023 Reserva; Tabela 007, 008
        $this->util->enviaArangoDB('ccee', 'eer', $date, 'mensal', ['data' =>
            $this->importExcelCcee->eer($path, $sheet_reservas,
            'Encargo de Energia de Reserva - R$',
            'Consumo de Referência para Pagamento de Encargo de Energia de Reserva - MW',
            array(
                'ano' => $ano,
            ))]);

        return '';
    }

    public function importInfoIndividual($historico, $sheet, $date, $ano)
    {
        // 002 Usinas
        $resultado['data'] = $this->importExcelCcee->geracao_usinas(
//            storage_path('app') . '/' . $resultado['individual'][$date][0],
            $historico, // para histórico
            $sheet,
            'Informações de garantia física, capacidade e geração das usinas por mês',
            $ano);

        return $resultado;
    }

    public function historico_infoMercado_individual_2015($file, $sheet, $date, $ano)
    {
        $resultado['data'] = $this->importExcelCcee->geracao_usinas_2015(
            $file,
            $sheet,
            'Informações de garantia física, capacidade e geração das usinas por mês',
            $ano);

        return $resultado;
    }

    public function historico_infoMercado_individual_2013e2014($file, $sheet, $date, $ano)
    {
        $resultado['data'] = $this->importExcelCcee->historico_infoMercado_2013e2014(
            $file,
            $sheet,
            'Informações de usinas',
            $ano);

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