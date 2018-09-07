<?php

namespace Crawler\Http\Controllers;

use Crawler\Model\ArangoDb;
use Illuminate\Http\Request;
use Carbon\Carbon;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;


class DecompController extends Controller
{

    private $arangoDb;

    public function __construct(ArangoDb $arangoDb)
    {
        $this->arangoDb = $arangoDb;
    }

    function cargaDecomp()
    {

        $date = Carbon::now()->format('Y-m-d');

        $regex = new \Crawler\Regex\RegexCceeNewaveDecomp();

        $limpo = $regex->setFimDecomp($regex->setInicioDecomp(implode($regex->limpaString(file('/var/www/html/crawler-doc88/app/DADGER.RV1')))));

        $rowData = array_slice(explode(" ", $limpo), 27);


        $sobra = ['36', '37', '38', '39', '40', '41', '42', '83', '84', '85', '86', '87', '88', '89', '130', '131', '132', '133', '134', '135', '136', '177', '178', '179', '180', '181', '182', '183', '224', '225', '226', '227', '228', '229', '230', '271', '272', '273', '274', '275','276', '277'];

            foreach ($sobra as $item) {
               unset ($rowData[$item]);
            }
//dd($rowData);

            $rowData = array_values($rowData);
            $count = count($rowData);

                for ($hP = 1; $hP < $count; $hP = $hP + 10)
                {
                    $horasP[] = $rowData[$hP];
                }

                for ($cP = 0; $cP < $count; $cP = $cP+ 10)
                {
                    $cargaP[] = $rowData[$cP];
                }

                for ($hM = 3; $hM < $count; $hM = $hM + 10)
                {
                    $horasM[] = $rowData[$hM];
                }

                for ($cM = 2; $cM < $count; $cM = $cM + 10)
                {
                    $cargaM[] = $rowData[$cM];
                }

                for ($hL = 5; $hL < $count; $hL = $hL + 10)
                {
                    $horasL[] = $rowData[$hL];
                }

                for ($cL = 4; $cL < $count; $cL = $cL + 10)
                {
                    $cargaL[] = $rowData[$cL];
                }


        function MWh($x, $y)
        {
            $i = 0;
            do {
                $result[] = number_format($x[$i] * $y[$i], 3, ',', '.');
                $i++;
                }
            while ($i < (count($x)) && $i < (count($y)));

                    return $result;
        };

                $cargaLeve = MWh($cargaL, $horasL);
                $cargaMedia = MWh($cargaM, $horasM);
                $cargaPesada = MWh($cargaP, $horasP);

                    $numSemana = count($cargaLeve);


        //3 primeiras semanas

            //Sudeste/Centro-Oeste
                $data['Sudeste/Centro-Oeste']['Total']['Leve'] = $cargaLeve[$numSemana-4];
                $data['Sudeste/Centro-Oeste']['Total']['Média'] = $cargaMedia[$numSemana-4];
                $data['Sudeste/Centro-Oeste']['Total']['Pesada'] = $cargaPesada[$numSemana-4];

                $data['Sudeste/Centro-Oeste']['Semana1']['Leve'] = $cargaLeve[0];
                $data['Sudeste/Centro-Oeste']['Semana1']['Média'] = $cargaMedia[0];
                $data['Sudeste/Centro-Oeste']['Semana1']['Pesada'] = $cargaPesada[0];

                $data['Sudeste/Centro-Oeste']['Semana2']['Leve'] = $cargaLeve[4];
                $data['Sudeste/Centro-Oeste']['Semana2']['Média'] = $cargaMedia[4];
                $data['Sudeste/Centro-Oeste']['Semana2']['Pesada'] = $cargaPesada[4];

                $data['Sudeste/Centro-Oeste']['Semana3']['Leve'] = $cargaLeve[8];
                $data['Sudeste/Centro-Oeste']['Semana3']['Média'] = $cargaMedia[8];
                $data['Sudeste/Centro-Oeste']['Semana3']['Pesada'] = $cargaPesada[8];

            //Sul
                $data['Sul']['Total']['Leve'] = $cargaLeve[$numSemana-3];
                $data['Sul']['Total']['Média'] = $cargaMedia[$numSemana-3];
                $data['Sul']['Total']['Pesada'] = $cargaPesada[$numSemana-3];

                $data['Sul']['Semana1']['Leve'] = $cargaLeve[1];
                $data['Sul']['Semana1']['Média'] = $cargaMedia[1];
                $data['Sul']['Semana1']['Pesada'] = $cargaPesada[1];

                $data['Sul']['Semana2']['Leve'] = $cargaLeve[5];
                $data['Sul']['Semana2']['Média'] = $cargaMedia[5];
                $data['Sul']['Semana2']['Pesada'] = $cargaPesada[5];

                $data['Sul']['Semana3']['Leve'] = $cargaLeve[9];
                $data['Sul']['Semana3']['Média'] = $cargaMedia[9];
                $data['Sul']['Semana3']['Pesada'] = $cargaPesada[9];

            //Nordeste
                $data['Nordeste']['Total']['Leve'] = $cargaLeve[$numSemana-2];
                $data['Nordeste']['Total']['Média'] = $cargaMedia[$numSemana-2];
                $data['Nordeste']['Total']['Pesada'] = $cargaPesada[$numSemana-2];

                $data['Nordeste']['Semana1']['Leve'] = $cargaLeve[2];
                $data['Nordeste']['Semana1']['Média'] = $cargaMedia[2];
                $data['Nordeste']['Semana1']['Pesada'] = $cargaPesada[2];

                $data['Nordeste']['Semana2']['Leve'] = $cargaLeve[6];
                $data['Nordeste']['Semana2']['Média'] = $cargaMedia[6];
                $data['Nordeste']['Semana2']['Pesada'] = $cargaPesada[6];

                $data['Nordeste']['Semana3']['Leve'] = $cargaLeve[10];
                $data['Nordeste']['Semana3']['Média'] = $cargaMedia[10];
                $data['Nordeste']['Semana3']['Pesada'] = $cargaPesada[10];

            //Norte
                $data['Norte']['Total']['Leve'] = $cargaLeve[$numSemana-1];
                $data['Norte']['Total']['Média'] = $cargaMedia[$numSemana-1];
                $data['Norte']['Total']['Pesada'] = $cargaPesada[$numSemana-1];

                $data['Norte']['Semana1']['Leve'] = $cargaLeve[3];
                $data['Norte']['Semana1']['Média'] = $cargaMedia[3];
                $data['Norte']['Semana1']['Pesada'] = $cargaPesada[3];

                $data['Norte']['Semana2']['Leve'] = $cargaLeve[7];
                $data['Norte']['Semana2']['Média'] = $cargaMedia[7];
                $data['Norte']['Semana2']['Pesada'] = $cargaPesada[7];

                $data['Norte']['Semana3']['Leve'] = $cargaLeve[11];
                $data['Norte']['Semana3']['Média'] = $cargaMedia[11];
                $data['Norte']['Semana3']['Pesada'] = $cargaPesada[11];


                        if ($numSemana == 20)
                        {
                        //Sudeste/Centro-Oeste
                            $data['Sudeste/Centro-Oeste']['Semana4']['Leve'] = $cargaLeve[12];
                            $data['Sudeste/Centro-Oeste']['Semana4']['Média'] = $cargaMedia[12];
                            $data['Sudeste/Centro-Oeste']['Semana4']['Pesada'] = $cargaPesada[12];

                        //Sul
                            $data['Sul']['Semana4']['Leve'] = $cargaLeve[13];
                            $data['Sul']['Semana4']['Média'] = $cargaMedia[13];
                            $data['Sul']['Semana4']['Pesada'] = $cargaPesada[13];

                        //Nordeste
                            $data['Nordeste']['Semana4']['Leve'] = $cargaLeve[14];
                            $data['Nordeste']['Semana4']['Média'] = $cargaMedia[14];
                            $data['Nordeste']['Semana4']['Pesada'] = $cargaPesada[14];

                        //Norte
                            $data['Norte']['Semana4']['Leve'] = $cargaLeve[15];
                            $data['Norte']['Semana4']['Média'] = $cargaMedia[15];
                            $data['Norte']['Semana4']['Pesada'] = $cargaPesada[15];
                        }
                        elseif ($numSemana == 24)
                        {
                            //Sudeste/Centro-Oeste
                            $data['Sudeste/Centro-Oeste']['Semana5']['Leve'] = $cargaLeve[16];
                            $data['Sudeste/Centro-Oeste']['Semana5']['Média'] = $cargaMedia[16];
                            $data['Sudeste/Centro-Oeste']['Semana5']['Pesada'] = $cargaPesada[16];

                            //Sul
                            $data['Sul']['Semana5']['Leve'] = $cargaLeve[17];
                            $data['Sul']['Semana5']['Média'] = $cargaMedia[17];
                            $data['Sul']['Semana5']['Pesada'] = $cargaPesada[17];

                            //Nordeste
                            $data['Nordeste']['Semana5']['Leve'] = $cargaLeve[18];
                            $data['Nordeste']['Semana5']['Média'] = $cargaMedia[18];
                            $data['Nordeste']['Semana5']['Pesada'] = $cargaPesada[18];

                            //Norte
                            $data['Norte']['Semana5']['Leve'] = $cargaLeve[18];
                            $data['Norte']['Semana5']['Média'] = $cargaMedia[18];
                            $data['Norte']['Semana5']['Pesada'] = $cargaPesada[18];
                        }
                        elseif ($numSemana == 28)
                        {
                            //Sudeste/Centro-Oeste
                            $data['Sudeste/Centro-Oeste']['Semana4']['Leve'] = $cargaLeve[20];
                            $data['Sudeste/Centro-Oeste']['Semana4']['Média'] = $cargaMedia[20];
                            $data['Sudeste/Centro-Oeste']['Semana4']['Pesada'] = $cargaPesada[20];

                            //Sul
                            $data['Sul']['Semana4']['Leve'] = $cargaLeve[21];
                            $data['Sul']['Semana4']['Média'] = $cargaMedia[21];
                            $data['Sul']['Semana4']['Pesada'] = $cargaPesada[21];

                            //Nordeste
                            $data['Nordeste']['Semana4']['Leve'] = $cargaLeve[22];
                            $data['Nordeste']['Semana4']['Média'] = $cargaMedia[22];
                            $data['Nordeste']['Semana4']['Pesada'] = $cargaPesada[22];

                            //Norte
                            $data['Norte']['Semana4']['Leve'] = $cargaLeve[23];
                            $data['Norte']['Semana4']['Média'] = $cargaMedia[23];
                            $data['Norte']['Semana4']['Pesada'] = $cargaPesada[23];
                        }


        //Exportação para o banco
        try {
            if ($this->arangoDb->collectionHandler()->has('teste')) {

                $this->arangoDb->documment()->set('carga', [$date => $data]);
                $this->arangoDb->documentHandler()->save('teste', $this->arangoDb->documment());
            } else {
                $this->arangoDb->collection()->setName('teste');
                $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());

                $this->arangoDb->documment()->set('carga', [$date => $data]);
                $this->arangoDb->documentHandler()->save('teste', $this->arangoDb->documment());
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
                'palavra_chave' => 'dados de carga newwave',
                'responsabilidade' => 'O crawler realiza a captura dos dados fornecidos no newave',
                'status' => 'Crawler Aneel realizado com sucesso!'
            ]
        );

    }
}
