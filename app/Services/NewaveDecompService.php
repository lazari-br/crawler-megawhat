<?php

namespace Crawler\Services;

use Crawler\Model\ArangoDb;
use Illuminate\Http\Request;
use Carbon\Carbon;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;


class NewaveDecompService
{

    private $arangoDb;

    public function __construct(ArangoDb $arangoDb)
    {
        $this->arangoDb = $arangoDb;
    }

    function MWh($x, $y)
    {
        $i = 0;
        do {
            $result[] = number_format($x[$i] * $y[$i], 3, ',', '.');
            $i++;
        } while ($i < (count($x)) && $i < (count($y)));

        return $result;
    }

    function unzip($arquivo_zip, $origem, $destino)
    {
        copy($origem . $arquivo_zip, $destino .  $arquivo_zip);
        chdir($destino);
        shell_exec("unzip $arquivo_zip -o");
    }


    function cargaNewWave($arquivo)
    {
        $regex = new \Crawler\Regex\RegexCceeNewaveDecomp();
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


        $rowLimpo = implode($regex->limpaString($arquivo));
        $limpo = preg_replace('/POS/', '', $rowLimpo);

        $mercadoIsolado = $regex->setFimNewave($regex->setInicioNewave($limpo));

        $subSeco = $regex->setSeCo($mercadoIsolado);
        $subS = $regex->setS($mercadoIsolado);
        $subNe = $regex->setNe($mercadoIsolado);
        $subN = $regex->setN($mercadoIsolado);

        //Sudeste/Centro-Oeste
        $rowSeco = explode(" ", $regex->setAno1($subSeco));
        $rowOutrosSeco = explode(" ", $regex->setOutrosAnos($subSeco));

        foreach ($rowSeco as $key => $item) {

            $ano1Seco[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        foreach ($rowOutrosSeco as $key => $item) {
            $outrosAnosSeco[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        $aSeco = count($ano1Seco);
        $bSeco = count($outrosAnosSeco);
        $qtMes = ($aSeco - $bSeco - 1);
        $qtdMeses = 12 - $qtMes;

        $i = 0;
        do {
            $dataSecoAno1[] = null;
            $i++;
        } while ($i < $qtdMeses);

        $i = 0;
        do {
            $dataSecoAno1[] = $ano1Seco[$i];
            $i++;
        } while ($i < $qtMes);

        $data['Sudeste/Centro-Oeste']['2018'] = array_combine($months, $dataSecoAno1);
        $data['Sudeste/Centro-Oeste']['2019'] = array_combine($months, array_slice($outrosAnosSeco, 0, 12));
        $data['Sudeste/Centro-Oeste']['2020'] = array_combine($months, array_slice($outrosAnosSeco, 13, 12));
        $data['Sudeste/Centro-Oeste']['2021'] = array_combine($months, array_slice($outrosAnosSeco, 26, 12));
        $data['Sudeste/Centro-Oeste']['2022'] = array_combine($months, array_slice($outrosAnosSeco, 39, 12));

        //Sul
        $rowS = explode(" ", $regex->setAno1($subS));
        $rowOutrosS = explode(" ", $regex->setOutrosAnos($subS));

        foreach ($rowS as $key => $item) {
            $ano1S[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        foreach ($rowOutrosS as $key => $item) {
            $outrosAnosS[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        $i = 0;
        do {
            $dataSAno1[] = null;
            $i++;
        } while ($i < $qtdMeses);

        $i = 0;
        do {
            $dataSAno1[] = $ano1S[$i];
            $i++;
        } while ($i < $qtMes);

        $data['Sul']['2018'] = array_combine($months, $dataSAno1);
        $data['Sul']['2019'] = array_combine($months, array_slice($outrosAnosS, 0, 12));
        $data['Sul']['2020'] = array_combine($months, array_slice($outrosAnosS, 13, 12));
        $data['Sul']['2021'] = array_combine($months, array_slice($outrosAnosS, 26, 12));
        $data['Sul']['2022'] = array_combine($months, array_slice($outrosAnosS, 39, 12));

        //Nordeste
        $rowNe = explode(" ", $regex->setAno1($subNe));
        $rowOutrosNe = explode(" ", $regex->setOutrosAnos($subNe));

        foreach ($rowNe as $key => $item) {
            $ano1Ne[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        foreach ($rowOutrosNe as $key => $item) {
            $outrosAnosNe[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        $i = 0;
        do {
            $dataNeAno1[] = null;
            $i++;
        } while ($i < $qtdMeses);

        $i = 0;
        do {
            $dataNeAno1[] = $ano1Ne[$i];
            $i++;
        } while ($i < $qtMes);

        $data['Nordeste']['2018'] = array_combine($months, $dataNeAno1);
        $data['Nordeste']['2019'] = array_combine($months, array_slice($outrosAnosNe, 0, 12));
        $data['Nordeste']['2020'] = array_combine($months, array_slice($outrosAnosNe, 13, 12));
        $data['Nordeste']['2021'] = array_combine($months, array_slice($outrosAnosNe, 26, 12));
        $data['Nordeste']['2022'] = array_combine($months, array_slice($outrosAnosNe, 39, 12));

        //Norte
        $rowN = explode(" ", $regex->setAno1($subN));
        $rowOutrosN = explode(" ", $regex->setOutrosAnos($subN));

        foreach ($rowN as $key => $item) {
            $ano1N[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        foreach ($rowOutrosN as $key => $item) {
            $outrosAnosN[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
        }

        $i = 0;
        do {
            $dataNAno1[] = null;
            $i++;
        } while ($i < $qtdMeses);

        $i = 0;
        do {
            $dataNAno1[] = $ano1N[$i];
            $i++;
        } while ($i < $qtMes);

        $data['Norte']['2018'] = array_combine($months, $dataNAno1);
        $data['Norte']['2019'] = array_combine($months, array_slice($outrosAnosN, 0, 12));
        $data['Norte']['2020'] = array_combine($months, array_slice($outrosAnosN, 13, 12));
        $data['Norte']['2021'] = array_combine($months, array_slice($outrosAnosN, 26, 12));
        $data['Norte']['2022'] = array_combine($months, array_slice($outrosAnosN, 39, 12));

        return $data;
    }


    function cargaDecomp($arquivo)
    {
        $date = Carbon::now()->format('Y-m-d');
        $regex = new \Crawler\Regex\RegexCceeNewaveDecomp();

        $limpo = $regex->setFimDecomp($a=$regex->setInicioDecomp(implode($regex->limpaString($arquivo))));
        $rowDataExplode = array_slice(explode(" ", $limpo), 28);

        foreach ($rowDataExplode as $key => $item)
        {
            if ($regex->validaDecomp($item) <> 'encontrado') {
                unset($rowDataExplode[$key]);
            }
        }

        $rowDataTot = array_values($rowDataExplode);
        $countRow = count($rowDataTot);
        $rowData = array_slice($rowDataTot, 0, $countRow - 30);

        $dataCarga = [];
        foreach ($rowData as $chave => $info) {
            if ($regex->validaDecompMult($info) === 'encontrado') {
                $dataCarga[] = number_format($rowData[$chave] * $rowData[$chave + 1],3,  ',', '.');
            }
        }

        foreach ($dataCarga as $keys => $item)
        {
            if ($keys % 3 === 0){
                $cargaPesada[] = $dataCarga[$keys];
                $cargaMedia[] = $dataCarga[$keys + 1];
                $cargaLeve[] = $dataCarga[$keys + 2];
            }
        }

        $qtidade = count($cargaLeve);
        $numSemana = $qtidade/4 - 1;

        for ($i = 1; $i <= $numSemana; $i++) {
            $data['Semana ' . $i] = $this->dataSemana($cargaLeve, $cargaMedia, $cargaPesada, $i);
        }

        return $data;
    }

    public function dataSemana($cargaLeve, $cargaMedia, $cargaPesada, $semana)
    {
        $subsistemas = ['Sudeste_Centro-Oeste', 'Sul', 'Nordeste', 'Norte'];

        $data = [];
        $subsist = 0;
        foreach ($subsistemas as $subsistema) {
            if ($subsistema === 'Sudeste_Centro-Oeste') {
                $subsist = 0;
                $data[$subsistema] = $this->dataSubsistema($cargaLeve, $cargaMedia, $cargaPesada, $subsist, $semana);

            } elseif ($subsistema === 'Sul') {
                $subsist = 1;
                $data[$subsistema] = $this->dataSubsistema($cargaLeve, $cargaMedia, $cargaPesada, $subsist, $semana);

            } elseif ($subsistema === 'Nordeste') {
                $subsist = 2;
                $data[$subsistema] = $this->dataSubsistema($cargaLeve, $cargaMedia, $cargaPesada, $subsist, $semana);

            } elseif ($subsistema === 'Norte') {
                $subsist = 3;
                $data[$subsistema] = $this->dataSubsistema($cargaLeve, $cargaMedia, $cargaPesada, $subsist, $semana);

            }
        }
        return $data;
    }

    public function dataSubsistema($cargaLeve, $cargaMedia, $cargaPesada, $subsist, $semana)
    {
        $data['Leve'] = $cargaLeve[$subsist + ($semana * 4)];
        $data['Médio'] = $cargaMedia[$subsist + ($semana * 4)];
        $data['Pesado'] = $cargaPesada[$subsist + ($semana * 4)];

        return $data;
    }


}
