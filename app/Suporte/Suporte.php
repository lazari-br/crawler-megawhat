<?php

namespace Crawler\Suporte;


use Maatwebsite\Excel\Excel;


class Suporte
{
    private $excel;
    private  $meses = ['Janeiro',
                       'Fevereiro',
                       'Março',
                       'Abril',
                       'Maio',
                       'Junho',
                       'Julho',
                       'Agosto',
                       'Setembro',
                       'Outubro',
                       'Novembro',
                       'Dezembro'];


    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }


    public function importExcel($inicio, $fim, $arquivo, $aba)
    {
        $this->setConfigStartRow($inicio);
        $dados = \Excel::selectSheetsByIndex($aba)
            ->load($arquivo, function ($reader) use($fim){
                $reader->limitRows($fim);
            })
            ->get()
            ->toArray();

        return $dados;
    }

    public function corrigeCelulaMesclada($array, $chave)
    {
        foreach ($array as $key => $info)
        {
            if ($array[$key][$chave] === null)
            {
                $array[$key][$chave] = $array[$key-1][$chave];
            }
        }

        return $array;
    }


    public function combinaValoresPmoUsina($array, $chaves, $quantidade, $data)
    {
        foreach ($array as $key => $item)
        {
            for ($i = 0; $i = $quantidade; $i++)
            {
                if ($item['origem'])
                {
                    $x = 5;

                    $usina = array_combine($chaves, [$item['tipo'], $item['origem'], $item['subsistema'], $item['usina'], $item['merc.']]);
                    $dados['Valores'][$data + $i] = array_combine($this->meses, array_slice($item, $x, 12));
                    $data[$item['usina']] = array_merge($usina, $dados);

                    $x = $x + 12;
                }
                else
                {
                    $x = 3;

                    $usina = array_combine($chaves, [$item['tipo'], $item['subsistema'], $item['usina']]);
                    $dados['Valores'][$data + $i] = array_combine($this->meses, array_slice($item, $x, 12));
                    $data[$item['usina']] = array_merge($usina, $dados);

                    $x = $x + 12;
                }
            }
        }
        return $data;
    }


}