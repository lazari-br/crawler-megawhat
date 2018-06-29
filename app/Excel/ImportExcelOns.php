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

class ImportExcelOns
{
    private $excel;
    private $startRow;


    public function __construct(Excel $excel)
    {
        $this->excel = $excel;

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

        $index = ['Norte', 'Nordeste', 'Sul', 'Sudeste', 'Total'];

        $this->setConfigStartRow(8);

        $rowDataNorte = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(8);
            })
            ->first()
            ->toArray();

        $norte = array_column(array_map($explode, array_slice(array_keys($rowDataNorte), 2)), "0");

        $this->setConfigStartRow(19);

        $rowDataSul = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(19);
            })
            ->first()
            ->toArray();

        $sul = array_column(array_map($explode, array_slice(array_keys($rowDataSul), 2)), "0");

        $valNorte = array_sum($norte);
        $valSul = array_sum($sul);

        $total = $valSul + $valNorte;

        $rowData = array_merge($norte, $sul);
        $rowData[] = $total;

        $dataFormat = array_map(function ($x) {
            return number_format($x, 3, ",", ".");
        }, $rowData);

        $data = array_combine($index, $dataFormat);

        return $data;
    }


    public function onsEnaSemanalPerc($file, $sheet)
    {

        $valida = function ($n) {
            if (is_numeric($n)) {
                ;
                return $n;
            }
        };

        $index = ['Norte', 'Nordeste', 'Sul', 'Sudeste'];

        $this->setConfigStartRow(12);
        $rowDataNorte = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(12);
            })
            ->first()
            ->toArray();

        $dataNorteEdit = array_keys($rowDataNorte);

        $this->setConfigStartRow(23);
        $rowDataSul = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) {
                $reader->limitRows(23);
            })
            ->first()
            ->toArray();

        $dataSulEdit = array_keys($rowDataSul);

        $validaDataNorte = array_map($valida, $dataNorteEdit);
        $validaDataSul = array_map($valida, $dataSulEdit);

        $dataNorte = array_filter($validaDataNorte);
        $dataSul = array_filter($validaDataSul);

        $rowData = array_merge($dataNorte, $dataSul);

        $dataFormat = array_map(function ($x) {
            return number_format($x, 20, ",", ".");
        }, $rowData);

        $data = array_combine($index, $dataFormat);

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
        die;
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
        $indice = ['Subsistema', 'Usina', 'Situação', 'Potência Total (MW)', 'Leilão', 'UG', '(MW)', 'Data de entrada em operação - DMSE', 'Data de entrada em operação - PMO', 'Diferença em relação ao anterior'];

        $this->setConfigStartRow(2);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader){
                $reader->limitRows(172);
            })
            ->toArray();

        foreach ($rowData as $key=>$item)
        {
            $data[] = array_combine($indice, array_values($item));
        }
        return $data;
    }

    public function pmoUsinaComb($file, $sheet)
    {
        $indice = ['Subsistema', 'Usina', 'Situação', 'Potência Total (MW)','Combustível', 'Leilão', 'UG', '(MW)', 'Data de entrada em operação - DMSE', 'Data de entrada em operação - PMO', 'Diferença em relação ao anterior'];

        $this->setConfigStartRow(2);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader){
                $reader->limitRows(172);
            })
            ->toArray();

        foreach ($rowData as $key=>$item)
        {
            $data[] = array_combine($indice, array_values($item));
        }
        return $data;
    }

    public function pmoNaoSimuladasExistente($file, $sheet)
    {
        $date = Carbon::now()->format('Y');

        $indice = ['Tipo',
                   'Subsistema',
                   'Usina'];

        $meses = ['Janeiro',
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

        $this->setConfigStartRow(1);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader){
                $reader->limitRows(9999999);
            })
            ->get()
            ->toArray();

        foreach ($rowData as $key=>$value)
        {
            $usina= array_combine($indice, [$value['tipo'], $value['subsistema'], $value['usina']]);

            $dataValor['Valores'][$date] = array_combine($meses, array_slice($value, 3, 12));
            $dataValor['Valores'][$date + 1] = array_combine($meses, array_slice($value, 15, 12));
            $dataValor['Valores'][$date + 2] = array_combine($meses, array_slice($value, 27, 12));
            $dataValor['Valores'][$date + 3] = array_combine($meses, array_slice($value, 39, 12));
            $dataValor['Valores'][$date + 4] = array_combine($meses, array_slice($value, 51, 12));

            $data[$value['usina']] = array_merge($usina, $dataValor);
        }
        return $data;
    }

    public function pmoNaoSimuladasExpansao($file, $sheet)
    {
        $data = [];
        $date = Carbon::now()->format('Y');

        $indice = ['Tipo',
                   'Origem',
                   'Subsistema',
                   'Usina',
                   'Merc.'];

        $meses = ['Janeiro',
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

        $this->setConfigStartRow(1);
        $rowData = \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader){
                $reader->limitRows(9999999);
            })
            ->get()
            ->toArray();

        foreach ($rowData as $key=>$value)
        {
            $usina= array_combine($indice, [$value['tipo'], $value['origem'], $value['subsistema'], $value['usina'], $value['merc.']]);

            $dataValor['Valores'][$date] = array_combine($meses, array_slice($value, 5, 12));
            $dataValor['Valores'][$date + 1] = array_combine($meses, array_slice($value, 17, 12));
            $dataValor['Valores'][$date + 2] = array_combine($meses, array_slice($value, 29, 12));
            $dataValor['Valores'][$date + 3] = array_combine($meses, array_slice($value, 41, 12));
            $dataValor['Valores'][$date + 4] = array_combine($meses, array_slice($value, 53, 12));

            $data[$value['usina']] = array_merge($usina, $dataValor);
        }
        return $data;
    }

}