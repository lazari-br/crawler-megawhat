<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09/05/18
 * Time: 13:57
 */

namespace Crawler\Excel;

use Carbon\Carbon;
use Maatwebsite\Excel\Excel;
use Crawler\Util\Util;
use Crawler\Util\UtilOns;
use Crawler\Regex\RegexOns;


class ImportExcelOns
{
    private $excel;
    private $startRow;
    private $util;
    private $utilOns;
    private $regexOns;


    public function __construct(Excel $excel,
                                RegexOns $regexOns,
                                Util $util,
                                UtilOns $utilOns)
    {
        $this->excel = $excel;
        $this->util = $util;
        $this->utilOns = $utilOns;
        $this->regexOns = $regexOns;
    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }


    public function onsMotDispMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldUsina = '';
        $oldCodigo = '';

        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $cabecario = ['Potência Instalada', 'Ordem de Mérito', 'Inflex.', 'Restrição Elétrica', 'Geração Fora de Mérito', 'Energia de Reposição', 'Garantia Energética', 'Export.', 'Verificado'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldUsina, &$oldCodigo, &$date, $cabecario) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['usina']) ||
                        empty($rowData['codigoons'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a usina ou o código vazio');

                } else {
                    unset($rowData[0]);
                    $usina = !empty($rowData['usina']) ? $rowData['usina'] : $oldUsina;
                    $codigo = !empty($rowData['usina']) ? $rowData['codigoons'] : $oldCodigo;
                    unset($rowData['usina']);
                    unset($rowData['codigoons']);

                    $arr = array_combine($cabecario, $rowData);
                    $arrDispacho = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrDispacho) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 2, ",", ".");

                        }

                        $arrDispacho[$key] = $total;
                    });

                    if (isset($data[$usina][$codigo])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } elseif (isset($data[$usina])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } else {
                        $data[$usina] = [$codigo => $arrDispacho
                        ];
                    }

                    $oldUsina = $usina;
                    $oldCodigo = $codigo;
                }
            });

        return $data;
    }


    public function onsEnaSemanalMWm($file, $sheet)
    {

        $explode = function ($n) {
            return explode("_", $n);
        };

        $index = ['Norte', 'Nordeste', 'Sul', 'Sudeste/Centro-Oeste', 'Total'];

        $rowDataNorte = $this->util->import(8, $sheet, $file, 8, 0);
        $norte = array_slice($rowDataNorte[0], 2);

        $rowDataSul = $this->util->import(19, $sheet, $file, 19, 0);
        $sul =  array_slice($rowDataSul[0], 2);

        $rowData = array_merge($norte, $sul);

        $array =[];
        foreach ($rowData as $key => $item) {
            $array[] = number_format($this->regexOns->getNumEnaImport($key), 3, '.', ',');
        }
        $array[4] = array_sum($array);
        $data = array_combine($index, $array);

        return $data;
    }

    public function onsEnaSemanalPerc($file, $sheet)
    {
        $index = ['Norte', 'Nordeste', 'Sul', 'Sudeste'];

        $rowDataNorte = $this->util->import(12, $sheet, $file, 12, 0);
        $rowDataSul = $this->util->import(23, $sheet, $file, 23, 0);

        $rowData = array_keys(array_merge($rowDataNorte[0], $rowDataSul[0]));
        $data = array_combine($index, [$rowData[3], $rowData[5], $rowData[8], $rowData[10]]);

        return $data;
    }

    public function onsEna($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['% MLT no dia', '% MLT acumulado no mês até o dia', 'ENA Bruta (MWmed) no dia', 'ENA Bruta (MWmed) acumulada até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use (&$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsEnaTotal($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $teste = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['% MLT no dia', '% MLT acumulado no mês até o dia', 'ENA Bruta (MWmed) no dia', 'ENA Bruta (MWmed) acumulada até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);

                    if (isset($data[$sist])) {
                        $data[$sist] = $arr;
                    } else {
                        $data[$sist] = $arr;
                    }
                    $oldSist = $sist;
                }
            });

        $colMltDia = number_format(array_sum(array_column($data, '% MLT no dia')), 3, ",", ".");
        $colMltAcum = number_format(array_sum(array_column($data, '% MLT acumulado no mês até o dia')), 3, ",", ".");
        $colBrutaDia = number_format(array_sum(array_column($data, 'ENA Bruta (MWmed) no dia')), 3, ",", ".");
        $colBrutaAcum = number_format(array_sum(array_column($data, 'ENA Bruta (MWmed) acumulada até o dia')), 3, ",", ".");

        $somas = array($colMltDia, $colMltAcum, $colBrutaDia, $colBrutaAcum);

        $data = array_combine($ena, $somas);

        return $data;
    }


    public function onsProdHidGWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdHidMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdHidUsina($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldUsina = '';
        $oldCodigo = '';

        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $cabecario = ['Programado (MWmed)', 'Verificado (MWmed)', 'Desvio %.'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldUsina, &$oldCodigo, &$date, $cabecario) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['usina']) ||
                        empty($rowData['codigo_ons'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a usina ou o código vazio');

                } else {
                    unset($rowData[0]);
                    $usina = !empty($rowData['usina']) ? $rowData['usina'] : $oldUsina;
                    $codigo = !empty($rowData['usina']) ? $rowData['codigo_ons'] : $oldCodigo;
                    unset($rowData['usina']);
                    unset($rowData['codigo_ons']);

                    $arr = array_combine($cabecario, $rowData);
                    $arrDispacho = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrDispacho) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 2, ",", ".");

                        }

                        $arrDispacho[$key] = $total;
                    });

                    if (isset($data[$usina][$codigo])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } elseif (isset($data[$usina])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } else {
                        $data[$usina] = [$codigo => $arrDispacho
                        ];
                    }

                    $oldUsina = $usina;
                    $oldCodigo = $codigo;
                }
            });

        return $data;
    }


    public function onsProdTerGWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdTerMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdTerUsina($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldUsina = '';
        $oldCodigo = '';

        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $cabecario = ['Programado (MWmed)', 'Verificado (MWmed)', 'Desvio %.'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldUsina, &$oldCodigo, &$date, $cabecario) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['usina']) ||
                        empty($rowData['codigo_ons'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a usina ou o código vazio');

                } else {
                    unset($rowData[0]);
                    $usina = !empty($rowData['usina']) ? $rowData['usina'] : $oldUsina;
                    $codigo = !empty($rowData['usina']) ? $rowData['codigo_ons'] : $oldCodigo;
                    unset($rowData['usina']);
                    unset($rowData['codigo_ons']);

                    $arr = array_combine($cabecario, $rowData);
                    $arrDispacho = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrDispacho) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 2, ",", ".");

                        }

                        $arrDispacho[$key] = $total;
                    });

                    if (isset($data[$usina][$codigo])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } elseif (isset($data[$usina])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } else {
                        $data[$usina] = [$codigo => $arrDispacho
                        ];
                    }

                    $oldUsina = $usina;
                    $oldCodigo = $codigo;
                }
            });

        return $data;
    }


    public function onsProdEolGWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdEolMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdEolUsina($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldUsina = '';
        $oldCodigo = '';

        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $cabecario = ['Programado (MWmed)', 'Verificado (MWmed)', 'Desvio %.'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldUsina, &$oldCodigo, &$date, $cabecario) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['usina']) ||
                        empty($rowData['codigo_ons'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a usina ou o código vazio');

                } else {
                    unset($rowData[0]);
                    $usina = !empty($rowData['usina']) ? $rowData['usina'] : $oldUsina;
                    $codigo = !empty($rowData['usina']) ? $rowData['codigo_ons'] : $oldCodigo;
                    unset($rowData['usina']);
                    unset($rowData['codigo_ons']);

                    $arr = array_combine($cabecario, $rowData);
                    $arrDispacho = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrDispacho) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 2, ",", ".");

                        }

                        $arrDispacho[$key] = $total;
                    });

                    if (isset($data[$usina][$codigo])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } elseif (isset($data[$usina])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } else {
                        $data[$usina] = [$codigo => $arrDispacho
                        ];
                    }

                    $oldUsina = $usina;
                    $oldCodigo = $codigo;
                }
            });

        return $data;
    }


    public function onsProdSolGWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');
                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdSolMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');

                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsProdSolUsina($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldUsina = '';
        $oldCodigo = '';

        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $cabecario = ['Programado (MWmed)', 'Verificado (MWmed)', 'Desvio %.'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldUsina, &$oldCodigo, &$date, $cabecario) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['usina']) ||
                        empty($rowData['codigo_ons'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a usina ou o código vazio');

                } else {
                    unset($rowData[0]);
                    $usina = !empty($rowData['usina']) ? $rowData['usina'] : $oldUsina;
                    $codigo = !empty($rowData['usina']) ? $rowData['codigo_ons'] : $oldCodigo;
                    unset($rowData['usina']);
                    unset($rowData['codigo_ons']);

                    $arr = array_combine($cabecario, $rowData);
                    $arrDispacho = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrDispacho) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 2, ",", ".");

                        }

                        $arrDispacho[$key] = $total;
                    });

                    if (isset($data[$usina][$codigo])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } elseif (isset($data[$usina])) {
                        $data[$usina][$codigo] = $arrDispacho;
                    } else {
                        $data[$usina] = [$codigo => $arrDispacho
                        ];
                    }

                    $oldUsina = $usina;
                    $oldCodigo = $codigo;
                }
            });

        return $data;
    }

    public function onsCargaGWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');
                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsCargaMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSist = '';
        $this->setConfigStartRow($startRow);
        $ena = ['GWh no Dia', 'GWh acumulado no Mês até o Dia', 'GWh acum. no Ano até o dia'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSist, &$date, $ena) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['subsistema'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o subsistema vazio');
                } else {
                    unset($rowData[0]);
                    $sist = !empty($rowData['subsistema']) ? $rowData['subsistema'] : $oldSist;
                    unset($rowData['subsistema']);

                    $arr = array_combine($ena, $rowData);
                    $arrSist = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrSist) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrSist[$key] = $total;
                    });

                    if (isset($data[$sist])) {
                        $data[$sist] = $arrSist;
                    } else {
                        $data[$sist] = $arrSist;
                    }
                    $oldSist = $sist;
                }
            });

        return $data;
    }


    public function onsEar($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldEnerg = '';
        $this->setConfigStartRow($startRow);
        $sist = ['Sul', 'SE/CO', 'Norte', "NE"];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldEnerg, &$date, $sist) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['energia_armazenada'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a energia vazia');
                } else {
                    unset($rowData[0]);
                    $energ = !empty($rowData['energia_armazenada']) ? $rowData['energia_armazenada'] : $oldEnerg;
                    unset($rowData['energia_armazenada']);

                    $arr = array_combine($sist, $rowData);
                    $arrEnerg = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrEnerg) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }
                        $arrEnerg[$key] = $total;
                    });

                    if (isset($data[$energ])) {
                        $data[$energ] = $arrEnerg;
                    } else {
                        $data[$energ] = $arrEnerg;
                    }
                    $oldEnerg = $energ;
                }
            });

        return $data;
    }


    public function onsEarTotal($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldEnerg = '';
        $this->setConfigStartRow($startRow);

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldEnerg) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['energia_armazenada'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a energia vazia');
                } else {
                    unset($rowData[0]);
                    $energ = !empty($rowData['energia_armazenada']) ? $rowData['energia_armazenada'] : $oldEnerg;
                    unset($rowData['energia_armazenada']);

                    if (isset($data)) {
                        $data[$energ] = $rowData;
                    } else {
                        $data[$energ] = $rowData;
                    }
                    $oldEnerg = $energ;
                }
            });

        $valores = array_values($data);

        $somaCapMax = number_format(array_sum($valores[0]), 3, ",", ".");
        $somaArmzMW = number_format(array_sum($valores[1]), 3, ",", ".");
        $somaArmzPerc = number_format(array_sum($valores[2]), 3, ",", ".");

        $somas = array($somaCapMax, $somaArmzMW, $somaArmzPerc);
        $energias = ['Capacidade Máxima (MWmês)', 'Armazenamento ao final do dia (MWmês)', 'Armazenamento ao final do dia (%)'];

        $data = array_combine($energias, $somas);

        return $data;
    }


    public function pmoUsina($file, $sheet)
    {
        $indice = ['Subsistema', 'Usina', 'Situação', 'Potência Total (MW)', 'Leilão', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Data de entrada em operação - PMO', 'Diferença em relação ao anterior'];

        $rowData = $this->util->import(2, $sheet, $file);
        $usina = strtoupper(array_keys($rowData[0])[1]);

        $indiceMescla = ['0', $usina, 'situacao', 'potencia_total_mw', 'leilao'];

        if ($rowData[0]['0'] === null) {
            $this->pmoUsina_sem_subsistema($rowData, $indice, $indiceMescla);
        }

        $data = [];
        foreach ($rowData as $key=>$item) {
            foreach ($indiceMescla as $mescla) {
                $rowData = $this->util->celulaMesclada($rowData, $mescla, 1);
            }
            $linha[$key] = array_combine($indice, $rowData[$key]);

            $linha = $this->utilOns->explode_ug_pmo($linha, $key);

            $data[$linha[$key]['Subsistema']][$linha[$key]['Usina']][$linha[$key]['Situação']][$linha[$key]['Potência Total (MW)']][$linha[$key]['Leilão']][$key] = [
                'UG' => $linha[$key]['UG'],
                'MW' => $linha[$key]['MW'],
                'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                'Data de entrada em operação - PMO' => $linha[$key]['Data de entrada em operação - PMO'],
                'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
            ];
        }

        return $data;
    }

    public function pmoUsina_sem_subsistema($rowData, $indice, $indiceMescla)
    {
        unset($indice[0]);
        unset($indiceMescla[0]);

        $data = [];
        foreach ($rowData as $key => $item) {
            unset($rowData[$key]['0']);
            foreach ($indiceMescla as $mescla) {
                $rowData = $this->util->celulaMesclada($rowData, $mescla, 1);
            }
            $linha[$key] = array_combine($indice, $rowData[$key]);

            $linha = $this->utilOns->explode_ug_pmo($linha, $key);

            $data[$linha[$key]['Usina']][$linha[$key]['Situação']][$linha[$key]['Potência Total (MW)']][$linha[$key]['Leilão']][$key] = [
                'UG' => $linha[$key]['UG'],
                'MW' => $linha[$key]['MW'],
                'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                'Data de entrada em operação - PMO' => $linha[$key]['Data de entrada em operação - PMO'],
                'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
            ];
        }

        return $data;
    }

    public function pmoUsinaComb($file, $sheet)
    {
        $indice = ['Subsistema', 'Usina', 'Situação', 'Potência Total (MW)','Combustível', 'Leilão', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Data de entrada em operação - PMO', 'Diferença em relação ao anterior'];

        $rowData = $this->util->import(2, $sheet, $file);
        $usina = strtoupper(array_keys($rowData[0])[1]);

        $indiceMescla = ['0', $usina, 'situacao', 'potencia_total_mw', 'leilao'];

        $data = [];
        foreach ($rowData as $key=>$item) {
            foreach ($indiceMescla as $mescla) {
                $rowData = $this->util->celulaMesclada($rowData, $mescla, 1);
            }
            $linha[$key] = array_combine($indice, $rowData[$key]);

            if (stripos($linha[$key]['UG'], ' a ') !== false) {
                $primeiro = explode(' a ', $linha[$key]['UG'])[0];
                $ultimo = explode(' a ', $linha[$key]['UG'])[1];

                $linha[$key]['UG'] = [];
                for ($i = $primeiro; $i <= $ultimo; $i++) {
                    $linha[$key]['UG'][$i] = $linha[$key]['MW'];
                }
            }

            $data[$linha[$key]['Subsistema']][$linha[$key]['Usina']][$linha[$key]['Situação']][$linha[$key]['Potência Total (MW)']][$linha[$key]['Leilão']][$key] = [
                'UG' => $linha[$key]['UG'],
                'MW' => $linha[$key]['MW'],
                'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                'Data de entrada em operação - PMO' => $linha[$key]['Data de entrada em operação - PMO'],
                'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
            ];
        }

        return $data;
    }

    public function pmoNaoSimuladasExistente($file, $sheet, $ano = null, $mes = null)
    {
//        $date = Carbon::now()->format('Y');
        $date = $ano; //para histórico

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $rowData = $this->util->import(1, $sheet, $file);

        foreach ($rowData as $key=>$value)
        {
            for ($i = 0; $i < 5; $i++) {
                $data[] = [
                    'tipo' => array_values($value)[0],
                    'subsistema' => array_values($value)[1],
                    'usina' => array_values($value)[2],
                    'ano' => $date + $i,
                    'mes' => $mes,
                    'valor' => $this->util->formata_valores(array_combine($meses, array_slice($value, 3 + (12 * $i), 12)))
                ];
            }
        }

        return $data;
    }

    public function pmoNaoSimuladasExpansao($file, $sheet, $ano = null, $mes = null)
    {
        $data = [];
//        $date = Carbon::now()->format('Y');
        $date = $ano; //para histórico

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $rowData = $this->util->import(1, $sheet, $file);
        foreach ($rowData as $key=>$value)
        {
            for ($i = 0; $i < 5; $i++) {
                $data[] = [
                    'tipo' => array_values($value)[0],
                    'origem' => array_values($value)[1],
                    'subsistema' => array_values($value)[2],
                    'usina' => array_values($value)[3],
                    'merc' => array_values($value)[4],
                    'ano' => $date + $i,
                    'mes' => $mes,
                    'valor' => $this->util->formata_valores(array_combine($meses, array_slice($value, 5 + (12 * $i), 12)))
                ];
            }
        }

        return $data;
    }

    public function historico_pmo_ate_2015($file, $sheets)
    {
        $data = [];
        foreach ($sheets as $num => $sheet)
        {
            $rowData = $this->util->import(2, $sheet, $file);
            $usina = array_keys($rowData[0])[1];

            if (in_array('combustivel', array_keys($rowData[0]), true) !== false) {
                $indice = ['Usina', 'Situação', 'Potência Total (MW)', 'Combustível', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Diferença em relação ao anterior'];
                $indiceMescla = [$usina, 'situacao', 'potencia_total_mw', 'combustivel'];
            } else {
                $indice = ['Usina', 'Situação', 'Potência Total (MW)', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Diferença em relação ao anterior'];
                $indiceMescla = [$usina, 'situacao', 'potencia_total_mw'];
            }

            foreach ($rowData as $key => $item) {
                if ($rowData[$key]['mw'])
                {
                    unset($rowData[$key]['0']);
                    foreach ($indiceMescla as $mescla) {
                        $rowData = $this->util->celulaMesclada($rowData, $mescla, 1);
                    }

                    $linha[$key] = array_combine($indice, $rowData[$key]);
                    $linha = $this->utilOns->explode_ug_pmo($linha, $key);

                    if (isset($rowData[$key]['Combustivel'])) {
                        $data[$key] = [
                            'Tipo' => strtoupper($usina),
                            'Usina' => $linha[$key]['Usina'],
                            'Situação' => $linha[$key]['Situação'],
                            'Potência Total (MW)' => $linha[$key]['Potência Total (MW)'],
                            'Combustível' => $linha[$key]['Combustível'],
                            'UG' => $linha[$key]['UG'],
                            'MW' => $linha[$key]['MW'],
                            'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                            'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
                            ];
                    } else {
                        $data[$key] = [
                            'Tipo' => strtoupper($usina),
                            'Usina' => $linha[$key]['Usina'],
                            'Situação' => $linha[$key]['Situação'],
                            'Potência Total (MW)' => $linha[$key]['Potência Total (MW)'],
                            'UG' => $linha[$key]['UG'],
                            'MW' => $linha[$key]['MW'],
                            'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                            'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
                        ];
                    }
                }
            }
        }

        return $data;
    }

    public function historico_pmo_ate_2017($file, $sheets)
    {
        $data = [];
        foreach ($sheets as $num => $sheet)
        {
            $rowData = $this->util->import(2, $sheet, $file);
            $usina = array_keys($rowData[0])[1];

            if (in_array('combustivel', array_keys($rowData[0]), true) !== false) {
                $indice = ['Usina', 'Situação', 'Potência Total (MW)', 'Combustível', 'Leilão', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Diferença em relação ao anterior'];
                $indiceMescla = [$usina, 'situacao', 'potencia_total_mw', 'combustivel', 'leilao'];
                $num_indice = 9;
            } else {
                $indice = ['Usina', 'Situação', 'Potência Total (MW)', 'Leilão', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Diferença em relação ao anterior'];
                $indiceMescla = [$usina, 'situacao', 'potencia_total_mw', 'leilao'];
                $num_indice = 8;
            }

            foreach ($rowData as $key => $item) {
                if ($rowData[$key]['mw'])
                {
                    unset($rowData[$key]['0']);
                    $rowData[$key] = array_slice($rowData[$key], 0, $num_indice);
                    foreach ($indiceMescla as $mescla) {
                        $rowData = $this->util->celulaMesclada($rowData, $mescla, 1);
                    }

                    $linha[$key] = array_combine($indice, $rowData[$key]);

                    $linha = $this->utilOns->explode_ug_pmo($linha, $key);

                    if (isset($rowData[$key]['Combustivel'])) {
                        $data[$key] = [
                            'Tipo' => strtoupper($usina),
                            'Usina' => $linha[$key]['Usina'],
                            'Situação' => $linha[$key]['Situação'],
                            'Potência Total (MW)' => $linha[$key]['Potência Total (MW)'],
                            'Combustível' => $linha[$key]['Combustível'],
                            'Leilão' => $linha[$key]['Leilão'],
                            'UG' => $linha[$key]['UG'],
                            'MW' => $linha[$key]['MW'],
                            'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                            'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
                        ];
                    }
                    else {
                        $data[$key] = [
                            'Tipo' => strtoupper($usina),
                            'Usina' => $linha[$key]['Usina'],
                            'Situação' => $linha[$key]['Situação'],
                            'Potência Total (MW)' => $linha[$key]['Potência Total (MW)'],
                            'Leilão' => $linha[$key]['Leilão'],
                            'UG' => $linha[$key]['UG'],
                            'MW' => $linha[$key]['MW'],
                            'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                            'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
                        ];
                    }
                }
            }
        }
        return $data;
    }

    public function historico_pmo_pos_2017($file, $sheets)
    {
        $data = [];
        foreach ($sheets as $num => $sheet)
        {
            $rowData = $this->util->import(2, $sheet, $file);
            $usina = array_keys($rowData[0])[1];

            if (in_array('combustivel', array_keys($rowData[0]), true) !== false) {
                $indice = ['Usina', 'Situação', 'Potência Total (MW)', 'Leilão', 'Combustível', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Data de entrada em operação - PMO', 'Diferença em relação ao anterior'];
                $indiceMescla = [$usina, 'situacao', 'potencia_total_mw', 'leilao', 'combustivel'];
            } else {
                $indice = ['Usina', 'Situação', 'Potência Total (MW)', 'Leilão', 'UG', 'MW', 'Data de entrada em operação - DMSE', 'Data de entrada em operação - PMO', 'Diferença em relação ao anterior'];
                $indiceMescla = [$usina, 'situacao', 'potencia_total_mw', 'leilao'];
            }

            foreach ($rowData as $key => $item) {
                if ($rowData[$key]['mw'])
                {
                    unset($rowData[$key]['0']);
                    foreach ($indiceMescla as $mescla) {
                        $rowData = $this->util->celulaMesclada($rowData, $mescla, 1);
                    }
                    $linha[$key] = array_combine($indice, $rowData[$key]);

                    $linha = $this->utilOns->explode_ug_pmo($linha, $key);
                    if (isset($rowData[$key]['Combustivel'])) {
                        $data[$key] = [
                            'Tipo' => strtoupper($usina),
                            'Usina' => $linha[$key]['Usina'],
                            'Situação' => $linha[$key]['Situação'],
                            'Potência Total (MW)' => $linha[$key]['Potência Total (MW)'],
                            'Combustível' => $linha[$key]['Combustível'],
                            'Leilão' => $linha[$key]['Leilão'],
                            'UG' => $linha[$key]['UG'],
                            'MW' => $linha[$key]['MW'],
                            'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                            'Data de entrada em operação - PMO' => $linha[$key]['Data de entrada em operação - PMO'],
                            'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
                        ];
                    }
                    else {
                        $data[$key] = [
                            'Tipo' => strtoupper($usina),
                            'Usina' => $linha[$key]['Usina'],
                            'Situação' => $linha[$key]['Situação'],
                            'Potência Total (MW)' => $linha[$key]['Potência Total (MW)'],
                            'Leilão' => $linha[$key]['Leilão'],
                            'UG' => $linha[$key]['UG'],
                            'MW' => $linha[$key]['MW'],
                            'Data de entrada em operação - DMSE' => $linha[$key]['Data de entrada em operação - DMSE'],
                            'Data de entrada em operação - PMO' => $linha[$key]['Data de entrada em operação - PMO'],
                            'Diferença em relação ao anterior' => $linha[$key]['Diferença em relação ao anterior']
                        ];
                    }
                }
            }
        }
        return $data;
    }

    public function import_historico_geracao_diario($file, $fonte, $unidade)
    {
        $rowData = file($file);
        unset($rowData[0]);
        unset($rowData[1]);

        $data = [];
        foreach ($rowData as $key => $item) {
            $linha = explode(';', $rowData[$key]);

            if ($linha[0]) {
                $ano = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('Y');
                $mes = $this->util->mesMesportugues(Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('m'));
                $dia = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('d');
                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'dia' => $dia,
                    'subsitema' => $linha[1],
                    'fonte' => $fonte,
                    'valor' => [
                        $unidade => (float)$this->regexOns->convert_str($linha[7])
                    ]
                ];
            }
        }

        return $data;
    }

    public function import_historico_geracao_diario_sin($file, $fonte, $unidade)
    {
        $rowData = file($file);
        unset($rowData[0]);
        unset($rowData[1]);

        $data = [];
        foreach ($rowData as $key => $item) {
            $linha = explode(';', $rowData[$key]);

            if ($linha[0]) {
                $ano = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('Y');
                $mes = $this->util->mesMesportugues(Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('m'));
                $dia = Carbon::createFromFormat('d/m/Y H:i:s', $linha[0])->format('d');
                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'dia' => $dia,
                    'subsitema' => 'SIN',
                    'fonte' => $fonte,
                    'valor' => [
                        $unidade => (float)$this->regexOns->convert_str($linha[7])
                    ]
                ];
            }
        }

        return $data;
    }

    public function import_historico_geracao_mensal($file, $fonte, $unidade)
    {
        $rowData = file($file);
        unset($rowData[0]);
        unset($rowData[1]);

        $data = [];
        foreach ($rowData as $key => $item) {
            $linha = explode(';', $rowData[$key]);

            if ($linha[0]) {
                $ano = explode(' de ', $linha[0])[1];
                $mes = explode(' de ', $linha[0])[0];
                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'subsitema' => $linha[1],
                    'fonte' => $fonte,
                    'valor' => [
                        $unidade => (float)$this->regexOns->convert_str($linha[5])
                    ]
                ];
            }
        }

        return $data;
    }

    public function import_historico_geracao_mensal_sin($file, $fonte, $unidade)
    {
        $rowData = file($file);
        unset($rowData[0]);
        unset($rowData[1]);

        $data = [];
        foreach ($rowData as $key => $item) {
            $linha = explode(';', $rowData[$key]);

            if ($linha[0]) {
                $ano = explode(' de ', $linha[2])[1];
                $mes = explode(' de ', $linha[2])[0];
                $data[$key] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'subsitema' => 'SIN',
                    'fonte' => $fonte,
                    'valor' => [
                        $unidade => (float)$this->regexOns->convert_str($linha[9])
                    ]
                ];
            }
        }

        return $data;
    }

    public function import_historico_geracao_anual($file, $fonte, $unidade)
    {
        $rowData = file($file);
        unset($rowData[0]);
        unset($rowData[1]);

        $data = [];
        foreach ($rowData as $key => $item) {
            $linha = explode(';', $rowData[$key]);

            if ($linha[0]) {
                $ano = $linha[0];
                $data[$key] = [
                    'ano' => $ano,
                    'subsitema' => $linha[1],
                    'fonte' => $fonte,
                    'valor' => [
                        $unidade => (float)$this->regexOns->convert_str($linha[7])
                    ]
                ];
            }
        }

        return $data;
    }

    public function import_historico_geracao_anual_sin($file, $fonte, $unidade)
    {
        $rowData = file($file);
        unset($rowData[0]);
        unset($rowData[1]);

        $data = [];
        foreach ($rowData as $key => $item) {
            $linha = explode(';', $rowData[$key]);

            if ($linha[0]) {
                $ano = $linha[0];
                $data[$key] = [
                    'ano' => $ano,
                    'subsitema' => 'SIN',
                    'fonte' => $fonte,
                    'valor' => [
                        $unidade => (float)$this->regexOns->convert_str($linha[9])
                    ]
                ];
            }
        }

        return $data;
    }


}