<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09/05/18
 * Time: 13:57
 */

namespace Crawler\Excel;

use Carbon\Carbon;
use function GuzzleHttp\Promise\all;
use Maatwebsite\Excel\Excel;
use function PhpParser\filesInDir;
use Crawler\Util\Util;
use Crawler\Util\UtilCcee;
use function Psy\sh;
use Crawler\Regex\RegexCcee;


class ImportExcelCcee
{
    private $excel;
    private $startRow;
    private $util;
    private $utilCcee;
    private $regexCcee;


    public function __construct(Excel $excel,
                                RegexCcee $regexCcee,
                                UtilCcee $utilCcee,
                                Util $util)
    {
        $this->excel = $excel;
        $this->util = $util;
        $this->utilCcee = $utilCcee;
        $this->regexCcee = $regexCcee;

    }

    public function setConfigStartRow($row)
    {
        return $this->startRow = config(['excel.import.startRow' => $row]);
    }

    public function cceeConsCGPatMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSubmercado = '';
        $oldSemana = '';
        $oldPatamar = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSubmercado, &$oldSemana, &$oldPatamar, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['submercado']) ||
                        empty($rowData['no_semana']) ||
                        empty($rowData['patamar'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o submercado, semana ou patamar vazio');

                } else {
                    unset($rowData[0]);
                    $submercado = !empty($rowData['submercado']) ? $rowData['submercado'] : $oldSubmercado;
                    $semana = !empty($rowData['no_semana']) ? $rowData['no_semana'] : $oldSemana;
                    $patamar = !empty($rowData['patamar']) ? $rowData['patamar'] : $oldPatamar;
                    unset($rowData['submercado']);
                    unset($rowData['no_semana']);
                    unset($rowData['patamar']);

                    $arr = array_combine($months, $rowData);
                    $arrPatamar = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrPatamar) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");
                        }
                        $arrPatamar[$key] = $total;
                    });

                    if (isset($data[$submercado][$semana])) {
                        $data[$submercado][$semana][$patamar] = $arrPatamar;
                    } elseif (isset($data[$submercado])) {
                        $data[$submercado][$semana] = [
                            $patamar => $arrPatamar
                        ];
                    } else {
                        $data[$submercado] = [
                            $semana => [
                                $patamar => $arrPatamar
                            ]
                        ];
                    }

                    $oldSubmercado = $submercado;
                    $oldSemana = $semana;
                    $oldPatamar = $patamar;
                }
            });

        return $data;
    }

    public function cceeConsCGPatMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSubmercado = '';
        $oldSemana = '';
        $oldPatamar = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSubmercado, &$oldSemana, &$oldPatamar, &$date, $months) {
                $rowData = $i->all();
                if (
                    $k === 0 &&
                    (
                        empty($rowData['submercado']) ||
                        empty($rowData['no_semana']) ||
                        empty($rowData['patamar'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o submercado, semana ou patamar vazio');
                } else {
                    unset($rowData[0]);
                    $submercado = !empty($rowData['submercado']) ? $rowData['submercado'] : $oldSubmercado;
                    $semana = !empty($rowData['no_semana']) ? $rowData['no_semana'] : $oldSemana;
                    $patamar = !empty($rowData['patamar']) ? $rowData['patamar'] : $oldPatamar;
                    unset($rowData['submercado']);
                    unset($rowData['no_semana']);
                    unset($rowData['patamar']);

                    $arr = array_combine($months, $rowData);
                    $arrPatamar = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrPatamar) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrPatamar[$key] = $total;
                    });

                    if (isset($data[$submercado][$semana])) {
                        $data[$submercado][$semana][$patamar] = $arrPatamar;
                    } elseif (isset($data[$submercado])) {
                        $data[$submercado][$semana] = [
                            $patamar => $arrPatamar
                        ];
                    } else {
                        $data[$submercado] = [
                            $semana => [
                                $patamar => $arrPatamar
                            ]
                        ];
                    }

                    $oldSubmercado = $submercado;
                    $oldSemana = $semana;
                    $oldPatamar = $patamar;
                }
            });

        return $data;
    }

    public function cceeConsCGClMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldClasse = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldClasse, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['classe_do_agente'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a classe vazia');
                } else {
                    unset($rowData[0]);
                    $classe = !empty($rowData['classe_do_agente']) ? $rowData['classe_do_agente'] : $oldClasse;
                    unset($rowData['classe_do_agente']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");

                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$classe])) {
                        $data[$classe] = $arrClasse;
                    } elseif (isset($data[$classe])) {
                        $data[$classe] = $arrClasse;
                    } else {
                        $data[$classe] = $arrClasse;
                    }

                    $oldClasse = $classe;
                }
            });

        return $data;
    }

    public function cceeConsCGClMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldClasse = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldClasse, &$date, $months) {
                $rowData = $i->all();
                if (
                    $k === 0 &&
                    (
                    empty($rowData['classe_do_agente'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a classe vazia');

                } else {
                    unset($rowData[0]);
                    $classe = !empty($rowData['classe_do_agente']) ? $rowData['classe_do_agente'] : $oldClasse;
                    unset($rowData['classe_do_agente']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$classe])) {
                        $data[$classe] = $arrClasse;
                    } else {
                        $data[$classe] = $arrClasse;
                    }

                    $oldClasse = $classe;
                }
            });

        return $data;
    }

    public function cceeConsCGAmbMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldAmbiente = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldAmbiente, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ambiente'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ambiente vazio');

                } else {
                    unset($rowData[0]);
                    $ambiente = !empty($rowData['ambiente']) ? $rowData['ambiente'] : $oldAmbiente;
                    unset($rowData['ambiente']);

                    $arr = array_combine($months, $rowData);
                    $arrAmbiente = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrAmbiente) {
                        $total = $value;

                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");

                        }

                        $arrAmbiente[$key] = $total;
                    });

                    if (isset($data[$ambiente])) {
                        $data[$ambiente] = $arrAmbiente;
                    } else {
                        $data[$ambiente] = $arrAmbiente;

                    }

                    $oldAmbiente = $ambiente;
                }
            });

        return $data;
    }

    public function cceeConsCGAmbMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldAmbiente = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldAmbiente, &$date, $months) {
                $rowData = $i->all();
                if (
                    $k === 0 &&
                    (
                    empty($rowData['ambiente'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ambiente vazio');

                } else {
                    unset($rowData[0]);
                    $ambiente = !empty($rowData['ambiente']) ? $rowData['ambiente'] : $oldAmbiente;
                    unset($rowData['ambiente']);

                    $arr = array_combine($months, $rowData);
                    $arrAmbiente = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrAmbiente) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrAmbiente[$key] = $total;
                    });

                    if (isset($data[$ambiente])) {
                        $data[$ambiente] = $arrAmbiente;
                    } else {
                        $data[$ambiente] = $arrAmbiente;

                    }

                    $oldAmbiente = $ambiente;
                }
            });

        return $data;
    }

    public function cceeConsLivCGRamoMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldRamo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldRamo, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ramo_de_atividade'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ramo vazio');

                } else {
                    unset($rowData[0]);
                    $ramo = !empty($rowData['ramo_de_atividade']) ? $rowData['ramo_de_atividade'] : $oldRamo;
                    unset($rowData['ramo_de_atividade']);

                    $arr = array_combine($months, $rowData);
                    $arrRamo = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrRamo) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");
                        }

                        $arrRamo[$key] = $total;
                    });

                    if (isset($data[$ramo])) {
                        $data[$ramo] = $arrRamo;
                    } else {
                        $data[$ramo] = $arrRamo;

                    }

                    $oldRamo = $ramo;
                }
            });

        return $data;
    }


    public function cceeConsLivCGRamoMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldRamo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldRamo, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ramo_de_atividade'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ramo vazio');

                } else {
                    unset($rowData[0]);
                    $ramo = !empty($rowData['ramo_de_atividade']) ? $rowData['ramo_de_atividade'] : $oldRamo;
                    unset($rowData['ramo_de_atividade']);

                    $arr = array_combine($months, $rowData);
                    $arrRamo = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrRamo) {
                        $total = $value;

                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }
                        $arrRamo[$key] = $total;
                    });

                    if (isset($data[$ramo])) {
                        $data[$ramo] = $arrRamo;
                    } else {
                        $data[$ramo] = $arrRamo;

                    }

                    $oldRamo = $ramo;
                }
            });
        return $data;
    }


    public function cceeConsGerCGMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSubmercado = '';
        $oldSemana = '';
        $oldPatamar = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSubmercado, &$oldSemana, &$oldPatamar, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['submercado']) ||
                        empty($rowData['no_semana']) ||
                        empty($rowData['patamar'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o submercado, semana ou patamar vazio');
                } else {
                    unset($rowData[0]);
                    $submercado = !empty($rowData['submercado']) ? $rowData['submercado'] : $oldSubmercado;
                    $semana = !empty($rowData['no_semana']) ? $rowData['no_semana'] : $oldSemana;
                    $patamar = !empty($rowData['patamar']) ? $rowData['patamar'] : $oldPatamar;
                    unset($rowData['submercado']);
                    unset($rowData['no_semana']);
                    unset($rowData['patamar']);

                    $arr = array_combine($months, $rowData);
                    $arrPatamar = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrPatamar) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");

                        }

                        $arrPatamar[$key] = $total;
                    });

                    if (isset($data[$submercado][$semana])) {
                        $data[$submercado][$semana][$patamar] = $arrPatamar;
                    } elseif (isset($data[$submercado])) {
                        $data[$submercado][$semana] = [
                            $patamar => $arrPatamar
                        ];
                    } else {
                        $data[$submercado] = [
                            $semana => [
                                $patamar => $arrPatamar
                            ]
                        ];
                    }

                    $oldSubmercado = $submercado;
                    $oldSemana = $semana;
                    $oldPatamar = $patamar;
                }
            });

        return $data;
    }

    public function cceeConsGerCGMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSubmercado = '';
        $oldSemana = '';
        $oldPatamar = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSubmercado, &$oldSemana, &$oldPatamar, &$date, $months) {
                $rowData = $i->all();
                if (
                    $k === 0 &&
                    (
                        empty($rowData['submercado']) ||
                        empty($rowData['no_semana']) ||
                        empty($rowData['patamar'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o submercado, semana ou patamar vazio');
                } else {
                    unset($rowData[0]);
                    $submercado = !empty($rowData['submercado']) ? $rowData['submercado'] : $oldSubmercado;
                    $semana = !empty($rowData['no_semana']) ? $rowData['no_semana'] : $oldSemana;
                    $patamar = !empty($rowData['patamar']) ? $rowData['patamar'] : $oldPatamar;
                    unset($rowData['submercado']);
                    unset($rowData['no_semana']);
                    unset($rowData['patamar']);

                    $arr = array_combine($months, $rowData);
                    $arrPatamar = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrPatamar) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrPatamar[$key] = $total;
                    });

                    if (isset($data[$submercado][$semana])) {
                        $data[$submercado][$semana][$patamar] = $arrPatamar;
                    } elseif (isset($data[$submercado])) {
                        $data[$submercado][$semana] = [
                            $patamar => $arrPatamar
                        ];
                    } else {
                        $data[$submercado] = [
                            $semana => [
                                $patamar => $arrPatamar
                            ]
                        ];
                    }

                    $oldSubmercado = $submercado;
                    $oldSemana = $semana;
                    $oldPatamar = $patamar;
                }
            });

        return $data;
    }

    public function cceeConsLivPCRamoMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldRamo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldRamo, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ramo_de_atividade'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ramo vazio');

                } else {
                    unset($rowData[0]);
                    $ramo = !empty($rowData['ramo_de_atividade']) ? $rowData['ramo_de_atividade'] : $oldRamo;
                    unset($rowData['ramo_de_atividade']);

                    $arr = array_combine($months, $rowData);
                    $arrRamo = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrRamo) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");
                        }

                        $arrRamo[$key] = $total;
                    });

                    if (isset($data[$ramo])) {
                        $data[$ramo] = $arrRamo;
                    } else {
                        $data[$ramo] = $arrRamo;

                    }

                    $oldRamo = $ramo;
                }
            });

        return $data;
    }


    public function cceeConsLivPCRamoMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldRamo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldRamo, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ramo_de_atividade'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ramo vazio');

                } else {
                    unset($rowData[0]);
                    $ramo = !empty($rowData['ramo_de_atividade']) ? $rowData['ramo_de_atividade'] : $oldRamo;
                    unset($rowData['ramo_de_atividade']);

                    $arr = array_combine($months, $rowData);
                    $arrRamo = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrRamo) {
                        $total = $value;

                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }
                        $arrRamo[$key] = $total;
                    });

                    if (isset($data[$ramo])) {
                        $data[$ramo] = $arrRamo;
                    } else {
                        $data[$ramo] = $arrRamo;

                    }

                    $oldRamo = $ramo;
                }
            });
        return $data;
    }


    public function cceeConsAutoProdPCRamoMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldRamo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldRamo, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ramo_de_atividade'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ramo vazio');

                } else {
                    unset($rowData[0]);
                    $ramo = !empty($rowData['ramo_de_atividade']) ? $rowData['ramo_de_atividade'] : $oldRamo;
                    unset($rowData['ramo_de_atividade']);

                    $arr = array_combine($months, $rowData);
                    $arrRamo = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrRamo) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");
                        }

                        $arrRamo[$key] = $total;
                    });

                    if (isset($data[$ramo])) {
                        $data[$ramo] = $arrRamo;
                    } else {
                        $data[$ramo] = $arrRamo;

                    }

                    $oldRamo = $ramo;
                }
            });

        return $data;
    }


    public function cceeConsAutoProdPCRamoMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldRamo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldRamo, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['ramo_de_atividade'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o ramo vazio');

                } else {
                    unset($rowData[0]);
                    $ramo = !empty($rowData['ramo_de_atividade']) ? $rowData['ramo_de_atividade'] : $oldRamo;
                    unset($rowData['ramo_de_atividade']);

                    $arr = array_combine($months, $rowData);
                    $arrRamo = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrRamo) {
                        $total = $value;

                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }
                        $arrRamo[$key] = $total;
                    });

                    if (isset($data[$ramo])) {
                        $data[$ramo] = $arrRamo;
                    } else {
                        $data[$ramo] = $arrRamo;

                    }

                    $oldRamo = $ramo;
                }
            });
        return $data;
    }

    public function cceeGerCGFontMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldFonte = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldFonte, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['fonte_de_geracao'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a fonte vazia');

                } else {
                    unset($rowData[0]);
                    $fonte = !empty($rowData['fonte_de_geracao']) ? $rowData['fonte_de_geracao'] : $oldFonte;
                    unset($rowData['fonte_de_geracao']);

                    $arr = array_combine($months, $rowData);
                    $arrFonte = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrFonte) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");
                        }

                        $arrFonte[$key] = $total;
                    });

                    if (isset($data[$fonte])) {
                        $data[$fonte] = $arrFonte;
                    } else {
                        $data[$fonte] = $arrFonte;

                    }

                    $oldFonte = $fonte;
                }
            });

        return $data;
    }


    public function cceeGerCGFontMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldFonte = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldFonte, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['fonte_de_geracao'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a fonte vazia');

                } else {
                    unset($rowData[0]);
                    $fonte = !empty($rowData['fonte_de_geracao']) ? $rowData['fonte_de_geracao'] : $oldFonte;
                    unset($rowData['fonte_de_geracao']);

                    $arr = array_combine($months, $rowData);
                    $arrFonte = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrFonte) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrFonte[$key] = $total;
                    });

                    if (isset($data[$fonte])) {
                        $data[$fonte] = $arrFonte;
                    } else {
                        $data[$fonte] = $arrFonte;

                    }

                    $oldFonte = $fonte;
                }
            });

        return $data;
    }

    public function cceeGerCGPatMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSubmercado = '';
        $oldSemana = '';
        $oldPatamar = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSubmercado, &$oldSemana, &$oldPatamar, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['submercado']) ||
                        empty($rowData['no_semana']) ||
                        empty($rowData['patamar'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o submercado, semana ou patamar vazio');

                } else {
                    unset($rowData[0]);
                    $submercado = !empty($rowData['submercado']) ? $rowData['submercado'] : $oldSubmercado;
                    $semana = !empty($rowData['no_semana']) ? $rowData['no_semana'] : $oldSemana;
                    $patamar = !empty($rowData['patamar']) ? $rowData['patamar'] : $oldPatamar;
                    unset($rowData['submercado']);
                    unset($rowData['no_semana']);
                    unset($rowData['patamar']);

                    $arr = array_combine($months, $rowData);
                    $arrPatamar = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrPatamar) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");

                        }

                        $arrPatamar[$key] = $total;
                    });

                    if (isset($data[$submercado][$semana])) {
                        $data[$submercado][$semana][$patamar] = $arrPatamar;
                    } elseif (isset($data[$submercado])) {
                        $data[$submercado][$semana] = [
                            $patamar => $arrPatamar
                        ];
                    } else {
                        $data[$submercado] = [
                            $semana => [
                                $patamar => $arrPatamar
                            ]
                        ];
                    }

                    $oldSubmercado = $submercado;
                    $oldSemana = $semana;
                    $oldPatamar = $patamar;
                }
            });

        return $data;
    }

    public function cceeGerCGPatMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldSubmercado = '';
        $oldSemana = '';
        $oldPatamar = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldSubmercado, &$oldSemana, &$oldPatamar, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['submercado']) ||
                        empty($rowData['no_semana']) ||
                        empty($rowData['patamar'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o submercado, semana ou patamar vazio');

                } else {
                    unset($rowData[0]);
                    $submercado = !empty($rowData['submercado']) ? $rowData['submercado'] : $oldSubmercado;
                    $semana = !empty($rowData['no_semana']) ? $rowData['no_semana'] : $oldSemana;
                    $patamar = !empty($rowData['patamar']) ? $rowData['patamar'] : $oldPatamar;
                    unset($rowData['submercado']);
                    unset($rowData['no_semana']);
                    unset($rowData['patamar']);

                    $arr = array_combine($months, $rowData);
                    $arrPatamar = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrPatamar) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrPatamar[$key] = $total;
                    });

                    if (isset($data[$submercado][$semana])) {
                        $data[$submercado][$semana][$patamar] = $arrPatamar;
                    } elseif (isset($data[$submercado])) {
                        $data[$submercado][$semana] = [
                            $patamar => $arrPatamar
                        ];
                    } else {
                        $data[$submercado] = [
                            $semana => [
                                $patamar => $arrPatamar
                            ]
                        ];
                    }

                    $oldSubmercado = $submercado;
                    $oldSemana = $semana;
                    $oldPatamar = $patamar;
                }
            });

        return $data;
    }

    public function cceeNumAgClasse($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldClasse= '';
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldClasse, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['classe'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a classe vazia');

                } else {
                    unset($rowData[0]);
                    $classe = !empty($rowData['classe']) ? $rowData['classe'] : $oldClasse;
                    unset($rowData['classe']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 0, ",", ".");
                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$classe])) {
                        $data[$classe] = $arrClasse;
                    } else {
                        $data[$classe] = $arrClasse;

                    }

                    $oldClasse= $classe;
                }
            });

        return $data;
    }

    public function cceeMontCGTipoMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldTipo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldTipo, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['tipo_de_contrato'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o tipo vazio');

                } else {
                    unset($rowData[0]);
                    $tipo = !empty($rowData['tipo_de_contrato']) ? $rowData['tipo_de_contrato'] : $oldTipo;
                    unset($rowData['tipo_de_contrato']);

                    $arr = array_combine($months, $rowData);
                    $arrTipo = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrTipo) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");
                        }

                        $arrTipo[$key] = $total;
                    });

                    if (isset($data[$tipo])) {
                        $data[$tipo] = $arrTipo;
                    } else {
                        $data[$tipo] = $arrTipo
                        ;

                    }

                    $oldTipo= $tipo;
                }
            });

        return $data;
    }


    public function cceeMontCGTipoMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldTipo = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldTipo, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                    empty($rowData['tipo_de_contrato'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com o tipo vazio');

                } else {
                    unset($rowData[0]);
                    $tipo = !empty($rowData['tipo_de_contrato']) ? $rowData['tipo_de_contrato'] : $oldTipo;
                    unset($rowData['tipo_de_contrato']);

                    $arr = array_combine($months, $rowData);
                    $arrTipo = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrTipo) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");
                        }

                        $arrTipo[$key] = $total;
                    });

                    if (isset($data[$tipo])) {
                        $data[$tipo] = $arrTipo;
                    } else {
                        $data[$tipo] = $arrTipo
                        ;

                    }

                    $oldTipo= $tipo;
                }
            });

        return $data;
    }


    public function cceeMontCGClasseCompVendMWh($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldVend = '';
        $oldComp = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldVend, &$oldComp, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['classe_do_vendedor']) ||
                        empty($rowData['classe_do_comprador'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a classe do comprador ou do vendedor vazia');

                } else {
                    unset($rowData[0]);
                    $vend = !empty($rowData['classe_do_vendedor']) ? $rowData['classe_do_vendedor'] : $oldVend;
                    $comp = !empty($rowData['classe_do_comprador']) ? $rowData['classe_do_comprador'] : $oldComp;
                    unset($rowData['classe_do_vendedor']);
                    unset($rowData['classe_do_comprador']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");

                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$vend][$comp])) {
                        $data[$vend][$comp]= $arrClasse;
                    } elseif (isset($data[$vend])) {
                        $data[$vend][$comp] = $arrClasse;
                    } else {
                        $data[$vend] = [$comp => $arrClasse
                        ];
                    }

                    $oldVend = $vend;
                    $oldComp = $comp;
                }
            });

        return $data;
    }

    public function cceeMontCGClasseCompVendMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldVend = '';
        $oldComp = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldVend, &$oldComp, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['classe_do_vendedor']) ||
                        empty($rowData['classe_do_comprador'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com a classe do comprador ou do vendedor vazia');

                } else {
                    unset($rowData[0]);
                    $vend = !empty($rowData['classe_do_vendedor']) ? $rowData['classe_do_vendedor'] : $oldVend;
                    $comp = !empty($rowData['classe_do_comprador']) ? $rowData['classe_do_comprador'] : $oldComp;
                    unset($rowData['classe_do_vendedor']);
                    unset($rowData['classe_do_comprador']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$vend][$comp])) {
                        $data[$vend][$comp]= $arrClasse;
                    } elseif (isset($data[$vend])) {
                        $data[$vend][$comp] = $arrClasse;
                    } else {
                        $data[$vend] = [$comp => $arrClasse
                        ];
                    }

                    $oldVend = $vend;
                    $oldComp = $comp;
                }
            });

        return $data;
    }


    public function cceeIncentContrCompMWh($file, $sheet, $startRow, $takeRows, $date)   //Problema na leitura das %s dos índices (' aparece em algumas células e em outras não)
    {
        $data = [];
        $oldModalidade = '';
        $oldPercent = '';
        $oldClasse = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldModalidade, &$oldPercent, &$oldClasse, &$date, $months, $daysInMonths) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['modalidade_energia']) ||
                        empty($rowData['percentual_de_desconto_do_vendedor']) ||
                        empty($rowData['classe_comprador'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com modalidade, percentual ou classe vazio');

                } else {
                    unset($rowData[0]);
                    $modalidade = !empty($rowData['modalidade_energia']) ? $rowData['modalidade_energia'] : $oldModalidade;
                    $percent= !empty($rowData['percentual_de_desconto_do_vendedor']) ? $rowData['percentual_de_desconto_do_vendedor'] : $oldPercent;
                    $classe = !empty($rowData['classe_comprador']) ? $rowData['classe_comprador'] : $oldClasse;
                    unset($rowData['modalidade_energia']);
                    unset($rowData['percentual_de_desconto_do_vendedor']);
                    unset($rowData['classe_comprador']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, $daysInMonths, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total_round = round($value * 24 * $daysInMonths[$key], 3);
                            $total = number_format($total_round, 3, ",", ".");

                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$modalidade][$percent])) {
                        $data[$modalidade][$percent][$classe] = $arrClasse;
                    } elseif (isset($data[$modalidade])) {
                        $data[$modalidade][$percent] = [
                            $classe => $arrClasse
                        ];
                    } else {
                        $data[$modalidade] = [
                            $percent=> [
                                $classe => $arrClasse
                            ]
                        ];
                    }

                    $oldModalidade = $modalidade;
                    $oldPercent = $percent;
                    $oldClasse= $classe;
                }
            });

        return $data;
    }

    public function cceeIncentContrCompMWm($file, $sheet, $startRow, $takeRows, $date)
    {
        $data = [];
        $oldModalidade = '';
        $oldPercent = '';
        $oldClasse = '';
        $year = $date->year;
        $this->setConfigStartRow($startRow);
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        \Excel::selectSheetsByIndex($sheet)
            ->load($file, function ($reader) use ($takeRows) {
                $reader->takeRows($takeRows);
            })
            ->get()
            ->each(function ($i, $k) use (&$data, &$oldModalidade, &$oldPercent, &$oldClasse, &$date, $months) {
                $rowData = $i->all();

                if (
                    $k === 0 &&
                    (
                        empty($rowData['modalidade_energia']) ||
                        empty($rowData['percentual_de_desconto_do_vendedor']) ||
                        empty($rowData['classe_comprador'])
                    )
                ) {
                    throw new \Exception('O primeiro item não pode estar com modalidade, percentual ou classe vazio');

                } else {
                    unset($rowData[0]);
                    $modalidade = !empty($rowData['modalidade_energia']) ? $rowData['modalidade_energia'] : $oldModalidade;
                    $percent= !empty($rowData['percentual_de_desconto_do_vendedor']) ? $rowData['percentual_de_desconto_do_vendedor'] : $oldPercent;
                    $classe = !empty($rowData['classe_comprador']) ? $rowData['classe_comprador'] : $oldClasse;
                    unset($rowData['modalidade_energia']);
                    unset($rowData['percentual_de_desconto_do_vendedor']);
                    unset($rowData['classe_comprador']);

                    $arr = array_combine($months, $rowData);
                    $arrClasse = [];
                    array_walk($arr, function ($value, $key) use ($date, &$arrClasse) {
                        $total = $value;
                        if (!is_null($value)) {
                            $total = number_format($value, 3, ",", ".");

                        }

                        $arrClasse[$key] = $total;
                    });

                    if (isset($data[$modalidade][$percent])) {
                        $data[$modalidade][$percent][$classe] = $arrClasse;
                    } elseif (isset($data[$modalidade])) {
                        $data[$modalidade][$percent] = [
                            $classe => $arrClasse
                        ];
                    } else {
                        $data[$modalidade] = [
                            $percent=> [
                                $classe => $arrClasse
                            ]
                        ];
                    }

                    $oldModalidade = $modalidade;
                    $oldPercent = $percent;
                    $oldClasse= $classe;
                }
            });

        return $data;
    }

    public function cceeEss($file, $sheet)
    {

        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $receb = $this->util->import(15, $sheet, $file, 20);

        foreach ($receb as $key => $linha) {
            if ($linha['componente'] === 'Total') {
                $rowData = array_slice($linha, 2);
            }
        }
        array_walk($rowData, function ($value, $key) use (&$arrData) {
            $total = $value;
            if (!is_null($value)) {
                $total = number_format($value, 2, ",", ".");
            }
            $arrData[$key] = $total;
        });
        $data = array_combine($months, $arrData);

        return $data;
    }


    public function cceeEssPorMWh($file, $sheet, $date)
    {

        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $year = $date->year;
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        $receb = $this->util->import(15, $sheet, $file, 20);

        foreach ($receb as $key => $linha) {
            if ($linha['componente'] === 'Total') {
                $dataReceb = array_slice($receb[0], 2);
            }
        }

        $cons = $this->util->import(527, $sheet, $file, 527);
        $rowCons = array_slice($cons[0], 2);
        $complemento = [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null];
        $rowDataCons = array_slice(array_merge($rowCons, $complemento), 0, 12);

        $dataMWh = array_map(function($x, $y){
            if (!is_null($x) && !is_null($y)) {
                return (24*$x*$y);
            }
        },
            $rowDataCons, $daysInMonths);

        $func = function ($n) {
            if (!is_null($n)) {

                return (1 / ($n));
            }};

        $dataCons = array_map($func, $dataMWh);
        $rowData = array_map(function($x, $y){return number_format(($x*$y), 15, ",", ".");},$dataCons, $dataReceb);
        $data = array_combine($months, $rowData);

        return $data;
    }



    public function cceeEer($file, $sheet)
    {

        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $valor = $this->util->import(162, $sheet, $file, 162, 0);
        $rowData = array_slice($valor[0], 2);

        array_walk($rowData, function ($value, $key) use (&$arrData) {
            $total = $value;
            if (!is_null($value)) {
                $total = number_format($value, 2, ",", ".");
            }
            $arrData[$key] = $total;
        });

        $data = array_combine($months, $arrData);

        return $data;
    }


    public function cceeEerPorMWh($file, $sheet, $date)
    {

        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $year = $date->year;
        $daysInMonths = [
            'Janeiro' => 31,
            'Fevereiro' => Carbon::createFromFormat('m/Y', '02/' . $year)->daysInMonth,
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

        $custo = $this->util->import(162, $sheet, $file, 162, 0);
        $dataCusto = array_slice($custo, 2);

        $energ = $this->util->import(172, $sheet, $file, 172, 0);
        $rowDataEnerg = array_slice($energ, 2);

        $dataMWh = array_map(function($x, $y){
            if (!is_null($x) && !is_null($y)) {
                return (24*$x*$y);
            }
        },
            $rowDataEnerg, $daysInMonths);

        $func = function ($n){
            if (!is_null($n)) {
                return (1 / $n);
            }};

        $dataEnerg = array_map($func, $dataMWh);

        $rowData = array_map(function($x, $y){
            return number_format(($x*$y), 15, ",", ".");
        },
        $dataCusto, $dataEnerg);
        $data = array_combine($months, $rowData);

        return $data;
    }


    public function cceeUsinas($file, $sheet)
    {
        $indice = ['Código do Ativo',
                   'Sigla do Ativo',
                   'CEG do empreendimento',
                   'Código da parcela da Usina',
                   'Parcela de Usina',
                   'Tipo de Despacho',
                   'Participante do Rateio de Perdas',
                   'Fonte de Energia Primária',
                   'Submercado',
                   'UF',
                   'Característica da Parcela',
                   'Participante do MRE',
                   'Participante do Regima de Cotas',
                   '% de Desconto',
                   'Capacidade da Usina (i) - MW (CAP_T)',
                   'Garantia Física (ii) MW médio (GF)',
                   'Fator de Operação Comercial (iv) (F_COMERCIALp,j)',
                   'Código Perfil',
                   'Sigla' ,
                   'Nome Empresarial'];

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

        $patamar = ['Leve', 'Médio', 'Pesado'];

        $data = [];
        $data212 = [];
        $fim = 0;
        $rowData = array_slice($this->util->import(23, $sheet, $file, 9999, 0), 1);
        foreach ($rowData as $key => $item)
        {
            if ($rowData[$key]['patamar'] === null){
                $fim = $key;
                break;
            }
            $data[$key] = array_combine($indice, array_slice($item, 1, 20));

            if ($data[$key]['Código da parcela da Usina'] === null) {
                unset ($data[$key]);
            }
            if (fmod($key, 3) === 0.0) {
                $data = $this->util->celulaMesclada($data, 'Código do Ativo', 3);
                $data = $this->util->celulaMesclada($data, 'Sigla do Ativo', 3);
                $data = $this->util->celulaMesclada($data, 'CEG do empreendimento', 3);
            }
        }

        $rowDataGeracao = $this->util->import(24, $sheet, $file, (24 + $fim) - 1, 0);
        foreach ($rowDataGeracao as $keys => $conteudo) {
            $dataGeracao[$keys] = array_combine($meses, array_slice($conteudo, 1, 12));
        }
        foreach ($data as $chave => $info)
        {
            $data[$chave]['Geração no centro de gravidade (v) por Patamar - MWh (Gp,j)'] = array_combine($patamar, [$dataGeracao[$chave + 0],
                                                                                                                    $dataGeracao[$chave + 1],
                                                                                                                    $dataGeracao[$chave + 2]]);
            if ($data[$chave]['Código do Ativo'] === 244.0 ||
                $data[$chave]['Código do Ativo'] === 331.0 ||
                $data[$chave]['Código do Ativo'] === 543.0) {
                $data[$chave] = $this->utilCcee->addExcecoesUsinas($data[$chave]);
            }
            elseif ($data[$chave]['Código do Ativo'] === 212.0) {
                $data212[] = $data[$chave];
                unset ($data[$chave]);
            }
        }
        $data[$fim] = $this->utilCcee->addExcecao212($data212, $chave, $fim);

        return $data;
    }


    public  function leilao($file, $sheet)
    {
        $carbon = Carbon::now();
        $date_indice = $carbon->format('m_Y');

        $indices = ['ID de Negociação',
                    'Número de Leilão',
                    'Tipo de Leilão',
                    'Número de Edital',
                    'Produto',
                    'Sigla do Vendedor',
                    'Razão Social do Vendedor',
                    'CNPJ do Vendedor',
                    'Sigla do Comprador',
                    'Razão Social do  Comprador',
                    'CNPJ do Comprador',
                    'CEG',
                    'Nome da Usina',
                    'Situação',
                    'Nota Explicativa',
                    'Submercado do Registro do Contrato',
                    'Tipo de Usina',
                    'UF da Usina',
                    'Fonte Energética',
                    'Combustível ou Rio da Usina',
                    'Potência da Usina (MW)',
                    'Potência Final Instalada (MWmed)',
                    'Garantia Física da Usina (MWmed)',
                    'Energia Negociada por Contrato (MWh)',
                    'Energia Negociada por Contrato para o Ano A (MWmed)',
                    'Energia Negociada por Contrato para o Ano A + 1 (MWmed)',
                    'Energia Negociada por Contrato para o Ano A + 2 (MWmed)',
                    'Energia Negociada por Contrato para o Ano A + 3 (MWmed)',
                    'Energia Negociada por Contrato para os demais anos (MWmed)',
                    'Tipo de Contrato(QTD/DIS)',
                    'Montante financeiro negociado por contrato (em milhões R$)',
                    'Montante financeiro negociado por contrato atualizado (Reais em milhões)',
                    'Preço de Venda ou ICB na data do leilão (R$/MWh)',
                    'ICE (R$/MWh)',
                    'Data de Realização do leilão',
                    'IPCA na data do leilão',
                    'IPCA ' . $date_indice,
                    'Preço de venda atualizado (R$/MWh)',
                    'Receita fixa por contrato na data do leilão para o ano A (R$/ano)',
                    'Receita fixa por contrato na data do leilão para o ano A + 1 (R$/ano)',
                    'Receita fixa por contrato na data do leilão para os demais anos (R$/ano)',
                    'Data do Início de Suprimento',
                    'Data do Fim de Suprimento',
                    'Possibilidade de escalonamento da entrega da energia do contrato (SIM/NÃO)',
                    'Entrega escalonada (SIM/NÃO)'
        ];

        $rowData = $this->util->import(10, $sheet, $file, 9999999, 0);

        $data = [];
        foreach ($rowData as $key => $item) {
            $data[$key] = array_combine($indices,  array_slice($item, 1));

            $inicio = 2000 + $this->regexCcee->getSuprimento($data[$key]['Data do Início de Suprimento']);
            $fim = 2000 + $this->regexCcee->getSuprimento($data[$key]['Data do Fim de Suprimento']);

            $data[$key]['Energia Negociada por Contrato para '. ($inicio) .' (MWh)'] = $this->utilCcee->calculaDias($data[$key]['Data do Início de Suprimento'], $data[$key]['Energia Negociada por Contrato para o Ano A (MWmed)']);

            for ($i = 1; $i < $fim - $inicio; $i++){
                $diasAno = $this->util->diasAno($inicio + $i);
                if ($i < 4) {
                    $data[$key]['Energia Negociada por Contrato para '. ($inicio + $i) .' (MWh)'] = $data[$key]['Energia Negociada por Contrato para o Ano A + '. $i .' (MWmed)'] * 24 * $diasAno;
                } else {
                    $data[$key]['Energia Negociada por Contrato para '. ($inicio + $i) .' (MWh)'] = $data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)'] * 24 * $diasAno;
                }
            }
            $data[$key]['Energia Negociada por Contrato para '. ($fim) .' (MWh)'] = $this->utilCcee->calculaDias($data[$key]['Data do Fim de Suprimento'], 0, $data[$key]['Energia Negociada por Contrato para os demais anos (MWmed)']);

            $data[$key]['Data de Realização do leilão'] = $this->util->dateEdit($data[$key]['Data de Realização do leilão']);
            $data[$key]['Data do Início de Suprimento'] = $this->util->dateEdit($data[$key]['Data do Início de Suprimento']);
            $data[$key]['Data do Fim de Suprimento'] = $this->util->dateEdit($data[$key]['Data do Fim de Suprimento']);
        }
        return $data;
    }

}