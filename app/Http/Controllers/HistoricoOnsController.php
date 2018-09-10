<?php

namespace Crawler\Http\Controllers;

use Carbon\Carbon;
use Crawler\Regex\RegexOns;
use Crawler\Regex\RegexSdroDiario;
use Crawler\Regex\RegexSdroSemanal;
use Crawler\Services\ImportServiceONS;
use Crawler\StorageDirectory\StorageDirectory;
use Crawler\Util\Util;
use Crawler\Util\UtilOns;
use Crawler\Regex\RegexMltEnas;
use Crawler\Model\ArangoDb;


class HistoricoOnsController extends Controller
{

    private $regexSdroSemanal;
    private $storageDirectory;
    private $regexSdroDiario;
    private $regexMltEnas;
    private $arangoDb;
    private $regexOns;
    private $importExcelOns;
    private $util;
    private $utilOns;
    private $data;


    public function __construct(RegexSdroSemanal $regexSdroSemanal,
                                StorageDirectory $storageDirectory,
                                ArangoDb $arangoDb,
                                RegexMltEnas $regexMltEnas,
                                RegexOns $regexOns,
                                RegexSdroDiario $regexSdroDiario,
                                Util $util,
                                UtilOns $utilOns,
                                ImportServiceONS $importExcelOns
    )
    {
        $this->regexSdroSemanal = $regexSdroSemanal;
        $this->storageDirectory = $storageDirectory;
        $this->regexSdroDiario = $regexSdroDiario;
        $this->regexMltEnas = $regexMltEnas;
        $this->arangoDb = $arangoDb;
        $this->regexOns = $regexOns;
        $this->importExcelOns = $importExcelOns;
        $this->util= $util;
        $this->utilOns= $utilOns;
    }


    public function historico_enas_mensal()
    {
        $data = [];
        $ena =[];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            '%mlt' => storage_path('/app/historico/ons/enas/mensal/mensal-%mlt.xlsx'),
            '%mlt armazenavel' => storage_path('/app/historico/ons/enas/mensal/mensal-%mlt-armazenavel.xlsx'),
            'mwmed' => storage_path('/app/historico/ons/enas/mensal/mensal-mwmed.xlsx'),
            'mwmed armazenavel' => storage_path('/app/historico/ons/enas/mensal/mensal-mwmed-armazenavel.xlsx')
        ];

        foreach ($files as $tipo => $file)
        {
            $rowData = file($files[$tipo]);
            unset($rowData[0]);

            foreach ($rowData as $key => $item)
            {
                $linha = explode(';', $rowData[$key]);
                $ano = explode(' de ', $linha[0])[1];
                $mes = explode(' de ', $linha[0])[0];

                $ena[$key][$tipo] = $this->regexOns->convert_str($linha[7]);

                $data['mensal'][$ano][$mes][trim($linha[1])] = $ena[$key];
            }
        }
        foreach ($data['mensal'] as $ano => $meses) {
            foreach ($meses as $mes => $regioes) {
                $mwmed = 0;
                $percent = 0;
                foreach ($regioes as $regiao => $array) {
                    foreach ($array as $unidade => $valor) {
                        if ($unidade === 'mwmed') {
                            $mwmed += (float)preg_replace('(,)', '.', $data['mensal'][$ano][$mes][$regiao][$unidade]);
                            $data['mensal'][$ano][$mes]['SIN'][$unidade] = $mwmed;
                        }  elseif ($unidade === '%mlt') {
                            $percent += ((float)preg_replace('(,)', '.', $data['mensal'][$ano][$mes][$regiao]['%mlt']) *
                                (float)preg_replace('(,)', '.', $data['mensal'][$ano][$mes][$regiao]['mwmed']));
                        }
                    }
                }
                $data['mensal'][$ano][$mes]['SIN']['%mlt'] = (float)$percent / (float)$mwmed;
            }
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, $data);
    }

    public function historico_enas_anual()
    {
        $data = [];
        $ena =[];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            '%mlt' => storage_path('/app/historico/ons/enas/anual/anual-%mlt.xlsx'),
            '%mlt armazenavel' => storage_path('/app/historico/ons/enas/anual/anual-%mlt-armazenavel.xlsx'),
            'mwmed' => storage_path('/app/historico/ons/enas/anual/anual-mwmed.xlsx'),
            'mwmed armazenavel' => storage_path('/app/historico/ons/enas/anual/anual-mwmed-armazenavel.xlsx')
        ];

        foreach ($files as $tipo => $file)
        {
            $rowData = file($files[$tipo]);
            unset($rowData[0]);

            foreach ($rowData as $key => $item)
            {
                $linha = explode(';', $rowData[$key]);

                $ena[$key][$tipo] = $this->regexOns->convert_str($linha[6]);

                $data['anual'][trim($linha[0])][trim($linha[1])] = $ena[$key];
            }
        }
        foreach ($data['anual'] as $ano => $regioes) {
            $mwmed = 0;
            $percent = 0;
            foreach ($regioes as $regiao=> $array) {
                foreach ($array as $unidade => $valor) {
                    if ($unidade === 'mwmed') {
                        $mwmed += (float)preg_replace('(,)', '.', $data['anual'][$ano][$regiao][$unidade]);
                        $data['anual'][$ano]['SIN'][$unidade] = $mwmed;
                    } elseif ($unidade === '%mlt') {
                        $percent += ((float)preg_replace('(,)', '.',$data['anual'][$ano][$regiao]['%mlt']) *
                            (float)preg_replace('(,)', '.', $data['anual'][$ano][$regiao]['mwmed']));
                    }
                }
            }
            $data['anual'][$ano]['SIN']['%mlt'] = (float)$percent / (float)$mwmed;
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, $data);
    }

    public function historico_enas_semanal()
    {
        $data = [];
        $ena =[];
        $subsistema =[];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            '%mlt' => storage_path('/app/historico/ons/enas/semanal/semanal-%mlt.xlsx'),
            '%mlt armazenavel' => storage_path('/app/historico/ons/enas/semanal/semanal-%mlt-armazenavel.xlsx'),
            'mwmed' => storage_path('/app/historico/ons/enas/semanal/semanal-mwmed.xlsx'),
            'mwmed armazenavel' => storage_path('/app/historico/ons/enas/semanal/semanal-mwmed-armazenavel.xlsx')
        ];

        foreach ($files as $tipo => $file)
        {
            $rowData = file($files[$tipo]);
            unset($rowData[0]);

            foreach ($rowData as $key => $item)
            {
                $linha = explode(';', $rowData[$key]);

                $carbon_inicio = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0]);
                $ano_inicio = $carbon_inicio->format('Y');
                $date_inicio = $carbon_inicio->format('d/m');

                $carbon_fim = Carbon::createFromFormat('d/m/Y', $linha[2]);
                $date_fim = $carbon_fim->format('d/m');

                $ena[$tipo] = $this->regexOns->convert_str($linha[7]);

                $data['semanal'][$ano_inicio][trim($linha[1])][$key] = [
                    'inicio' => $date_inicio,
                    'fim' => $date_fim,
                    'valor' => $ena
                ];
            }
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, $data);
    }

    public function historico_enas_diario()
    {
        $data = [];
        $ena =[];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            '%mlt' => storage_path('/app/historico/ons/enas/diario/diario-%mlt.xlsx'),
            '%mlt armazenavel' => storage_path('/app/historico/ons/enas/diario/diario-%mlt-armazenavel.xlsx'),
            'mwmed' => storage_path('/app/historico/ons/enas/diario/diario-mwmed.xlsx'),
            'mwmed armazenavel' => storage_path('/app/historico/ons/enas/diario/diario-mwmed-armazenavel.xlsx')
        ];

        foreach ($files as $tipo => $file)
        {
            $rowData = file($files[$tipo]);
            unset($rowData[0]);

            foreach ($rowData as $key => $item)
            {
                $linha = explode(';', $rowData[$key]);

                $carbon = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0]);
                $ano_inicio = $carbon->format('Y');
                $mes_inicio = $this->util->mesMesportugues($carbon->format('m'));
                $dia_inicio = $carbon->format('d');

                $ena[$key][$tipo] = $this->regexOns->convert_str($linha[7]);

                $data['diario'][$ano_inicio][$mes_inicio][$dia_inicio][trim($linha[1])] = $ena[$key];
            }
        }
        foreach ($data['diario'] as $ano => $meses) {
            foreach ($meses as $mes => $dias) {
                foreach ($dias as $dia => $regioes) {
                    $mwmed = 0;
                    $percent = 0;
                    foreach ($regioes as $regiao => $array) {
                        foreach ($array as $unidade => $valor) {
                            if ($unidade === 'mwmed') {
                                $mwmed += (float)preg_replace('(,)', '.',$data['diario'][$ano][$mes][$dia][$regiao][$unidade]);
                                $data['diario'][$ano][$mes][$dia]['SIN'][$unidade] = $mwmed;
                            } elseif ($unidade === '%mlt') {
                                $percent += ((float)preg_replace('(,)', '.',$data['diario'][$ano][$mes][$dia][$regiao]['%mlt']) *
                                    (float)preg_replace('(,)', '.',$data['diario'][$ano][$mes][$dia][$regiao]['mwmed']));
                            }
                        }
                    }
                    $data['diario'][$ano][$mes][$dia]['SIN']['%mlt'] = (float)$percent / (float)$mwmed;
                }
            }
        }
        $this->util->enviaArangoDB('ons', 'ena', $date, $data);
    }


    public function historico_carga_anual()
    {

        $data = [];
        $geracao = [];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'mwmed' => storage_path('app/historico/ons/carga/geracao_ano_mwmed.xlsx'),
            'gwh' => storage_path('app/historico/ons/carga/geracao_ano_gwh.xlsx')];

        foreach ($files as $unidade => $file)
        {
            $rowData = file($file);
            unset($rowData[0]);

            foreach ($rowData as $key => $info) {
                $linha = explode(';', $rowData[$key]);

                $geracao[$key][$unidade] = $this->regexOns->convert_str($linha[4]);

                $data['anual'][$linha[0]][$linha[1]] = $geracao[$key];
            }
        }
        foreach ($data['anual'] as $ano => $regioes) {
            $mwmed = 0;
            $gwh = 0;
            foreach ($regioes as $regiao => $array) {
                $mwmed += (float)$data['anual'][$ano][$regiao]['mwmed'];
                $gwh += (float)$data['anual'][$ano][$regiao]['gwh'];
            }
            $data['anual'][$ano]['SIN']['mwmed'] = $mwmed;
            $data['anual'][$ano]['SIN']['gwh'] = $gwh;
        }

        $this->util->enviaArangoDB('ons', 'carga', $date, $data);
    }

    public function historico_carga_mensal()
    {

        $data = [];
        $geracao = [];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'mwmed' => storage_path('app/historico/ons/carga/geracao_mensal_mwmed.xlsx'),
            'gwh' => storage_path('app/historico/ons/carga/geracao_mensal_gwh.xlsx')];

        foreach ($files as $unidade => $file)
        {
            $rowData = file($file);
            unset($rowData[0]);

            foreach ($rowData as $key => $info) {
                $linha = explode(';', $rowData[$key]);

                $ano = explode(' de ', $linha[0])[1];
                $mes = explode(' de ', $linha[0])[0];

                $geracao[$key][$unidade] = $this->regexOns->convert_str($linha[6]);

                $data['mensal'][$ano][$mes][$linha[1]] = $geracao[$key];
            }
        }
        foreach ($data['mensal'] as $ano => $meses) {
            foreach ($meses as $mes => $regioes) {
                $mwmed = 0;
                $gwh = 0;
                foreach ($regioes as $regiao => $array) {
                    $mwmed += (float)preg_replace('(,)', '.',$data['mensal'][$ano][$mes][$regiao]['mwmed']);
                    $gwh += (float)preg_replace('(,)', '.',$data['mensal'][$ano][$mes][$regiao]['gwh']);
                }
                $data['mensal'][$ano][$mes]['SIN']['mwmed'] = $mwmed;
                $data['mensal'][$ano][$mes]['SIN']['gwh'] = $gwh;
            }
        }

        $this->util->enviaArangoDB('ons', 'carga', $date, $data);
    }


    public function historico_carga_diario()
    {
        $data = [];
        $geracao = [];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'mwmed' => storage_path('app/historico/ons/carga/geracao_diario_mwmed.xlsx'),
            'gwh' => storage_path('app/historico/ons/carga/geracao_diario_gwh.xlsx')];

        foreach ($files as $unidade => $file)
        {
            $rowData = file($file);
            unset($rowData[0]);

            foreach ($rowData as $key => $info) {
                $linha = explode(';', $rowData[$key]);

                $dia = explode(' de ', $linha[0])[0];
                $mes = explode(' de ', $linha[0])[1];
                $ano = explode(' de ', $linha[0])[2];

                $geracao[$key][$unidade] = $this->regexOns->convert_str($linha[6]);

                $data['diario'][$ano][$mes][$dia][$linha[1]] = $geracao[$key];
            }
        }
        foreach ($data['diario'] as $ano => $meses) {
            foreach ($meses as $mes => $dias) {
                foreach ($dias as $dia => $regioes) {
                    $mwmed = 0;
                    $gwh = 0;
                    foreach ($regioes as $regiao => $array) {
                        $mwmed += (float)preg_replace('(,)', '.',$data['diario'][$ano][$mes][$dia][$regiao]['mwmed']);
                        $gwh += (float)preg_replace('(,)', '.',$data['diario'][$ano][$mes][$dia][$regiao]['gwh']);
                    }
                    $data['diario'][$ano][$mes][$dia]['SIN']['mwmed'] = $mwmed;
                    $data['diario'][$ano][$mes][$dia]['SIN']['gwh'] = $gwh;
                }
            }
        }

        $this->util->enviaArangoDB('ons', 'carga', $date, $data);
    }

    public function historico_intercambio_diario()
    {
        $data = [];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'nordeste-sudeste' => storage_path('app/historico/ons/intercambio/nordeste-sudeste.xlsx'),
            'norte-nordeste' => storage_path('app/historico/ons/intercambio/norte-nordeste.xlsx'),
            'norte-sudeste' => storage_path('app/historico/ons/intercambio/norte-sudeste.xlsx'),
            'sin-argentina' => storage_path('app/historico/ons/intercambio/sin-argentina.xlsx'),
            'sin-paraguai' => storage_path('app/historico/ons/intercambio/sin-paraguai.xlsx'),
            'sin-uruguai' => storage_path('app/historico/ons/intercambio/sin-uruguai.xlsx'),
            'sudeste-sul' => storage_path('app/historico/ons/intercambio/sudeste-sul.xlsx')
        ];

        foreach ($files as $rota => $file)
        {
            $rowData = file($file);
            unset($rowData[0]);

            foreach ($rowData as $key => $info)
            {
                $linha = explode(';', $rowData[$key]);

                $ano = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('Y');
                $mes = $this->util->mesMesportugues(Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('m'));
                $dia = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('d');

                if(stripos($linha[7], '-') !== false) {
                    $origem = trim(explode('-', $linha[4])[1]);
                    $destino = trim(explode('-', $linha[4])[0]);
                    $intercambio = $this->regexOns->convert_str(preg_replace('(-)', '', $linha[7]));
                } else {
                    $origem = trim(explode('-', $linha[4])[0]);
                    $destino = trim(explode('-', $linha[4])[1]);
                    $intercambio = $this->regexOns->convert_str($linha[7]);
                }

                $data['diario'][$ano][$mes][$dia] = [
                    'origem' => $origem,
                    'destino' => $destino,
                    'intercambio' => $intercambio
                ];
            }

        }

        $this->util->enviaArangoDB('ons', 'intercambio', $date, $data);

    }


    public function historico_cmo_semanal()
    {
        $data = [];
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'nordeste' => storage_path('app/historico/ons/cmo/nordeste.xlsx'),
            'norte' => storage_path('app/historico/ons/cmo/norte.xlsx'),
            'sudeste_centro-oeste' => storage_path('app/historico/ons/cmo/sudeste_centro-oeste.xlsx'),
            'sul' => storage_path('app/historico/ons/cmo/sul.xlsx'),
        ];

        foreach ($files as $subsistema => $file)
        {
            $rowData = file($file);
            unset($rowData[0]);

            foreach ($rowData as $key => $item)
            {
                $linha = explode(';', $rowData[$key]);

                $ano = Carbon::createFromFormat('m/d/Y', $linha[0])->format('Y');
                $inicio = Carbon::createFromFormat('m/d/Y', $linha[0])->format('d/m');
                $fim = Carbon::createFromFormat('m/d/Y', $linha[2])->format('d/m');

                $data['semanal']['por-subsistema'][$ano][$linha[1]][$key] = [
                    'inicio' => $inicio,
                    'fim' => $fim,
                    'valor' => $this->regexOns->convert_str($linha[6])
                ];
            }

        }

        $this->util->enviaArangoDB('ons', 'cmo', $date, $data);
    }

    public function historico_cmo_patamar()
    {
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'nordeste' => storage_path('app/historico/ons/cmo/por-patamar-nordeste.txt'),
            'norte' => storage_path('app/historico/ons/cmo/por-patamar-norte.txt'),
            'sudeste_centro-oeste' => storage_path('app/historico/ons/cmo/por-patamar-sudeste.txt'),
            'sul' => storage_path('app/historico/ons/cmo/por-patamar-sul.txt')
        ];

        foreach ($files as $regiao => $file) {
            $rowData = file($file);

            unset($rowData[0]);
            foreach ($rowData as $key => $item) {
                $linha = explode(';', $rowData[$key]);

                $ano[$key] = Carbon::createFromFormat('m/d/Y', $linha[1])->format('Y');
                $inicio[$key] = Carbon::createFromFormat('m/d/Y', $linha[1])->format('d/m');
                $fim[$key] = Carbon::createFromFormat('m/d/Y', $linha[2])->format('d/m');

                $this->data['semanal']['por-patamar'][$ano[$key]][$linha[5]][$key] = [
                    'inicio' => $inicio[$key],
                    'fim' => $fim[$key],
                    'valor' => $this->regexOns->convert_str($linha[7])
                ];
            }
        }

        $this->util->enviaArangoDB('ons', 'cmo', $date, $this->data);
    }


    public function historico_geracao_diario()
    {
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'eolica' => [
                'gwh' => storage_path('app/historico/ons/geracao/diario/eolica-diario-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/diario/eolica-diario-mwmed.txt')
            ],
            'hidreletrica' => [
                'gwh' => storage_path('app/historico/ons/geracao/diario/hidreletrica-diario-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/diario/hidreletrica-diario-mwmed.txt')
            ],
            'nuclear' => [
                'gwh' => storage_path('app/historico/ons/geracao/diario/nuclear-diario-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/diario/nuclear-diario-mwmed.txt')
            ],
            'solar' => [
                'gwh' => storage_path('app/historico/ons/geracao/diario/solar-diario-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/diario/solar-diario-mwmed.txt')
            ],
            'termica' => [
                'gwh' => storage_path('app/historico/ons/geracao/diario/termica-diario-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/diario/termica-diario-mwmed.txt')
            ],
            'total' => [
                'gwh' => storage_path('app/historico/ons/geracao/diario/total-diario-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/diario/total-diario-mwmed.txt')
            ]
        ];

        foreach ($files as $fonte => $unidades) {
            foreach ($unidades as $unidade => $file) {
                $rowData = file($file);
                unset($rowData[0]);
                unset($rowData[1]);

                foreach ($rowData as $key => $item) {
                    $linha[$fonte][$unidade] = explode(';', $rowData[$key]);

                    if ($linha[$fonte][$unidade][0]) {
                        $ano = Carbon::createFromFormat('d/m/Y H:i:s', $linha[$fonte][$unidade][0])->format('Y');
                        $mes = $this->util->mesMesportugues(Carbon::createFromFormat('d/m/Y H:i:s', $linha[$fonte][$unidade][0])->format('m'));
                        $dia = Carbon::createFromFormat('d/m/Y H:i:s', $linha[$fonte][$unidade][0])->format('d');

                        if (stripos(trim($this->regexOns->convert_str($linha[$fonte][$unidade][7])), 'e-') !== false) {
                            $numero = (float)explode('e', trim($this->regexOns->convert_str($linha[$fonte][$unidade][7])))[0];
                            $expoente = (float)explode('e', trim($this->regexOns->convert_str($linha[$fonte][$unidade][7])))[1];

                            $geracao[$key][$fonte][$linha[$fonte][$unidade][1]][$unidade][$key] = $numero * pow((float)10, (float)$expoente);
                        } else {
                            $geracao[$key][$fonte][$linha[$fonte][$unidade][1]][$unidade] = trim($this->regexOns->convert_str($linha[$fonte][$unidade][7]));
                        }

                        $this->data['diario'][$ano][$mes][$dia] = $geracao[$key];
                    }
                }
            }
        }
        foreach ($this->data['diario'] as $ano => $meses) {
            foreach ($meses as $mes => $dias) {
                foreach ($dias as $dia => $tipos) {
                    $mwmed = 0;
                    $gwh = 0;
                    foreach ($tipos as $tipo => $regioes) {
                        foreach ($regioes as $regiao => $unidades) {
                            $gwh += (float)preg_replace('(,)', '.',$this->data['diario'][$ano][$mes][$dia][$tipo][$regiao]['gwh']);
                            $mwmed += (float)preg_replace('(,)', '.',$this->data['diario'][$ano][$mes][$dia][$tipo][$regiao]['mwmed']);
                        }
                        $this->data['diario'][$ano][$mes][$dia][$tipo]['SIN']['gwh'] = $gwh;
                        $this->data['diario'][$ano][$mes][$dia][$tipo]['SIN']['mwmed'] = $mwmed;
                    }
                }
            }
        }

        $this->util->enviaArangoDB('ons', 'geracao', $date, $this->data);
    }


    public function historico_geracao_mensal()
    {
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'eolica' => [
                'gwh' => storage_path('app/historico/ons/geracao/mensal/eolica-mensal-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/mensal/eolica-mensal-mwmed.txt')
            ],
            'hidreletrica' => [
                'gwh' => storage_path('app/historico/ons/geracao/mensal/hidreletrica-mensal-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/mensal/hidreletrica-mensal-mwmed.txt')
            ],
            'nuclear' => [
                'gwh' => storage_path('app/historico/ons/geracao/mensal/nuclear-mensal-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/mensal/nuclear-mensal-mwmed.txt')
            ],
            'solar' => [
                'gwh' => storage_path('app/historico/ons/geracao/mensal/solar-mensal-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/mensal/solar-mensal-mwmed.txt')
            ],
            'termica' => [
                'gwh' => storage_path('app/historico/ons/geracao/mensal/termica-mensal-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/mensal/termica-mensal-mwmed.txt')
            ],
            'total' => [
                'gwh' => storage_path('app/historico/ons/geracao/mensal/total-mensal-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/mensal/total-mensal-mwmed.txt')
            ]
        ];

        foreach ($files as $fonte => $unidades) {
            foreach ($unidades as $unidade => $file) {
                $rowData = file($file);
                unset($rowData[0]);
                unset($rowData[1]);

                foreach ($rowData as $key => $item)
                {
                    $linha = explode(';', $rowData[$key]);

                    if ($linha[0])
                    {
                        $ano = Carbon::createFromFormat('d/m/Y H:i:s', $linha[2])->format('Y');
                        $mes = $this->util->mesMesportugues(Carbon::createFromFormat('d/m/Y H:i:s', $linha[2])->format('m'));

                        $this->data['mensal'][$ano][$mes][$fonte][$linha[1]][$unidade] = trim($this->regexOns->convert_str($linha[5]));
                    }
                }
            }
        }
        foreach ($this->data['mensal'] as $ano => $meses) {
            foreach ($meses as $mes => $tipos) {
                $mwmed = 0;
                $gwh = 0;
                foreach ($tipos as $tipo => $regioes) {
                    foreach ($regioes as $regiao => $unidades) {
                        $gwh += (float)preg_replace('(,)', '.',$this->data['mensal'][$ano][$mes][$tipo][$regiao]['gwh']);
                        $mwmed += (float)preg_replace('(,)', '.',$this->data['mensal'][$ano][$mes][$tipo][$regiao]['mwmed']);
                    }
                    $this->data['mensal'][$ano][$mes][$tipo]['SIN']['gwh'] = $gwh;
                    $this->data['mensal'][$ano][$mes][$tipo]['SIN']['mwmed'] = $mwmed;
                }
            }
        }

        $this->util->enviaArangoDB('ons', 'geracao', $date, $this->data);
    }


    public function historico_geracao_anual()
    {
        $date = Carbon::now()->format('Y-m-d');

        $files = [
            'eolica' => [
                'gwh' => storage_path('app/historico/ons/geracao/anual/eolica-anual-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/anual/eolica-anual-mwmed.txt')
            ],
            'hidreletrica' => [
                'gwh' => storage_path('app/historico/ons/geracao/anual/hidreletrica-anual-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/anual/hidreletrica-anual-mwmed.txt')
            ],
            'nuclear' => [
                'gwh' => storage_path('app/historico/ons/geracao/anual/nuclear-anual-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/anual/nuclear-anual-mwmed.txt')
            ],
            'solar' => [
                'gwh' => storage_path('app/historico/ons/geracao/anual/solar-anual-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/anual/solar-anual-mwmed.txt')
            ],
            'termica' => [
                'gwh' => storage_path('app/historico/ons/geracao/anual/termica-anual-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/anual/termica-anual-mwmed.txt')
            ],
            'total' => [
                'gwh' => storage_path('app/historico/ons/geracao/anual/total-anual-gwh.txt'),
                'mwmed' => storage_path('app/historico/ons/geracao/anual/total-anual-mwmed.txt')
            ]
        ];

        foreach ($files as $fonte => $unidades) {
            foreach ($unidades as $unidade => $file) {
                $rowData = file($file);
                unset($rowData[0]);

                foreach ($rowData as $key => $item) {
                    $linha = explode(';', $rowData[$key]);
                    if ($linha[0]) {
                        $this->data['anual'][$linha[0]][$fonte][$linha[1]][$unidade] = trim($this->regexOns->convert_str($linha[7]));
                    }
                }
            }
        }
        foreach ($this->data['anual'] as $ano => $tipos) {
            $mwmed = 0;
            $gwh = 0;
            foreach ($tipos as $tipo => $regioes) {
                foreach ($regioes as $regiao => $unidades) {
                    $gwh += (float)$this->data['anual'][$ano][$tipo][$regiao]['gwh'];
                    $mwmed += (float)$this->data['anual'][$ano][$tipo][$regiao]['mwmed'];
                }
                $this->data['anual'][$ano][$tipo]['SIN']['gwh'] = $gwh;
                $this->data['anual'][$ano][$tipo]['SIN']['mwmed'] = $mwmed;
            }
        }

        $this->util->enviaArangoDB('ons', 'geracao', $date, $this->data);
    }


    public function historico_ear_diario()
    {
        $date = Carbon::now()->format('Y-m-d');

        $file = storage_path('app/historico/ons/ear/dia.csv');

        $rowData = file($file);
        unset($rowData[0]);

        foreach ($rowData as $key => $item)
        {
            $linha = explode(';', $rowData[$key]);

            $ano = Carbon::createFromFormat('d/m/Y', $linha[0])->format('Y');
            $mes = $this->util->mesMesportugues(Carbon::createFromFormat('d/m/Y', $linha[0])->format('m'));
            $dia = Carbon::createFromFormat('d/m/Y', $linha[0])->format('d');

            $this->data['diario'][$linha[2]][$ano][$mes][$dia]['%ear_max'] = $this->regexOns->convert_str($linha[5]);
        }

        $this->util->enviaArangoDB('ons', 'ear', $date, $this->data);
    }


    public function historico_ear_semanal()
    {
        $date = Carbon::now()->format('Y-m-d');

        $file = storage_path('app/historico/ons/ear/semana.csv');

        $rowData = file($file);
        unset($rowData[0]);

        foreach ($rowData as $key => $item)
        {
            $linha = explode(';', $rowData[$key]);

            $ano = Carbon::createFromFormat('d/m/Y', $linha[0])->format('Y');
            $inicio = Carbon::createFromFormat('d/m/Y', $linha[0])->format('d/m');
            $fim = Carbon::createFromFormat('d/m/Y', $linha[0])->format('dd/m');

            $this->data['semanal'][$linha[1]][$ano][$key] = [
                'inicio' => $inicio,
                'fim' => $fim,
                'valor' => $this->regexOns->convert_str($linha[6])]
            ;
        }

        $this->util->enviaArangoDB('ons', 'ear', $date, $this->data);
    }


    public function historico_ear_mensal()
    {
        $date = Carbon::now()->format('Y-m-d');

        $file = storage_path('app/historico/ons/ear/mes.csv');

        $rowData = file($file);
        unset($rowData[0]);

        foreach ($rowData as $key => $item)
        {
            $linha = explode(';', $rowData[$key]);

            $ano = explode(' de ', $linha[0])[1];
            $mes = explode(' de ', $linha[0])[0];

            $this->data['mensal'][$linha[1]][$ano][$mes]['%ear_max'] = $this->regexOns->convert_str($linha[6]);
        }

        $this->util->enviaArangoDB('ons', 'ear', $date, $this->data);
    }

    public function historico_cdre()
    {
        set_time_limit(-1);

        $date = Util::getDateIso();

        $data = [];
        $nome_arquivos = [];

        $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $meses_indice = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $anos = ['2010', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018'];

        foreach ($anos as $ano) {
            foreach ($meses as $chave => $mes) {
                $nome_arquivos[$ano][$meses_indice[$chave]] = $mes . $ano . '.xlsx';
            }
        }
        unset($nome_arquivos['2018']['Setembro']);
        unset($nome_arquivos['2018']['Outubro']);
        unset($nome_arquivos['2018']['Novembro']);
        unset($nome_arquivos['2018']['Dezembro']);

        $file = '';
        foreach ($nome_arquivos as $ano => $arquivos) {
            foreach ($arquivos as $mes => $arquivo)
            {
                if ($ano === 2010) {
                    if ($mes !== 'Janeiro') {
                        if ($mes !== 'Fevereiro') {
                            $file = storage_path('app/historico/ons/pmo_cdre/cronograma/' . $arquivo);
                        }
                    }
                } else {
                    $file = storage_path('app/historico/ons/pmo_cdre/cronograma/' . $arquivo);
                }
                if ($file) {
                    $data['mensal']['expansao'][$ano][$mes] = $this->importExcelOns->historico_pmo_cronograma($file, $ano, $mes);
                }
            }
        }
        $this->util->enviaArangoDB('ons', 'pmo', $date, $data);
    }
}
