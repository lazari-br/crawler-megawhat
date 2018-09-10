<?php

namespace Crawler\Http\Controllers;

use Crawler\Services\ImportServiceCcee;
use Crawler\Regex\RegexCceeInfoMercadoGeral;
use Crawler\StorageDirectory\StorageDirectory;
use Crawler\Util\Util;
use Goutte\Client;
use Carbon\Carbon;
use Crawler\Model\ArangoDb;
use Crawler\Regex\RegexCceePldSemanal;
use Crawler\Regex\RegexCcee;
use Crawler\Regex\RegexCceePldMensal;
use Crawler\Regex\RegexCceeNewaveDecomp;


class HistoricoCceeController extends Controller
{

    private $storageDirectory;
    private $client;
    private $regexCceePldSemanal;
    private $arangoDb;
    private $regexCceeInfoMercadoGeral;
    private $regexCceeNewaveDecomp;
    private $regexCceePldMensal;
    private $regexCcee;
    private $importServiceCcee;
    private $util;

    public function __construct(StorageDirectory $storageDirectory,
                                Client $client,
                                RegexCceePldSemanal $regexCceePldSemanal,
                                RegexCceePldMensal $regexCceePldMensal,
                                RegexCceeInfoMercadoGeral $regexCceeInfoMercadoGeral,
                                RegexCceeNewaveDecomp $regexCceeNewaveDecomp,
                                RegexCcee $regexCcee,
                                ImportServiceCcee $importExcelCcee,
                                Util $util,
                                ArangoDb $arangoDb)
    {
        $this->storageDirectory = $storageDirectory;
        $this->client = $client;
        $this->regexCceePldSemanal = $regexCceePldSemanal;
        $this->regexCceePldMensal = $regexCceePldMensal;
        $this->regexCceeNewaveDecomp = $regexCceeNewaveDecomp;
        $this->regexCcee = $regexCcee;
        $this->arangoDb = $arangoDb;
        $this->regexCceeInfoMercadoGeral = $regexCceeInfoMercadoGeral;
        $this->importServiceCcee = $importExcelCcee;
        $this->util = $util;
    }


    public function historico_pld_mensal()
    {
        $carbon = Carbon::now();
        $date = $carbon->format('Y-m-d');

        $url_base = "https://www.ccee.org.br/preco/precoMedio.do";

        $crawler = $this->client->request('GET', $url_base, array('allow_redirects' => true));
        $this->client->getCookieJar();

        $results = explode('<table class="displaytag-Table_soma">', $this->regexCceePldMensal->clearHtml($crawler->html()));

        $mes_ano = $this->regexCceePldMensal->capturaMes($results[1]);

        foreach ($mes_ano as $meses)
        {
            foreach ($meses as $mes)
            {
                $ano_mes[] = trim($mes);
            }
        }

        $seco = $this->regexCceePldMensal->capturaSeCo($results[1]);
        $sul = $this->regexCceePldMensal->capturaS($results[1]);
        $ne = $this->regexCceePldMensal->capturaNe($results[1]);
        $norte = $this->regexCceePldMensal->capturaN($results[1]);

        foreach ($ano_mes as $key=>$me)
        {
            $ano = explode('/', $ano_mes[$key])[1];
            $mes = $this->util->mesMesportugues(explode('/', $ano_mes[$key])[0]);

            $data['mensal'][$ano][$mes] = [$seco[$key],
                $sul[$key],
                $ne[$key],
                $norte[$key]
            ];
        }

        $this->util->enviaArangoDB('ccee', 'PLD', $date, $data);

    }

    public function historico_pld_semanal()
    {
        $anos = ['2002',
            '2003',
            '2004',
            '2005',
            '2006',
            '2007',
            '2008',
            '2009',
            '2010',
            '2011',
            '2012',
            '2013',
            '2014',
            '2015',
            '2016',
            '2017'
        ];

        $meses = ['01',
            '02',
            '03',
            '04',
            '05',
            '06',
            '07',
            '08',
            '09',
            '10',
            '11',
            '12'
        ];

        $date = [];
        foreach ($anos as $key => $ano) {
            foreach ($meses as $chave => $mes) {
                $date = $anos[$key] . $meses[$chave];

                $url_base = "https://www.ccee.org.br//preco_adm/precos/historico/semanal/index.jsp?month=".$date;

                $crawler = $this->client->request('GET', $url_base, array('allow_redirects' => true));

                $this->client->getCookieJar();

                $results = explode('<table width="100%" class="displayTag-table_soma">', $this->regexCceePldSemanal->clearHtml($crawler->html()));
                $results = array_slice($results, 1);
                $sudeste_centro_oeste = [];
                $sul = [];
                $nordeste = [];
                $norte = [];
                $date = Util::getDateIso();

                foreach ($results as $result) {
                    /** Sudeste/centro-Oeste */
                    $sudeste_centro_oeste[] = [
                        'semana' => $this->regexCceePldSemanal->getSemana($result),
                        'periodo_de' => $this->regexCceePldSemanal->getPeriodoDe($result),
                        'periodo_ate' => $this->regexCceePldSemanal->getPeriodoAte($result),
                        'pesada' => $this->regexCceePldSemanal->getSudesteCentroOestePesada($result),
                        'media' => $this->regexCceePldSemanal->getSudesteCentroOesteMedia($result),
                        'leve' => $this->regexCceePldSemanal->getSudesteCentroOesteLeve($result),
                    ];
                    /** ----------- */

                    /** Sul */
                    $sul[] = [
                        'semana' => $this->regexCceePldSemanal->getSemana($result),
                        'periodo_de' => $this->regexCceePldSemanal->getPeriodoDe($result),
                        'periodo_ate' => $this->regexCceePldSemanal->getPeriodoAte($result),
                        'pesada' => $this->regexCceePldSemanal->getSulPesada($result),
                        'media' => $this->regexCceePldSemanal->getSulMedia($result),
                        'leve' => $this->regexCceePldSemanal->getSulLeve($result),
                    ];
                    /** ----------- */

                    /** Nordeste */
                    $nordeste[] = [
                        'semana' => $this->regexCceePldSemanal->getSemana($result),
                        'periodo_de' => $this->regexCceePldSemanal->getPeriodoDe($result),
                        'periodo_ate' => $this->regexCceePldSemanal->getPeriodoAte($result),
                        'pesada' => $this->regexCceePldSemanal->getNordestePesada($result),
                        'media' => $this->regexCceePldSemanal->getNordesteMedia($result),
                        'leve' => $this->regexCceePldSemanal->getNordesteLeve($result),
                    ];
                    /** ---------- */

                    /** Norte */
                    $norte[] = [
                        'semana' => $this->regexCceePldSemanal->getSemana($result),
                        'periodo_de' => $this->regexCceePldSemanal->getPeriodoDe($result),
                        'periodo_ate' => $this->regexCceePldSemanal->getPeriodoAte($result),
                        'pesada' => $this->regexCceePldSemanal->getNortePesada($result),
                        'media' => $this->regexCceePldSemanal->getNorteMedia($result),
                        'leve' => $this->regexCceePldSemanal->getNorteLeve($result),
                    ];

                }

                $resultados['semanal'] [$ano][$this->util->mesMesportugues($mes)] = [
                    'sudeste_centro_oeste' => $sudeste_centro_oeste,
                    'sul' => $sul,
                    'nordeste' => $nordeste,
                    'norte' => $norte
                ];
            }
        }

        $this->util->enviaArangoDB('ccee', 'PLD', $date, $resultados);
    }

    public function historico_infoMercado_geral()
    {
        set_time_limit(-1);
        $date = Carbon::now()->format('Y-m-d');

        $dados['mensal']['geral']['2017'] = $this->importServiceCcee->importInfoGeral(storage_path('app/historico/ccee/InfoMercado Dados Gerais 2017.xlsx'), $date,
            4,
            2,
            26,
            6,
            23,
            9,
            24);
        $dados['mensal']['geral']['2016'] = $this->importServiceCcee->importInfoGeral(storage_path('app/historico/ccee/InfoMercado Dados Gerais 2015 vs2.xlsx'), $date,
            4,
            2,
            25,
            6,
            22,
            9,
            23);
        $dados['mensal']['geral']['2015'] = $this->importServiceCcee->importInfoGeral(storage_path('app/historico/ccee/InfoMercado Dados Gerais 2015 vs2.xlsx'), $date,
            4,
            2,
            25,
            6,
            22,
            9,
            23);
        $dados['mensal']['geral']['2014'] = $this->importServiceCcee->importInfoGeral(storage_path('app/historico/ccee/InfoMercado Dados Gerais 2014 vs3.xlsx'), $date,
            3,
            1,
            23,
            5,
            21,
            8,
            22);
        $dados['mensal']['geral']['2013'] = $this->importServiceCcee->importInfoGeral(storage_path('app/historico/ccee/InfoMercado Dados Gerais 2013 - vs1.xlsx'), $date,
            3,
            1,
            23,
            5,
            21,
            8,
            22);

        $this->util->enviaArangoDB('ccee', 'info-mercado', $date, $dados);
    }

    public function historico_infoMercado_individual()
    {
        set_time_limit(-1);
        $date = Carbon::now()->format('Y-m-d');

        $dados['mensal']['individual']['2013'] = $this->importServiceCcee->historico_infoMercado_individual_2013e2014(storage_path('app/historico/ccee/InfoMercado Dados Individuais 2013_Rev1.xlsx'), 2, $date);
        $dados['mensal']['individual']['2014'] = $this->importServiceCcee->historico_infoMercado_individual_2013e2014(storage_path('app/historico/ccee/InfoMercado Dados Individuais 2014_Rev1.xlsx'), 2, $date);
        $dados['mensal']['individual']['2015'] = $this->importServiceCcee->historico_infoMercado_individual_2015(storage_path('app/historico/ccee/InfoMercado Dados Individuais 2015_Rev1.xlsx'), 2, $date);
        $dados['mensal']['individual']['2016'] = $this->importServiceCcee->importInfoIndividual(storage_path('app/historico/ccee/InfoMercado Dados Individuais 2016_Rev1.xlsx'), 3, $date);
        $dados['mensal']['individual']['2017'] = $this->importServiceCcee->importInfoIndividual(storage_path('app/historico/ccee/InfoMercado Dados Individuais 2017_Rev1.xlsx'), 3, $date);

        $this->util->enviaArangoDB('ccee', 'info-mercado', $date, $dados);
    }

}
