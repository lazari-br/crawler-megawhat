<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09/05/18
 * Time: 13:57
 */

namespace Crawler\Excel;

use Carbon\Carbon;
use function GuzzleHttp\Promise\all;
use Maatwebsite\Excel\Excel;
use function PhpParser\filesInDir;
use Crawler\Util\Util;
use Crawler\Util\UtilCcee;
use function Psy\sh;
use Crawler\Regex\RegexCcee;


class ImportExcelCcee
{
    private $excel;
    private $startRow;
    private $util;
    private $utilCcee;
    private $regexCcee;


    public function __construct(Excel $excel,
                                RegexCcee $regexCcee,
                                UtilCcee $utilCcee,
                                Util $util)
    {
        $this->excel = $excel;
        $this->util = $util;
        $this->utilCcee = $utilCcee;
        $this->regexCcee = $regexCcee;

    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }

    public function dado_porSemanaEpatamar($file, $sheet, $nomeTabela, $array)
    {
        $data = [];
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $setInicio = $this->util->import(12, $sheet, $file);
        $inicio = $this->utilCcee->encontra_tabela($setInicio, $nomeTabela)[0];
        $fim = $this->utilCcee->encontra_tabela($setInicio, $nomeTabela)[1];

        $linha_inicial = (float)$inicio + 14;
        $linha_final = (float)$fim + 13;
        $rowData = $this->util->import($linha_inicial, $sheet, $file, $linha_final);

        foreach ($rowData as $key => $info) {
            unset ($rowData[$key]['0']);

            $rowData = $this->util->celulaMesclada($rowData, 'submercado', 1);
            $rowData = $this->util->celulaMesclada($rowData, 'no_semana', 1);

            if (count($rowData[$key]) !== 15){
                return response()->json(['Error:' => 'A importação da tabela'.$nomeTabela.' não foi realizada corretamente!']);
            }

            $data[$key] = array_merge($array,
                [
                'submercado' => $rowData[$key]['submercado'],
                'semana' => $rowData[$key]['no_semana'],
                'patamar' => $rowData[$key]['patamar'],
                'valor' => [
                    'mwh' => $this->util->formata_valores_mwh(array_combine($months, array_slice($rowData[$key], 3, 12))),
                    'mwmed' => $this->util->formata_valores(array_combine($months, array_slice($rowData[$key], 3, 12)))
                ]
            ]);
        }

        return $data;
    }

    public function dado_padrao($file, $sheet, $nome_tabela, $primeira_coluna, $array)
    {
        $data = [];
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $setInicio = $this->util->import(12, $sheet, $file);
        $inicio = $this->utilCcee->encontra_tabela($setInicio, $nome_tabela)[0];
        $fim = $this->utilCcee->encontra_tabela($setInicio, $nome_tabela)[1];

        $rowData = $this->util->import($inicio + 14, $sheet, $file, $fim + 13);
        foreach ($rowData as $chave => $linha) {
            if ($rowData[$chave][$primeira_coluna] === null){
                break;
            }
            if (count($rowData[$chave]) !== 14){
                return response()->json(['Error:' => 'A importação da tabela '.$nome_tabela.' não foi realizada corretamente!']);
            }

            $indice = array_keys($linha)[1];
            $valores = array_combine($months, array_slice($linha, 2, 12));
            $arrClasse['MWh'] = $this->util->formata_valores_mwh($valores);
            $arrClasse['MWmed'] = $this->util->formata_valores($valores);
            $data[$chave] = array_merge($array,
                [
                $indice => $rowData[$chave][$primeira_coluna],
                'valor'=> $arrClasse
            ]);
        }

        return $data;
    }

    public function dado_sem_conta($file, $sheet, $nome_tabela, $primeira_coluna, $array)
    {
        $data = [];
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $setInicio = $this->util->import(12, $sheet, $file);

        $inicio = $this->utilCcee->encontra_tabela($setInicio, $nome_tabela)[0];
        $fim = $this->utilCcee->encontra_tabela($setInicio, $nome_tabela)[1];

        $rowData = $this->util->import($inicio + 14, $sheet, $file, $fim + 13);
        foreach ($rowData as $chave => $linha) {
            if (count($rowData[$chave]) !== 14){
                return response()->json(['Error:' => 'A importação da tabela '.$nome_tabela.' não foi realizada corretamente!']);
            }

            $indice = array_keys($linha)[1];
            $valores = array_combine($months, array_slice($linha, 2, 12));
            $arrClasse = $this->util->formata_valores($valores);
            $data[$chave] = array_merge($array, [
                $indice => $rowData[$chave][$primeira_coluna],
                'valor'=> $arrClasse
            ]);
        }

        return $data;
    }

    public function montante_de_contrato_por_comprador_e_vendedor($file, $sheet, $nomeTabela, $array)
    {
        $data = [];
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $setInicio = $this->util->import(12, $sheet, $file);

        $inicio = $this->utilCcee->encontra_tabela($setInicio, $nomeTabela)[0];
        $fim = $this->utilCcee->encontra_tabela($setInicio, $nomeTabela)[1];

        $linhaInicial = $inicio + 14;
        $linhaFinal = $fim + 13;

        $rowData = $this->util->import($linhaInicial, $sheet, $file, $linhaFinal);
        foreach ($rowData as $chave => $item){
            if ($rowData[$chave]['classe_do_vendedor'] === null){
                $rowData = $this->util->celulaMesclada($rowData, 'classe_do_vendedor', 1);
            }

            if (count($rowData[$chave]) !== 15){
                return response()->json(['Error:' => 'A importação da tabela '.$nomeTabela.' não foi realizada corretamente!']);
            }

            $valores = array_combine($months, array_slice($rowData[$chave], 3));
            $arrClasse = $this->util->formata_valores_mwh($valores);
            $data[$chave] = array_merge($array, [
                'Classe do Vendedor' => $rowData[$chave]['classe_do_vendedor'],
                'Classe do Comprador' => $rowData[$chave]['classe_do_comprador'],
                'valor' => $arrClasse
            ]);
        }

        return $data;
    }

    public function montante_de_contrato_por_modalidade_e_desconto($file, $sheet, $nome_tabela, $array)
    {
        $data = [];
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $setInicio = $this->util->import(12, $sheet, $file);
        $inicio = $this->utilCcee->encontra_tabela($setInicio, $nome_tabela)[0];
        $fim = $this->utilCcee->encontra_tabela($setInicio, $nome_tabela)[1];

        $linha_inicial = $inicio + 14;
        $linha_final = $fim + 13;
        $rowData = $this->util->import($linha_inicial, $sheet, $file, $linha_final);
        foreach ($rowData as $chave => $linha)
        {
            if (is_numeric($rowData[$chave]['percentual_de_desconto_do_vendedor'])) {
                $rowData[$chave]['percentual_de_desconto_do_vendedor'] = $rowData[$chave]['percentual_de_desconto_do_vendedor'] * 100 . '%';
            }
            elseif (!$rowData[$chave]['percentual_de_desconto_do_vendedor']) {
                $rowData = $this->util->celulaMesclada($rowData, 'percentual_de_desconto_do_vendedor', 1);
            }
            elseif (!$rowData[$chave]['modalidade_energia']) {
                $rowData = $this->util->celulaMesclada($rowData, 'modalidade_energia', 1);
            }
        }
        foreach ($rowData as $keys => $info){
            if (count($rowData[$keys]) !== 16){
                return response()->json(['Error:' => 'A importação da tabela '.$nome_tabela.' não foi realizada corretamente!']);
            }

            $valores = array_combine($months, array_slice($rowData[$keys], 4));
            $dados = $this->util->formata_valores_mwh($valores);
            $data[$keys] = array_merge($array, [
                'modalidade de energia' => $rowData[$keys]['modalidade_energia'],
                'percentual de desconto do vendedor' => $rowData[$keys]['percentual_de_desconto_do_vendedor'],
                'classe do produtor' => $rowData[$keys]['classe_comprador'],
                'valor' => $dados
            ]);
        }

        return $data;
    }

    public function ess($file, $sheet, $primeira_tabela, $segunda_tabela, $array)
    {
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $data = [];
        $dividendo = [];
        $divisor = [];
        $rowData = $this->util->import(12, $sheet, $file);

        $inicio1 = (float)$this->utilCcee->encontra_tabela($rowData, $primeira_tabela)[0];
        $fim1 = (float)$this->utilCcee->encontra_tabela($rowData, $primeira_tabela)[1];
        $inicio2 = (float)$this->utilCcee->encontra_tabela($rowData, $segunda_tabela)[0];
        $fim2 = (float)$this->utilCcee->encontra_tabela($rowData, $segunda_tabela)[1];

        $rowDividendo = $this->util->import($inicio1 + 14, $sheet, $file, $fim1 + 12);
        foreach ($rowDividendo as $linha){
            $dividendo = array_combine($months, array_slice($linha, 2));
        }

        $rowDivisor = $this->util->import($inicio2 + 14, $sheet, $file, $fim2 + 12);
        foreach ($rowDivisor as $linha){
            $divisor = array_combine($months, array_slice($linha, 4));
        }

        foreach ($months as $chave => $mes) {
            if ($dividendo[$mes] && $divisor[$mes]) {
                $data[$mes] = number_format($dividendo[$mes] / $divisor[$mes], 2, ',', '.');
            } else {
                $data[$mes] = null;
            }
        }
        $resultado['R$/MWh'] = array_merge($array, ['valor' => $data]);
        $resultado['R$'] = array_merge($array, ['valor' => $this->util->formata_valores($dividendo)]);

        return $resultado;
    }


    public function eer($file, $sheet, $primeira_tabela, $segunda_tabela, $array)
    {
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $data = [];
        $rowData = $this->util->import(12, $sheet, $file);

        $inicio1 = (float)$this->utilCcee->encontra_tabela($rowData, $primeira_tabela)[0];
        $inicio2 = (float)$this->utilCcee->encontra_tabela($rowData, $segunda_tabela)[0];

        $rowDividendo = $this->util->import($inicio1 + 14, $sheet, $file, $inicio1 + 14);
        $dividendo = array_combine($months, array_slice($rowDividendo[0], 2));

        $rowDivisor = $this->util->import($inicio2 + 14, $sheet, $file, $inicio2 + 14);
        $divisor = array_combine($months, array_slice($rowDivisor[0], 2));

        foreach ($months as $chave => $mes) {
            if ($dividendo[$mes] && $divisor[$mes]) {
                $data[$mes] = number_format($dividendo[$mes] / $divisor[$mes], 2, ',', '.');
            } else {
                $data[$mes] = null;
            }
        }
        $resultado['R$/MWh']  = array_merge($array, ['valor' => $data]);
        $resultado['R$'] = array_merge($array, ['valor' => $this->util->formata_valores($dividendo)]);

        return $resultado;
    }

    public function geracao_usinas($file, $sheet, $tabela, $ano)
    {
        $indice = ['Código do Ativo',
                   'Sigla do Ativo',
                   'CEG do empreendimento',
                   'Código da parcela da Usina',
                   'Parcela de Usina',
                   'Tipo de Despacho',
                   'Participante do Rateio de Perdas',
                   'Fonte de Energia Primária',
                   'Submercado',
                   'UF',
                   'Característica da Parcela',
                   'Participante do MRE',
                   'Participante do Regima de Cotas',
                   '% de Desconto',
                   'Capacidade da Usina (i) - MW (CAP_T)',
                   'Garantia Física (ii) MW médio (GF)',
                   'Fator de Operação Comercial (iv) (F_COMERCIALp,j)',
                   'Código Perfil',
                   'Sigla' ,
                   'Nome Empresarial'];

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $data = [];
        $data212 = [];
        $dataExcecoes = [];
        $exclusoes = [];
        $setInicio = $this->util->import(12, $sheet, $file);

        $inicio = $this->utilCcee->encontra_tabela($setInicio, $tabela)[0];
        $rowData = $this->util->import($inicio + 14, $sheet, $file);
        unset ($rowData[0]);
        foreach ($rowData as $key => $item)
        {
            $data[$key] = array_combine($indice, array_slice($rowData[$key], 1, 20));
            $data[$key]['Ano'] = $ano;

            if (!$data[$key]['Código do Ativo']) {
                $data = $this->util->celulaMesclada($data, 'Código do Ativo', 1);
                $data = $this->util->celulaMesclada($data, 'Sigla do Ativo', 1);
                $data = $this->util->celulaMesclada($data, 'CEG do empreendimento', 1);
            }

           $data = $this->utilCcee->add_secundarios($data, $key);
        }

        $rowDataGeracao = $this->util->import($inicio + 15, $sheet, $file);
        foreach ($rowDataGeracao as $keys => $conteudo)
        {
            $dataGeracao = $this->util->formata_valores(array_slice($conteudo, 1, 12));
            $data[$keys + 1]['Geração no centro de gravidade (v) - MWh (Gp,j)'] = array_combine($meses, $dataGeracao);

            if ($data[$keys + 1]['Código do Ativo'] === 244.0 ||
                $data[$keys + 1]['Código do Ativo'] === 331.0 ||
                $data[$keys + 1]['Código do Ativo'] === 543.0) {
                $dataExcecoes[] = $this->utilCcee->addExcecoesUsinas($data[$keys + 1]);
                $exclusoes[] = $keys + 1;
            }
            elseif ($data[$keys + 1]['Código do Ativo'] === 212.0) {
                $data212[] = $data[$keys + 1];
                $exclusoes[] = $keys + 1;
            }
            foreach ($exclusoes as $exclusao) {
                if ($keys + 1 === $exclusao) {
                    unset($data[$keys +1]);
                }
            }

        }

        $dataExcecoes[] = $this->utilCcee->addExcecao212($data212);
        $excecoes = $this->utilCcee->junta_excecoes($dataExcecoes);

        $data = array_merge($data, $excecoes);

        return $data;
    }

    public function geracao_usinas_2015($file, $sheet, $tabela, $ano) // comentar dados retirados do CEG na function add_secundarios da class UtilCcee
    {
        $indice = ['Código do Ativo',
                   'Sigla do Ativo',
                   'Código da parcela da Usina',
                   'Parcela de Usina',
                   'Tipo de Despacho',
                   'Participante do Rateio de Perdas',
                   'Fonte de Energia Primária',
                   'Submercado',
                   'UF',
                   'Característica da Parcela',
                   'Participante do MRE',
                   'Participante do Regima de Cotas',
                   '% de Desconto',
                   'Capacidade da Usina (i) - MW (CAP_T)',
                   'Garantia Física (ii) MW médio (GF)',
                   'Fator de Operação Comercial (iv) (F_COMERCIALp,j)',
                   'Código Perfil',
                   'Sigla' ,
                   'Nome Empresarial'];

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $data = [];
        $data212 = [];
        $dataExcecoes = [];
        $exclusoes = [];
        $setInicio = $this->util->import(12, $sheet, $file);

        $inicio = $this->utilCcee->encontra_tabela($setInicio, $tabela)[0];
        $rowData = $this->util->import($inicio + 14, $sheet, $file);

        unset ($rowData[0]);
        foreach ($rowData as $key => $item)
        {
            $data[$key] = array_combine($indice, array_slice($rowData[$key], 1, 19));
            $data[$key]['Ano'] = $ano;

            if (!$data[$key]['Código do Ativo']) {
                $data = $this->util->celulaMesclada($data, 'Código do Ativo', 1);
                $data = $this->util->celulaMesclada($data, 'Sigla do Ativo', 1);
            }

           $data = $this->utilCcee->add_secundarios_ate_2015($data, $key);
        }

        $rowDataGeracao = $this->util->import($inicio + 15, $sheet, $file);
        foreach ($rowDataGeracao as $keys => $conteudo)
        {
            $dataGeracao = $this->util->formata_valores(array_slice($conteudo, 1, 12));
            $data[$keys + 1]['Geração no centro de gravidade (v) - MWh (Gp,j)'] = array_combine($meses, $dataGeracao);

            if ($data[$keys + 1]['Código do Ativo'] === 244.0 ||
                $data[$keys + 1]['Código do Ativo'] === 331.0) {
                $dataExcecoes[] = $this->utilCcee->addExcecoesUsinas($data[$keys + 1]);
                $exclusoes[] = $keys + 1;
            }
            if ($data[$keys + 1]['Código do Ativo'] === 212.0) {
                $data212[] = $data[$keys + 1];
                $exclusoes[] = $keys + 1;
            }

            foreach ($exclusoes as $exclusao) {
                if ($keys + 1 === $exclusao) {
                    unset($data[$keys +1]);
                }
            }
        }

        $dataExcecoes[] = $this->utilCcee->addExcecao212_2015($data212);
        $excecoes = $this->utilCcee->junta_excecoes($dataExcecoes);

        $data = array_merge($data, $excecoes);
        return $data;
    }

    public function historico_infoMercado_2013e2014($file, $sheet, $tabela, $ano)
    {
        $indice = ['Código do Ativo',
                   'Sigla do Ativo',
                   'Código da parcela da Usina',
                   'Parcela de Usina',
                   'Tipo de Despacho',
                   'Participante do Rateio de Perdas',
                   'Fonte de Energia Primária',
                   'Submercado',
                   'UF',
                   'Característica da Parcela',
                   'Participante do MRE',
                   'Participante do Regima de Cotas',
                   'Capacidade da Usina (i) - MW (CAP_T)',
                   'Garantia Física (ii) MW médio (GF)',
                   'Fator de Operação Comercial (iv) (F_COMERCIALp,j)',
                   'Código Perfil',
                   'Sigla' ,
                   'Nome Empresarial'];

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $data = [];
        $data212 = [];
        $dataExcecoes = [];
        $exclusoes = [];
        $setInicio = $this->util->import(12, $sheet, $file);

        $inicio = $this->utilCcee->encontra_tabela($setInicio, $tabela)[0];
        $rowData = $this->util->import($inicio + 14, $sheet, $file);

        unset ($rowData[0]);
        foreach ($rowData as $key => $item)
        {
            $data[$key] = array_combine($indice, array_slice($rowData[$key], 1, 18));
            $data[$key]['Ano'] = $ano;

            if (!$data[$key]['Parcela de Usina']) {
                unset($data[$key]);
            }
            if (isset($data[$key])) {
                if (!$data[$key]['Código do Ativo']) {
                    $data = $this->util->celulaMesclada($data, 'Código do Ativo', 3);
                    $data = $this->util->celulaMesclada($data, 'Sigla do Ativo', 3);
                }

            $data = $this->utilCcee->add_secundarios_ate_2015($data, $key);
            }
        }

        $rowDataGeracao = $this->util->import($inicio + 15, $sheet, $file);
        foreach ($rowDataGeracao as $keys => $conteudo)
        {
            if ($rowDataGeracao[$keys]['0']) {
                $dataGeracao[$keys] = array_slice($conteudo, 1, 12);

                if (fmod($keys, 3) === 2.0) {
                    $data[$keys - 1]['Geração no centro de gravidade (v) - MWh (Gp,j)'] = $this->util->formata_valores_mwh(
                        array_combine($meses, array_slice($this->utilCcee->calcula_geracao_consolidada(
                            $rowDataGeracao[$keys - 2], $rowDataGeracao[$keys - 1], $rowDataGeracao[$keys]), 1)));

                    if ($data[$keys - 1]['Código do Ativo'] === 244.0 ||
                        $data[$keys - 1]['Código do Ativo'] === 331.0) {
                        $dataExcecoes[] = $this->utilCcee->addExcecoesUsinas($data[$keys - 1]);
                        $exclusoes[] = $keys - 1;
                    }
                    if ($data[$keys - 1]['Código do Ativo'] === 212.0) {
                        $data212[] = $data[$keys - 1];
                        $exclusoes[] = $keys - 1;
                    }
                    foreach ($exclusoes as $exclusao) {
                        if ($keys - 1 === $exclusao) {
                            unset($data[$keys - 1]);
                        }
                    }
                }
            }
        }
        $dataExcecoes[] = $this->utilCcee->addExcecao212_2015($data212);
        $excecoes = $this->utilCcee->junta_excecoes($dataExcecoes);

        $data = array_merge($data, $excecoes);

        return $data;
    }


    public  function leilao($file, $sheet)
    {
        $carbon = Carbon::now();
        $date_indice = $carbon->format('m_Y');

        $indices = ['ID de Negociação',
                    'Número de Leilão',
                    'Tipo de Leilão',
                    'Número de Edital',
                    'Produto',
                    'Sigla do Vendedor',
                    'Razão Social do Vendedor',
                    'CNPJ do Vendedor',
                    'Sigla do Comprador',
                    'Razão Social do  Comprador',
                    'CNPJ do Comprador',
                    'CEG',
                    'Nome da Usina',
                    'Situação',
                    'Nota Explicativa',
                    'Submercado do Registro do Contrato',
                    'Tipo de Usina',
                    'UF da Usina',
                    'Fonte Energética',
                    'Combustível ou Rio da Usina',
                    'Potência da Usina (MW)',
                    'Potência Final Instalada (MWmed)',
                    'Garantia Física da Usina (MWmed)',
                    'Energia Negociada por Contrato (MWh)',
                    'Energia Negociada por Contrato para o Ano A (MWmed)',
                    'Energia Negociada por Contrato para o Ano A + 1 (MWmed)',
                    'Energia Negociada por Contrato para o Ano A + 2 (MWmed)',
                    'Energia Negociada por Contrato para o Ano A + 3 (MWmed)',
                    'Energia Negociada por Contrato para os demais anos (MWmed)',
                    'Tipo de Contrato(QTD/DIS)',
                    'Montante financeiro negociado por contrato (em milhões R$)',
                    'Montante financeiro negociado por contrato atualizado (Reais em milhões)',
                    'Preço de Venda ou ICB na data do leilão (R$/MWh)',
                    'ICE (R$/MWh)',
                    'Data de Realização do leilão',
                    'IPCA na data do leilão',
                    'IPCA ' . $date_indice,
                    'Preço de venda atualizado (R$/MWh)',
                    'Receita fixa por contrato na data do leilão para o ano A (R$/ano)',
                    'Receita fixa por contrato na data do leilão para o ano A + 1 (R$/ano)',
                    'Receita fixa por contrato na data do leilão para os demais anos (R$/ano)',
                    'Data do Início de Suprimento',
                    'Data do Fim de Suprimento',
                    'Possibilidade de escalonamento da entrega da energia do contrato (SIM/NÃO)',
                    'Entrega escalonada (SIM/NÃO)'
        ];

        $data = [];
        $rowData = $this->util->import(10, $sheet, $file);
        foreach ($rowData as $key => $item)
        {
            $data[$key] = array_combine($indices,  array_slice($item, 1));

            $inicio = 2000 + $this->regexCcee->getSuprimento($data[$key]['Data do Início de Suprimento']);
            $fim = 2000 + $this->regexCcee->getSuprimento($data[$key]['Data do Fim de Suprimento']);

            $data[$key]['Energia Negociada por Contrato para '. ($inicio) .' (MWh)'] =
                $this->utilCcee->calcula_anual($data[$key]['Data do Início de Suprimento'], $data[$key]['Energia Negociada por Contrato para o Ano A (MWmed)']);
            $data[$key]['Energia Negociada por Contrato para '. ($inicio) .' (MWh) po Mês'] =
                $this->utilCcee->calcula_mensal_anoA($data[$key]['Energia Negociada por Contrato para o Ano A (MWmed)'], $data[$key]['Data do Início de Suprimento']);

            for ($i = 1; $i < $fim - $inicio; $i++){
                $diasAno = $this->util->diasAno($inicio + $i);
                if ($i < 4) {
                    $data[$key]['Energia Negociada por Contrato para '. ($inicio + $i) .' (MWh)'] =
                        $data[$key]['Energia Negociada por Contrato para o Ano A + '. $i .' (MWmed)'] * 24 * $diasAno;
                    $data[$key]['Energia Negociada por Contrato para '. ($inicio + $i) .' (MWh)'] =
                        $this->utilCcee->calcula_mensal_primeirosAnos($data[$key]['Energia Negociada por Contrato para o Ano A + '. $i .' (MWmed)'] * 24 * $diasAno);
                } else {
                    $data[$key]['Energia Negociada por Contrato para '. ($inicio + $i) .' (MWh)'] =
                        $data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)'] * 24 * $diasAno;
                    $data[$key]['Energia Negociada por Contrato para '. ($inicio + $i) .' (MWh)'] =
                        $this->utilCcee->calcula_mensal_primeirosAnos($data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)'] * 24 * $diasAno);
                }
            }
            $data[$key]['Energia Negociada por Contrato para '. ($fim) .' (MWh)'] =
                $this->utilCcee->calcula_anual($data[$key]['Data do Fim de Suprimento'], 0, $data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)']);
            $data[$key]['Energia Negociada por Contrato para '. ($fim) .' (MWh)'] =
                $this->utilCcee->calcula_mensal_ultimoAno($data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)'], $data[$key]['Data do Fim de Suprimento']);

            $data[$key]['Data de Realização do leilão'] = $this->util->dateEdit($data[$key]['Data de Realização do leilão']);
            $data[$key]['Data do Início de Suprimento'] = $this->util->dateEdit($data[$key]['Data do Início de Suprimento']);
            $data[$key]['Data do Fim de Suprimento'] = $this->util->dateEdit($data[$key]['Data do Fim de Suprimento']);

            unset ($data[$key]['Energia Negociada por Contrato (MWh)']);
            unset ($data[$key]['Energia Negociada por Contrato para o Ano A + 1 (MWmed)']);
            unset ($data[$key]['Energia Negociada por Contrato para o Ano A + 2 (MWmed)']);
            unset ($data[$key]['Energia Negociada por Contrato para o Ano A + 3 (MWmed)']);
            unset ($data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)']);
        }

        return $data;
    }

}