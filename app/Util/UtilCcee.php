<?php

namespace Crawler\Util;


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
            foreach ($item['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'] as $chave => $patamar) {
                foreach ($patamar as $mes => $info){
                    $dados[$key]['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'][$chave][$mes] =
                        $data['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'][$chave][$mes] *
                        $dados[$key]['Capacidade da Usina (i) - MW (CAP_T)'] /
                        $data['Capacidade da Usina (i) - MW (CAP_T)'];
                }
            }
        }
        return $dados;
    }

    public function addExcecao212($dados, $chave, $fim)
    {
        $data = [];
        $usina = 'Paulo Afonso ';
        $cegs = explode(', ', $dados[0]['CEG do empreendimento']);

        foreach ($cegs as $i => $ceg)
        {
            $data[$i] = $dados[0];
            $data[$i]['Sigla do Ativo'] = $data[$i + 1]['Parcela de Usina'] = $usina . ($i + 1);
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

    public function calculaGeracao212($dados, $data)
    {
        foreach ($data as $key => $item) {
            foreach ($item['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'] as $chave => $patamar) {
                foreach ($patamar as $mes => $info){
                    $data[$key]['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'][$chave][$mes] =
                        ($dados[0]['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'][$chave][$mes] +
                            $dados[1]['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'][$chave][$mes]) *
                        $data[$key]['Capacidade da Usina (i) - MW (CAP_T)'] /
                        ($dados[0]['Capacidade da Usina (i) - MW (CAP_T)'] + $dados[1]['Capacidade da Usina (i) - MW (CAP_T)']);
                }
            }
        }
        return $data;
    }

    public function calculaDias($date, $valorVigente, $valorAntigo = null)
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

}
