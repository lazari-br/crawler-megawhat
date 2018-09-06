<?php

namespace Crawler\Util;


use Carbon\Carbon;
use Crawler\Regex\RegexCcee;

class UtilCcee extends Util
{
    public function addExcecoesUsinas($data)
    {
        $indices = ['Sigla do Ativo', 'CEG do empreendimento',  'Parcela de Usina'];
        $dataExplodida = [];
        $dados[] = $data;
        foreach ($data as $key => $info)
        {
            foreach ($indices as $keys => $item)
            {
                if($key === $item) {
                    if (strpbrk($info, ',')) {
                        $dataExplodida[$key] = $explode = explode(',', $data[$key]);
                        $dados = $this->helpAddExcecoes($dataExplodida, $key, $item, $dados);
                    } elseif (strpbrk($info, '+')) {
                        $dataExplodida[$key] = $explode = explode('+', $data[$key]);
                        $dados = $this->helpAddExcecoes($dataExplodida, $key, $item, $dados);
                    } elseif (strpbrk($info, '([0-9]){2}.e')) {
                        $dataExplodida[$key] = $explode = explode('e', $data[$key]);
                        $dados = $this->helpAddExcecoes($dataExplodida, $key, $item, $dados);
                    }
                }
            }
        }

        $dados = $this->calculoGeracao($dados, $data);

        return $dados;
    }

    public function helpAddExcecoes ($dataExplodida, $key, $item, $data)
    {
        $dados = [];
        $linhas = count($dataExplodida[$key]);

        for ($i = 0; $i < $linhas; $i++) {
            if ($dataExplodida[$key]) {
                $data[$i] = $data[0];
                $dados[$i] = $dataExplodida[$item][$i];
                $data[$i][$key] = $dados[$i];
            }
        }
        foreach ($data as $chave => $info) {
            $data[$chave] = $this->geracaoExcecoes($info, $chave);
        }

        return $data;
    }

    public function geracaoExcecoes($data, $chave)
    {
        $parametro = $data['Código do Ativo'];

        if ($parametro === 244.0){
            if($chave === 1) {
                $data['Capacidade da Usina (i) - MW (CAP_T)'] = $data['Garantia Física (ii) MW médio (GF)'] = 0;
            }
        } elseif ($parametro === 331.0){
            if($chave === 0) {
                $data['Capacidade da Usina (i) - MW (CAP_T)'] = 889;
            } else {
                $data['Capacidade da Usina (i) - MW (CAP_T)'] = 79.25;
                $data['Garantia Física (ii) MW médio (GF)'] = 0;
            }
        }
        if ($parametro === 543.0) {
            if ($chave === 0) {
                $data['Capacidade da Usina (i) - MW (CAP_T)'] = 0.772;
            }
            if ($chave === 1) {
                $data['Capacidade da Usina (i) - MW (CAP_T)'] = 0.114;
            }
        }
        return $data;
    }

    public function calculoGeracao($dados, $data)
    {
        foreach ($dados as $key => $item) {
            foreach ($item['Geração no centro de gravidade (v) - MWh (Gp,j)'] as $mes => $info) {
                if ($data['Capacidade da Usina (i) - MW (CAP_T)'] !== 0) {
                    $dados[$key]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$mes] =
                        (float)$data['Geração no centro de gravidade (v) - MWh (Gp,j)'][$mes] *
                        (float)$dados[$key]['Capacidade da Usina (i) - MW (CAP_T)'] /
                        (float)$data['Capacidade da Usina (i) - MW (CAP_T)'];
                } else {
                    $dados[$key]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$mes] = null;
                }
            }
        }
        return $dados;
    }

    public function calculoGeracaoPorPatamar($dados, $data)
    {
        foreach ($dados as $key => $item) {
            foreach ($item['Geração no centro de gravidade (v) - MWh (Gp,j)'] as $chave => $patamar) {
                foreach ($patamar as $mes => $info){
                    $dados[$key]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$chave][$mes] =
                        (float)$data['Geração no centro de gravidade (v) - MWh (Gp,j)'][$chave][$mes] *
                        (float)$dados[$key]['Capacidade da Usina (i) - MW (CAP_T)'] /
                        (float)$data['Capacidade da Usina (i) - MW (CAP_T)'];
                }
            }
        }
        return $dados;
    }

    public function addExcecao212($dados)
    {
        $data = [];
        $usina = 'Paulo Afonso ';
        $cegs = explode(', ', $dados[0]['CEG do empreendimento']);

        foreach ($cegs as $i => $ceg)
        {
            $data[$i] = $dados[0];
            $data[$i]['Sigla do Ativo'] = $data[$i +1]['Parcela de Usina'] = $usina . ($i + 1);
            $data[$i]['CEG do empreendimento'] = $ceg;
        }

        $data[5] = $data[3];
        $data[4]['Sigla do Ativo'] = $data[5]['Parcela de Usina'] = 'Apolonio Sales (Moxotó)';

        $data[0]['Capacidade da Usina (i) - MW (CAP_T)'] = 180.001;
        $data[1]['Capacidade da Usina (i) - MW (CAP_T)'] = 443;
        $data[2]['Capacidade da Usina (i) - MW (CAP_T)'] = 794.2;
        $data[3]['Capacidade da Usina (i) - MW (CAP_T)'] = 2367.855;
        $data[4]['Capacidade da Usina (i) - MW (CAP_T)'] = 400;
        $data[5]['Capacidade da Usina (i) - MW (CAP_T)'] = 94.545;

        $data[0]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[1]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[2]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[3]['Garantia Física (ii) MW médio (GF)'] = 2225;
        $data[4]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[5]['Garantia Física (ii) MW médio (GF)'] = 46.698;

        $data = $this->calculaGeracao212($dados, $data);

        return $data;
    }

    public function addExcecao212_2015($dados)
    {
        $usina = 'Paulo Afonso ';

        $data = [];
        for ($i = 0; $i < 5; $i++) {
            $data[$i] = $dados[0];
            $data[$i]['Sigla do Ativo'] = $usina . ($i + 1);
            $data[$i]['Parcela de Usina'] = $usina . ($i + 1);
        }
        $data[5] = $dados[0];
        $data[4]['Sigla do Ativo'] = $data[4]['Parcela de Usina'] = $data[3]['Parcela de Usina'];
        $data[4]['Código da parcela da Usina'] = $dados[1]['Código da parcela da Usina'];
        $data[5]['Parcela de Usina'] = $data[5]['Sigla do Ativo'] = 'Apolonio Sales (Moxotó)';

        $data[0]['Capacidade da Usina (i) - MW (CAP_T)'] = 180.001;
        $data[1]['Capacidade da Usina (i) - MW (CAP_T)'] = 443;
        $data[2]['Capacidade da Usina (i) - MW (CAP_T)'] = 794.2;
        $data[3]['Capacidade da Usina (i) - MW (CAP_T)'] = 2367.855;
        $data[4]['Capacidade da Usina (i) - MW (CAP_T)'] = 400;
        $data[5]['Capacidade da Usina (i) - MW (CAP_T)'] = 94.545;

        $data[0]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[1]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[2]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[3]['Garantia Física (ii) MW médio (GF)'] = 2225;
        $data[4]['Garantia Física (ii) MW médio (GF)'] = 0.01;
        $data[5]['Garantia Física (ii) MW médio (GF)'] = 46.698;

        $data = $this->calculaGeracao212($dados, $data);

        return $data;
    }

    public function calculaGeracao212($dados, $data)
    {
        foreach ($data as $key => $item) {
            foreach ($data[$key]['Geração no centro de gravidade (v) - MWh (Gp,j)'] as $mes => $geracao)
            {
                $data[$key]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$mes] =
                    ((float)$dados[0]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$mes] +
                        (float)$dados[1]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$mes]) *
                    (float)$data[$key]['Capacidade da Usina (i) - MW (CAP_T)'] /
                    ((float)$dados[0]['Capacidade da Usina (i) - MW (CAP_T)'] +
                        (float)$dados[1]['Capacidade da Usina (i) - MW (CAP_T)']);
            }
        }
        return $data;
    }

    public function calculaGeracao212poPatamar($dados, $data)
    {
        foreach ($data as $key => $item) {
            foreach ($item['Geração no centro de gravidade (v) - MWh (Gp,j)'] as $chave => $patamar)
            {
                foreach ($patamar as $mes => $info){
                    $data[$key]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$chave][$mes] =
                        (float)($dados[0]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$chave][$mes] +
                            (float)$dados[1]['Geração no centro de gravidade (v) - MWh (Gp,j)'][$chave][$mes]) *
                        (float)$data[$key]['Capacidade da Usina (i) - MW (CAP_T)'] /
                        ((float)$dados[0]['Capacidade da Usina (i) - MW (CAP_T)'] +
                            (float)$dados[1]['Capacidade da Usina (i) - MW (CAP_T)']);
                }
            }
        }

        return $data;
    }

    public function calcula_geracao_consolidada ($data1, $data2, $data3)
    {
        foreach ($data1 as $key => $item) {
            $result[] = (float)$data1[$key] + (float)$data2[$key] + (float)$data3[$key];
        }
        return $result;
    }

    public function junta_excecoes($dataExcecoes)
    {
        $dados = [];
        foreach ($dataExcecoes as $chave => $excecao) {
            foreach ($excecao as $key => $linha) {
                $dados[] = $linha;
            }
        }
        return $dados;
    }

    public function calcula_anual($date, $valorVigente, $valorAntigo = null)
    {
        $dataEdit = \DateTime::createFromFormat('d-m-y', $date);
        $dateFormat = $dataEdit->format('d-m-Y');

        $date1 = new \DateTime($dateFormat. ' 00:00:00');
        $date2 = new \DateTime('31-12-' . $dataEdit->format('Y'));

        $diff = $date1->diff($date2);
        $dias = $diff->days;

        $ano = $this->diasAno($dataEdit->format('Y'));
        $media = (($ano - $dias) * $valorAntigo + $dias * $valorVigente)*24;

        return $media;
    }

    public function encontra_tabela($array, $tabela)
    {
        $inicio = '';
        $fim = '';

        foreach ($array as $key => $item) {
            if ((stripos($array[$key][array_keys($array[0])[1]], $tabela)) !== false) {
                $inicio = $key;
            }  elseif (stripos($array[$key][array_keys($array[0])[1]], 'Total') !== false) {
                $fim = $key;
                if ($inicio){
                    break;
                }
            }
        }

        if ($inicio === '') {
            return response()->json(['Error:' => 'A tabela '.$tabela.' não foi encontrada!']);
        }

        $limite = array($inicio, $fim);
        return $limite;
    }

    public function calcula_mensal_primeirosAnos($mwmed)
    {
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::parse()->daysInMonth,
            'Março' => 31,
            'Abril' => 30,
            'Maio' => 31,
            'Junho' => 30,
            'Julho' => 31,
            'Agosto' => 31,
            'Setembro' => 30,
            'Outubro' => 31,
            'Novembro' => 30,
            'Dezembro' => 31
        ];

        $valor  = [];
        foreach ($daysInMonths as $mes => $dia) {
            $valor[$mes] = number_format($mwmed * 24 * $daysInMonths[$mes], 10, ',', '.');

        }

        return $valor;
    }

    public function  calcula_mensal_anoA ($mwmed, $date)
    {
        $valor  = [];
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            '1' => 31,
            '2' => 28,
            '3' => 31,
            '4' => 30,
            '5' => 31,
            '6' => 30,
            '7' => 31,
            '8' => 31,
            '9' => 30,
            '10' => 31,
            '11' => 30,
            '12' => 31
        ];

        $carbon = \Carbon\Carbon::createFromFormat('d-m-Y', $date);
        $ano = $carbon->format('Y');

        foreach ($daysInMonths as $mes => $dia)
        {
            $data = \Carbon\Carbon::createFromFormat('d/m/Y', $daysInMonths[$mes].'/'.$mes.'/'.$ano);

            if ($carbon->diffInMonths($data, false) > 0) {
                $valor[$meses[$mes-1]] = number_format($mwmed * 24 * $daysInMonths[$mes], 10, ',', '.');
            }
            elseif ($carbon->diffInMonths($data, false) === 0)
            {
                if($carbon->diffInDays($data, false) > 0){
                    $valor[$meses[$mes-1]] = number_format($mwmed * 24 * $carbon->diffInDays($data, false), 10, ',', '.');
                }
            }
            else {
                $valor[$meses[$mes-1]] = null;
            }
        }

        return $valor;
    }

    public function calcula_mensal_ultimoAno($mwmed, $date)
    {
        $valor  = [];
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            '1' => 31,
            '2' => 28,
            '3' => 31,
            '4' => 30,
            '5' => 31,
            '6' => 30,
            '7' => 31,
            '8' => 31,
            '9' => 30,
            '10' => 31,
            '11' => 30,
            '12' => 31
        ];

        $carbon = \Carbon\Carbon::createFromFormat('d-m-Y', $date);
        $ano = $carbon->format('Y');

        foreach ($daysInMonths as $mes => $dia)
        {
            $data = \Carbon\Carbon::createFromFormat('d/m/Y', $daysInMonths[$mes].'/'.$mes.'/'.$ano);

            if ($carbon->diffInMonths($data, false) < 0) {
                $valor[$meses[$mes-1]] = number_format($mwmed * 24 * $daysInMonths[$mes], 10, ',', '.');
            }
            elseif ($carbon->diffInMonths($data, false) === 0)
            {
                if($carbon->diffInDays($data, false) > 0){
                    $valor[$meses[$mes-1]] =
                        number_format($mwmed * 24 * ($daysInMonths[$mes] - $carbon->diffInDays($data, false)), 10, ',', '.');
                }
            }
            else {
                $valor[$meses[$mes-1]] = null;
            }
        }

        return $valor;
    }

    public function add_secundarios($data, $key)
    {
        $regexCcee = new RegexCcee;

        $data[$key]['Sub-CEG do empreendimento'] = $regexCcee->get_sub_ceg($data[$key]['CEG do empreendimento']);
        $data[$key]['Tipo de Usina'] = $regexCcee->get_tipo_usina($data[$key]['CEG do empreendimento']);

        if($data[$key]['Fator de Operação Comercial (iv) (F_COMERCIALp,j)'] &&
            $data[$key]['Capacidade da Usina (i) - MW (CAP_T)']) {
            $data[$key]['Capacidade Instalada da Usina Aplicada FO'] =
                (float)$data[$key]['Fator de Operação Comercial (iv) (F_COMERCIALp,j)'] * (float)$data[$key]['Capacidade da Usina (i) - MW (CAP_T)'];
        } else {
            $data[$key]['Capacidade Instalada da Usina Aplicada FO'] = null;
        }
        if ($data[$key]['Garantia Física (ii) MW médio (GF)'] &&
            $data[$key]['Capacidade da Usina (i) - MW (CAP_T)']) {
            $data[$key]['Garantia física da Usina Aplicada FO'] =
                (float)$data[$key]['Garantia Física (ii) MW médio (GF)'] * (float)$data[$key]['Fator de Operação Comercial (iv) (F_COMERCIALp,j)'];
        } else {
            $data[$key]['Garantia física da Usina Aplicada FO'] = null;
        }
        if ($data[$key]['Garantia física da Usina Aplicada FO'] &&
            $data[$key]['Capacidade Instalada da Usina Aplicada FO']) {
            $data[$key]['Fator de Capacidade (FC)'] =
                (float)$data[$key]['Garantia física da Usina Aplicada FO'] / (float)$data[$key]['Capacidade Instalada da Usina Aplicada FO'];
        } else {
            $data[$key]['Fator de Capacidade (FC)'] = null;
        }
        if ($data[$key]['Fator de Capacidade (FC)'] &&
            $data[$key]['Capacidade Instalada da Usina Aplicada FO']) {
            $data[$key]['Garantia Física Média'] =
                (float)$data[$key]['Fator de Capacidade (FC)'] * (float)$data[$key]['Capacidade Instalada da Usina Aplicada FO'];
        } else {
            $data[$key]['Garantia Física Média'] = null;
        }
        return $data;
    }
public function add_secundarios_ate_2015($data, $key)
    {
        if($data[$key]['Fator de Operação Comercial (iv) (F_COMERCIALp,j)'] &&
            $data[$key]['Capacidade da Usina (i) - MW (CAP_T)']) {
            $data[$key]['Capacidade Instalada da Usina Aplicada FO'] =
                (float)$data[$key]['Fator de Operação Comercial (iv) (F_COMERCIALp,j)'] * (float)$data[$key]['Capacidade da Usina (i) - MW (CAP_T)'];
        } else {
            $data[$key]['Capacidade Instalada da Usina Aplicada FO'] = null;
        }
        if ($data[$key]['Garantia Física (ii) MW médio (GF)'] &&
            $data[$key]['Capacidade da Usina (i) - MW (CAP_T)']) {
            $data[$key]['Garantia física da Usina Aplicada FO'] =
                (float)$data[$key]['Garantia Física (ii) MW médio (GF)'] * (float)$data[$key]['Fator de Operação Comercial (iv) (F_COMERCIALp,j)'];
        } else {
            $data[$key]['Garantia física da Usina Aplicada FO'] = null;
        }
        if ($data[$key]['Garantia física da Usina Aplicada FO'] &&
            $data[$key]['Capacidade Instalada da Usina Aplicada FO']) {
            $data[$key]['Fator de Capacidade (FC)'] =
                (float)$data[$key]['Garantia física da Usina Aplicada FO'] / (float)$data[$key]['Capacidade Instalada da Usina Aplicada FO'];
        } else {
            $data[$key]['Fator de Capacidade (FC)'] = null;
        }
        if ($data[$key]['Fator de Capacidade (FC)'] &&
            $data[$key]['Capacidade Instalada da Usina Aplicada FO']) {
            $data[$key]['Garantia Física Média'] =
                (float)$data[$key]['Fator de Capacidade (FC)'] * (float)$data[$key]['Capacidade Instalada da Usina Aplicada FO'];
        } else {
            $data[$key]['Garantia Física Média'] = null;
        }
        return $data;
    }

}
