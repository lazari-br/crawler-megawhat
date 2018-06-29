<?php

namespace Crawler\Http\Controllers;

use Crawler\Model\ArangoDb;
use Illuminate\Http\Request;
use Carbon\Carbon;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;


class NewaveController extends Controller
{

    private $arangoDb;

    public function __construct(ArangoDb $arangoDb)
    {
        $this->arangoDb = $arangoDb;
    }

    function cargaNewWave()
    {

        $date = Carbon::now()->format('Y-m-d');

        $regex = new \Crawler\Regex\RegexCceeNewaveDecomp();
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $rowLimpo = implode($regex->limpaString(file('/var/www/html/crawler-doc88/app/SISTEMA.DAT')));
        $limpo = $rowLimpo;

        $mercadoIsolado = $regex->setFimNewave($regex->setInicioNewave($limpo));

        $subSeco = $regex->setSeCo($mercadoIsolado);
        $subS = $regex->setS($mercadoIsolado);
        $subNe = $regex->setNe($mercadoIsolado);
        $subN = $regex->setN($mercadoIsolado);

        //Sudeste/Centro-Oeste
        $rowSeco = explode(" ", $regex->setAno1($subSeco));
        $rowOutrosSeco = explode(" ", $regex->setOutrosAnos($subSeco));

                foreach($rowSeco as $key=>$item){
                    $ano1Seco[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
                }

                foreach($rowOutrosSeco as $key=>$item){
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

                foreach($rowS as $key=>$item){
                    $ano1S[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
                }

                foreach($rowOutrosS as $key=>$item){
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

                foreach($rowNe as $key=>$item){
                    $ano1Ne[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
                }

                foreach($rowOutrosNe as $key=>$item){
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

                foreach($rowN as $key=>$item){
                    $ano1N[] = number_format(preg_replace("/(\.)/", "", $item), 3, ",", ".");
                }

                foreach($rowOutrosN as $key=>$item){
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

        //Exportação para o banco
        try {
            if ($this->arangoDb->collectionHandler()->has('newave')) {

                $this->arangoDb->documment()->set('carga', [$date => $data]);
                $this->arangoDb->documentHandler()->save('newave', $this->arangoDb->documment());
            } else {
                $this->arangoDb->collection()->setName('newave');
                $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());

                $this->arangoDb->documment()->set('carga', [$date => $data]);
                $this->arangoDb->documentHandler()->save('newave', $this->arangoDb->documment());
            }



        }  catch
        (ArangoConnectException $e) {
            print 'Connection error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoClientException $e) {
            print 'Client error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoServerException $e) {
            print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
        }

        return response()->json(
            [
                'palavra_chave' => 'dados de carga newave',
                'responsabilidade' => 'O crawler realiza a captura dos dados fornecidos no newave',
                'status' => 'Crawler Aneel realizado com sucesso!'
            ]
        );

    }

}
