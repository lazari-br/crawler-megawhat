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
        $tratar = [];
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

                $tratar[$ano][$mes][trim($linha[1])] = $ena[$key];

                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'subsistema' => trim($linha[1]),
                    'valor' => $this->util->formata_valores($ena[$key])
                ];
            }
        }
        foreach ($tratar as $ano => $meses) {
            foreach ($meses as $mes => $regioes) {
                $mwmed = 0;
                $percent = 0;
                foreach ($regioes as $regiao => $array) {
                    foreach ($array as $unidade => $valor) {
                        if ($unidade === 'mwmed') {
                            $mwmed += (float)preg_replace('(,)', '.', $tratar[$ano][$mes][$regiao][$unidade]);
                            $tratar[$ano][$mes]['SIN'][$unidade] = $mwmed;
                        }  elseif ($unidade === '%mlt') {
                            $percent += ((float)preg_replace('(,)', '.', $tratar[$ano][$mes][$regiao]['%mlt']) *
                                (float)preg_replace('(,)', '.', $tratar[$ano][$mes][$regiao]['mwmed']));
                        }
                    }
                }
                $data[] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'subsistema' => 'SIN',
                    'valor' => $this->util->formata_valores([
                        '%mlt' => (float)$percent / (float)$mwmed
                    ])
                ];
            }
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, 'mensal', $data);
    }

    public function historico_enas_anual()
    {
        $data = [];
        $tratar = [];
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

                $tratar[trim($linha[0])][trim($linha[1])] = $ena[$key];

                $data[$key] = [
                    'ano' => trim($linha[0]),
                    'subsistema' => trim($linha[1]),
                    'valor' => $this->util->formata_valores($ena[$key])
                ];
            }
        }
        foreach ($tratar as $ano => $regioes) {
            $mwmed = 0;
            $percent = 0;
            foreach ($regioes as $regiao=> $array) {
                foreach ($array as $unidade => $valor) {
                    if ($unidade === 'mwmed') {
                        $mwmed += (float)preg_replace('(,)', '.', $tratar[$ano][$regiao][$unidade]);
                        $tratar[$ano]['SIN'][$unidade] = $mwmed;
                    } elseif ($unidade === '%mlt') {
                        $percent += ((float)preg_replace('(,)', '.',$tratar[$ano][$regiao]['%mlt']) *
                            (float)preg_replace('(,)', '.', $tratar[$ano][$regiao]['mwmed']));
                    }
                }
            }
            $data[] = [
                'ano' => $ano,
                'subsistema' => 'SIN',
                'valor' => $this->util->formata_valores(['%mlt' => ((float)$percent / (float)$mwmed)])
            ];
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, 'anual', $data);
    }

    public function historico_enas_semanal()
    {
        $data = [];
        $ena =[];
        $tratar =[];
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

                $ena[$key][$tipo] = $this->regexOns->convert_str($linha[7]);

                $tratar[$ano_inicio][$date_inicio.'*'.$date_fim][trim($linha[1])] = $ena[$key];

                $data[$key] = [
                    'ano' => $ano_inicio,
                    'subsistema' => trim($linha[1]),
                    'inicio' => $date_inicio,
                    'fim' => $date_fim,
                    'valor' => $this->util->formata_valores($ena[$key])
                ];
            }
        }
        foreach ($tratar as $ano => $inicio)  {
            foreach ($inicio as $semana => $regioes) {
                $mwmed = 0;
                $percent = 0;
                foreach ($regioes as $regiao => $array) {
                    $mwmed += (float)preg_replace('(,)', '.', $array['mwmed']);
                    $percent += (float)preg_replace('(,)', '.', $array['%mlt']) *
                        (float)preg_replace('(,)', '.', $array['mwmed']);
                }
                $data[] = [
                    'ano' => $ano,
                    'subsistema' => 'SIN',
                    'inicio' => explode('*', $semana)[0],
                    'fim' => explode('*', $semana)[1],
                    'valor' => $this->util->formata_valores(['%mlt' => ((float)$percent / (float)$mwmed)])
                ];
            }
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, 'semanal', $data);
    }

    public function historico_enas_diario()
    {
        $data = [];
        $tratar = [];
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

                $tratar[$ano_inicio][$mes_inicio][$dia_inicio][trim($linha[1])] = $ena[$key];
                $data[$key] = [
                    'ano' => $ano_inicio,
                    'mes' => $mes_inicio,
                    'dia' => $dia_inicio,
                    'subsistema' => trim($linha[1]),
                    'valor' => $this->util->formata_valores($ena[$key])
                ];
            }
        }
        foreach ($tratar as $ano => $meses) {
            foreach ($meses as $mes => $dias) {
                foreach ($dias as $dia => $regioes) {
                    $mwmed = 0;
                    $percent = 0;
                    foreach ($regioes as $regiao => $array) {
                        foreach ($array as $unidade => $valor) {
                            if ($unidade === 'mwmed') {
                                $mwmed += (float)preg_replace('(,)', '.',$tratar[$ano][$mes][$dia][$regiao][$unidade]);
                                $tratar[$ano][$mes][$dia]['SIN'][$unidade] = $mwmed;
                            } elseif ($unidade === '%mlt') {
                                $percent += ((float)preg_replace('(,)', '.',$tratar[$ano][$mes][$dia][$regiao]['%mlt']) *
                                    (float)preg_replace('(,)', '.',$tratar[$ano][$mes][$dia][$regiao]['mwmed']));
                            }
                        }
                    }
                    $data[] = [
                        'ano' => $ano,
                        'mes' => $mes,
                        'dia' => $dia,
                        'subsistema' => 'SIN',
                        'valor' => $this->util->formata_valores(['%mlt' => (float)$percent / (float)$mwmed])
                    ];
                }
            }
        }

        $this->util->enviaArangoDB('ons', 'ena', $date, 'diario', $data);
    }

    public function historico_carga_anual()
    {
        $data = [];
        $tratar = [];
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
                $tratar[$linha[0]][$linha[1]] = $geracao[$key];

                $data[$key] = [
                    'ano' => $linha[0],
                    'subsistema' => $linha[1],
                    'valor'=> $this->util->formata_valores($geracao[$key])
                ];
            }
        }
        foreach ($tratar as $ano => $regioes) {
            $mwmed = 0;
            $gwh = 0;
            foreach ($regioes as $regiao => $array) {
                $mwmed += (float)preg_replace('(,)', '.', $tratar[$ano][$regiao]['mwmed']);
                $gwh += (float)preg_replace('(,)', '.', $tratar[$ano][$regiao]['gwh']);
            }
            $data[] = [
                'ano' => $ano,
                'subsistema' => 'SIN',
                'valor'=> $this->util->formata_valores([
                    'mwmed' => $mwmed,
                    'gwh' => $gwh
                ])
            ];
        }

        $this->util->enviaArangoDB('ons', 'carga', $date, 'anual', $data);
    }

    public function historico_carga_mensal()
    {
        $data = [];
        $tratar = [];
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
                $tratar[$ano][$mes][$linha[1]] = $geracao[$key];

                $data[$key] =[
                    'ano' => $ano,
                    'mes' => $mes,
                    'subsistema' => $linha[1],
                    'valor' => $geracao[$key]
                ];
            }
        }
        foreach ($tratar as $ano => $meses) {
            foreach ($meses as $mes => $regioes) {
                $mwmed = 0;
                $gwh = 0;
                foreach ($regioes as $regiao => $array) {
                    $mwmed += (float)preg_replace('(,)', '.',$tratar[$ano][$mes][$regiao]['mwmed']);
                    $gwh += (float)preg_replace('(,)', '.',$tratar[$ano][$mes][$regiao]['gwh']);
                }
                $data[] = [
                    'ano' => $ano,
                    'mes'=> $mes,
                    'subsistema' => 'SIN',
                    'valor' => $this->util->formata_valores([
                        'mwmed' => $mwmed,
                        'gwh' => $gwh
                    ])
                ];
            }
        }

        $this->util->enviaArangoDB('ons', 'carga', $date,'mensal', $data);
    }

    public function historico_carga_diario()
    {
        $data = [];
        $tratar = [];
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
                $tratar[$ano][$mes][$dia][$linha[1]] = $geracao[$key];

                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'dia' => $dia,
                    'subsistema' => $linha[1],
                    'valor' => $this->util->formata_valores($geracao[$key])
                ];
            }
        }
        foreach ($tratar as $ano => $meses) {
            foreach ($meses as $mes => $dias) {
                foreach ($dias as $dia => $regioes) {
                    $mwmed = 0;
                    $gwh = 0;
                    foreach ($regioes as $regiao => $array) {
                        $mwmed += (float)preg_replace('(,)', '.',$tratar[$ano][$mes][$dia][$regiao]['mwmed']);
                        $gwh += (float)preg_replace('(,)', '.',$tratar[$ano][$mes][$dia][$regiao]['gwh']);
                    }
                    $data[] = [
                        'ano' => $ano,
                        'mes' => $mes,
                        'dia' => $dia,
                        'subsistema' => 'SIN',
                        'valor' => $this->util->formata_valores([
                            'mwmed' => $mwmed,
                            'gwh' => $gwh
                        ])
                    ];
                }
            }
        }

        $this->util->enviaArangoDB('ons', 'carga', $date, 'diario', $data);
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

                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'dia' => $dia,
                    'origem' => $origem,
                    'destino' => $destino,
                    'intercambio' => $intercambio
                ];
            }

        }

        $this->util->enviaArangoDB('ons', 'intercambio', $date, 'diario', $data);

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

                $data['por-subsistema'][$key] = [
                    'ano' => $ano,
                    'subsistema' => $linha[1],
                    'inicio' => $inicio,
                    'fim' => $fim,
                    'valor' => $this->regexOns->convert_str($linha[6])
                ];
            }

        }

        $this->util->enviaArangoDB('ons', 'cmo', $date, 'semanal', $data);
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

                $this->data['por-patamar'][$key] = [
                    'ano' => $ano[$key],
                    'subsistema', $linha[5],
                    'inicio' => $inicio[$key],
                    'fim' => $fim[$key],
                    'valor' => $this->regexOns->convert_str($linha[7])
                ];
            }
        }

        $this->util->enviaArangoDB('ons', 'cmo', $date, 'semanal', $this->data);
    }


    public function historico_geracao_diario()
    {
        set_time_limit(-1);
        $date = Carbon::now()->format('Y-m-d');

        $eolica = [
            'gwh' => storage_path('app/historico/ons/geracao/diario/eolica-diario-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/diario/eolica-diario-mwmed.txt')
        ];
        $hidreletrica = [
            'gwh' => storage_path('app/historico/ons/geracao/diario/hidreletrica-diario-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/diario/hidreletrica-diario-mwmed.txt')
        ];
        $nuclear = [
            'gwh' => storage_path('app/historico/ons/geracao/diario/nuclear-diario-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/diario/nuclear-diario-mwmed.txt')
        ];
        $solar = [
            'gwh' => storage_path('app/historico/ons/geracao/diario/solar-diario-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/diario/solar-diario-mwmed.txt')
        ];
        $termica = [
            'gwh' => storage_path('app/historico/ons/geracao/diario/termica-diario-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/diario/termica-diario-mwmed.txt')
        ];
        $total = [
            'gwh' => storage_path('app/historico/ons/geracao/diario/total-diario-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/diario/total-diario-mwmed.txt')
        ];
        $sin = [
            'mwmed' => [
                'eolica' => storage_path('app/historico/ons/geracao/diario/eolica-mwmed.csv'),
                'hidreletrica' => storage_path('app/historico/ons/geracao/diario/hidreletrica-mwmed.csv'),
                'nuclear' => storage_path('app/historico/ons/geracao/diario/nuclear-mwmed.csv'),
                'solar' => storage_path('app/historico/ons/geracao/diario/solar-mwmed.csv'),
                'termica' => storage_path('app/historico/ons/geracao/diario/termica-mwmed.csv')
            ],
            'gwh' => [
                'eolica' => storage_path('app/historico/ons/geracao/diario/eolica-gwh.csv'),
                'hidreletrica' => storage_path('app/historico/ons/geracao/diario/hidreletrica-gwh.csv'),
                'nuclear' => storage_path('app/historico/ons/geracao/diario/nuclear-gwh.csv'),
                'solar' => storage_path('app/historico/ons/geracao/diario/solar-gwh.csv'),
                'termica' => storage_path('app/historico/ons/geracao/diario/termica-gwh.csv')
            ]
        ];

        $data['eolica'] = $this->importExcelOns->import_historico_geracao_diario($eolica, 'eolica');
        $data['hidreletrica'] = $this->importExcelOns->import_historico_geracao_diario($hidreletrica, 'hidreletrica');
        $data['nuclear'] = $this->importExcelOns->import_historico_geracao_diario($nuclear, 'nuclear');
        $data['solar'] = $this->importExcelOns->import_historico_geracao_diario($solar, 'solar');
        $data['termica'] = $this->importExcelOns->import_historico_geracao_diario($termica, 'termica');
        $data['total'] = $this->importExcelOns->import_historico_geracao_diario($total, 'total');

        foreach ($sin as $unidade =>$files) {
            foreach ($files as $fonte => $file) {
                $data['SIN'][$fonte] = $this->importExcelOns->import_historico_geracao_diario_sin($file, $unidade, $fonte);
            }
        }

        $this->data = array_merge(
            $data['eolica'],
            $data['hidreletrica'],
            $data['nuclear'],
            $data['solar'],
            $data['termica'],
            $data['total'],
            $data['SIN']['eolica'],
            $data['SIN']['hidreletrica'],
            $data['SIN']['nuclear'],
            $data['SIN']['solar'],
            $data['SIN']['termica']
        );

        $this->util->enviaArangoDB('ons', 'geracao', $date, 'diario', $this->data);
    }

    public function historico_geracao_mensal()
    {
        set_time_limit(-1);
        $date = Carbon::now()->format('Y-m-d');

        $eolica = [
            'gwh' => storage_path('app/historico/ons/geracao/mensal/eolica-mensal-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/mensal/eolica-mensal-mwmed.txt')
        ];
        $hidreletrica = [
            'gwh' => storage_path('app/historico/ons/geracao/mensal/hidreletrica-mensal-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/mensal/hidreletrica-mensal-mwmed.txt')
        ];
        $nuclear = [
            'gwh' => storage_path('app/historico/ons/geracao/mensal/nuclear-mensal-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/mensal/nuclear-mensal-mwmed.txt')
        ];
        $solar = [
            'gwh' => storage_path('app/historico/ons/geracao/mensal/solar-mensal-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/mensal/solar-mensal-mwmed.txt')
        ];
        $termica = [
            'gwh' => storage_path('app/historico/ons/geracao/mensal/termica-mensal-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/mensal/termica-mensal-mwmed.txt')
        ];
        $total = [
            'gwh' => storage_path('app/historico/ons/geracao/mensal/total-mensal-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/mensal/total-mensal-mwmed.txt')
        ];
        $sin = [
            'mwmed' => [
                'eolica' => storage_path('app/historico/ons/geracao/mensal/eolica-mwmed.csv'),
                'hidreletrica' => storage_path('app/historico/ons/geracao/mensal/hidreletrica-mwmed.csv'),
                'nuclear' => storage_path('app/historico/ons/geracao/mensal/nuclear-mwmed.csv'),
                'solar' => storage_path('app/historico/ons/geracao/mensal/solar-mwmed.csv'),
                'termica' => storage_path('app/historico/ons/geracao/mensal/termica-mwmed.csv')
            ],
            'gwh' => [
                'eolica' => storage_path('app/historico/ons/geracao/mensal/eolica-gwh.csv'),
                'hidreletrica' => storage_path('app/historico/ons/geracao/mensal/hidreletrica-gwh.csv'),
                'nuclear' => storage_path('app/historico/ons/geracao/mensal/nuclear-gwh.csv'),
                'solar' => storage_path('app/historico/ons/geracao/mensal/solar-gwh.csv'),
                'termica' => storage_path('app/historico/ons/geracao/mensal/termica-gwh.csv')
            ]
        ];

        $data['eolica'] = $this->importExcelOns->import_historico_geracao_mensal($eolica, 'eolica');
        $data['hidreletrica'] = $this->importExcelOns->import_historico_geracao_mensal($hidreletrica, 'hidreletrica');
        $data['nuclear'] = $this->importExcelOns->import_historico_geracao_mensal($nuclear, 'nuclear');
        $data['solar'] = $this->importExcelOns->import_historico_geracao_mensal($solar, 'solar');
        $data['termica'] = $this->importExcelOns->import_historico_geracao_mensal($termica, 'termica');
        $data['total'] = $this->importExcelOns->import_historico_geracao_mensal($total, 'total');

        foreach ($sin as $unidade =>$files) {
            foreach ($files as $fonte => $file) {
                $data['SIN'][$fonte] = $this->importExcelOns->import_historico_geracao_mensal_sin($file, $unidade, $fonte);
            }
        }

        $this->data = array_merge(
            $data['eolica'],
            $data['hidreletrica'],
            $data['nuclear'],
            $data['solar'],
            $data['termica'],
            $data['total'],
            $data['SIN']['eolica'],
            $data['SIN']['hidreletrica'],
            $data['SIN']['nuclear'],
            $data['SIN']['solar'],
            $data['SIN']['termica']
        );

        $this->util->enviaArangoDB('ons', 'geracao', $date, 'mensal', $this->data);
    }


    public function historico_geracao_anual()
    {
        set_time_limit(-1);
        $date = Carbon::now()->format('Y-m-d');

        $eolica = [
            'gwh' => storage_path('app/historico/ons/geracao/anual/eolica-anual-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/anual/eolica-anual-mwmed.txt')
        ];
        $hidreletrica = [
            'gwh' => storage_path('app/historico/ons/geracao/anual/hidreletrica-anual-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/anual/hidreletrica-anual-mwmed.txt')
        ];
        $nuclear = [
            'gwh' => storage_path('app/historico/ons/geracao/anual/nuclear-anual-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/anual/nuclear-anual-mwmed.txt')
        ];
        $solar = [
            'gwh' => storage_path('app/historico/ons/geracao/anual/solar-anual-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/anual/solar-anual-mwmed.txt')
        ];
        $termica = [
            'gwh' => storage_path('app/historico/ons/geracao/anual/termica-anual-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/anual/termica-anual-mwmed.txt')
        ];
        $total = [
            'gwh' => storage_path('app/historico/ons/geracao/anual/total-anual-gwh.txt'),
            'mwmed' => storage_path('app/historico/ons/geracao/anual/total-anual-mwmed.txt')
        ];
        $sin = [
            'mwmed' => [
                'eolica' => storage_path('app/historico/ons/geracao/anual/eolica-mwmed.csv'),
                'hidreletrica' => storage_path('app/historico/ons/geracao/anual/hidreletrica-mwmed.csv'),
                'nuclear' => storage_path('app/historico/ons/geracao/anual/nuclear-mwmed.csv'),
                'solar' => storage_path('app/historico/ons/geracao/anual/solar-mwmed.csv'),
                'termica' => storage_path('app/historico/ons/geracao/anual/termica-mwmed.csv')
            ],
            'gwh' => [
                'eolica' => storage_path('app/historico/ons/geracao/anual/eolica-gwh.csv'),
                'hidreletrica' => storage_path('app/historico/ons/geracao/anual/hidreletrica-gwh.csv'),
                'nuclear' => storage_path('app/historico/ons/geracao/anual/nuclear-gwh.csv'),
                'solar' => storage_path('app/historico/ons/geracao/anual/solar-gwh.csv'),
                'termica' => storage_path('app/historico/ons/geracao/anual/termica-gwh.csv')
            ]
        ];

        $data['eolica'] = $this->importExcelOns->import_historico_geracao_anual($eolica, 'eolica');
        $data['hidreletrica'] = $this->importExcelOns->import_historico_geracao_anual($hidreletrica, 'hidreletrica');
        $data['nuclear'] = $this->importExcelOns->import_historico_geracao_anual($nuclear, 'nuclear');
        $data['solar'] = $this->importExcelOns->import_historico_geracao_anual($solar, 'solar');
        $data['termica'] = $this->importExcelOns->import_historico_geracao_anual($termica, 'termica');
        $data['total'] = $this->importExcelOns->import_historico_geracao_anual($total, 'total');

        foreach ($sin as $unidade =>$files) {
            foreach ($files as $fonte => $file) {
                $data['SIN'][$fonte] = $this->importExcelOns->import_historico_geracao_anual_sin($file, $unidade, $fonte);
            }
        }

        $this->data = array_merge(
            $data['eolica'],
            $data['hidreletrica'],
            $data['nuclear'],
            $data['solar'],
            $data['termica'],
            $data['total'],
            $data['SIN']['eolica'],
            $data['SIN']['hidreletrica'],
            $data['SIN']['nuclear'],
            $data['SIN']['solar'],
            $data['SIN']['termica']
        );

        $this->util->enviaArangoDB('ons', 'geracao', $date, 'anual', $this->data);
    }


    public function historico_ear_diario()
    {
        set_time_limit(-1);
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
//dd($linha);
            $this->data[$key] = [
                'ano' => $ano,
                'mes' => $mes,
                'dia' => $dia,
                'subsistema' => $linha[2],
                'valor' => $this->util->formata_valores(['%ear_max' => $this->regexOns->convert_str($linha[5])])
            ];
        }

        $this->util->enviaArangoDB('ons', 'ear', $date, 'diario', $this->data);
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
            $fim = Carbon::createFromFormat('d/m/Y', $linha[0])->format('d/m');

            $this->data[$key] = [
                'ano' => $ano,
                'inicio' => $inicio,
                'fim' => $fim,
                'subsistema' => $linha[1],
                'valor' => $this->util->formata_valores(['%ear_max' => [$this->regexOns->convert_str($linha[6])]])
            ];
        }

        $this->util->enviaArangoDB('ons', 'ear', $date, 'semanal', $this->data);
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

            $this->data[$key] = [
                'ano' => $ano,
                'mes' => $mes,
                'subsistema' =>$linha[1],
                'valor' => $this->util->formata_valores(['%ear_max' => $this->regexOns->convert_str($linha[6])])
            ];
        }

        $this->util->enviaArangoDB('ons', 'ear', $date, 'mensal',$this->data);
    }

    public function historico_cdre_cronograma()
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
                    $data[] = $this->importExcelOns->historico_pmo_cronograma($file, $ano, $mes);
                }
            }
        }
        $this->util->enviaArangoDB('ons', 'pmo', $date, 'mensal', $data['expansao']);
    }


    public function historico_cdre_memorial()
    {
        set_time_limit(-1);
        $date = Util::getDateIso();

        $data = [];
        $nome_arquivos = [];

        $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $meses_indice = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $anos = ['2017', '2018'];

        foreach ($anos as $ano) {
            foreach ($meses as $chave => $mes) {
                $nome_arquivos[$ano][$meses_indice[$chave]] = $mes .'-'. $ano . '.xlsx';
            }
        }
        unset($nome_arquivos['2018']['Outubro']);
        unset($nome_arquivos['2018']['Novembro']);
        unset($nome_arquivos['2018']['Dezembro']);

        foreach ($nome_arquivos as $ano => $arquivos) {
            foreach ($arquivos as $mes => $arquivo) {
                $file = 'storage/app/historico/ons/pmo_cdre/memorial/' . $arquivo;
                if ($ano === 2017) {
                    if ($this->util->mesMesXXportugues($mes) <= 4) {
                        $data['data'][] = $this->importExcelOns->histoemorial($file, 1, $ano, $mes);
                    }
                } else {
                    $data['data'][] = $this->importExcelOns->historico_pmo_memorial($file, 2, $ano, $mes);
                }
            }
        }

        $this->util->enviaArangoDB('ons', 'pmo', $date, 'mensal',  $data);
    }
}
