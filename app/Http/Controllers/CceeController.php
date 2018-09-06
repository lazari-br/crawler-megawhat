<?php

namespace Crawler\Http\Controllers;

use Crawler\Services\ImportServiceCcee;
use Crawler\Regex\RegexCceeInfoMercadoGeral;
use Crawler\Services\ImportServiceONS;
use Crawler\StorageDirectory\StorageDirectory;
use Crawler\Util\Util;
use Goutte\Client;
use Carbon\Carbon;
use Crawler\Model\ArangoDb;
use Ixudra\Curl\Facades\Curl;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use Crawler\Regex\RegexCceePldSemanal;
use Crawler\Regex\RegexCcee;
use Crawler\Regex\RegexCceePldMensal;
use Crawler\Regex\RegexCceeNewaveDecomp;
use Chumper\Zipper\Zipper;
use Crawler\Services\NewaveDecompService;


class CceeController extends Controller
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
    private $newaveDecompController;
    private $util;

    public function __construct(StorageDirectory $storageDirectory,
                                Client $client,
                                RegexCceePldSemanal $regexCceePldSemanal,
                                RegexCceePldMensal $regexCceePldMensal,
                                RegexCceeInfoMercadoGeral $regexCceeInfoMercadoGeral,
                                RegexCceeNewaveDecomp $regexCceeNewaveDecomp,
                                RegexCcee $regexCcee,
                                ImportServiceCcee $importExcelCcee,
                                NewaveDecompService $newaveDecompController,
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
        $this->newaveDecompController = $newaveDecompController;
        $this->util = $util;
    }

    public function historicoPrecoMensal()
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
            $atual['Mensal'][$ano_mes[$key]] = [$seco[$key],
                $sul[$key],
                $ne[$key],
                $norte[$key]
            ];
        }

        $this->util->enviaArangoDB('ccee', 'PLD', $date, array_values($atual['Mensal'])[0]);

        return response()->json([
            'site' => 'https://www.ccee.org.br//preco/precoMedio.do/',
            'responsabilidade' => 'Realizar a capitura mensal das informações na tabela Html',
            'status' => 'Crawler Ccee mensal realizado com sucesso!'
        ]);

    }

    public function historicoPrecoSemanal()
    {
        $url_base = "https://www.ccee.org.br/preco_adm/precos/historico/semanal/";

        $crawler = $this->client->request('GET', $url_base, array('allow_redirects' => true));
        $result_status = $this->client->getResponse();

        $this->client->getCookieJar();

        if ($result_status->getStatus() == 200) {

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
                /** ---------- */

            }

            $resultados['Semanal'][$date] = [
                'sudeste_centro_oeste' => $sudeste_centro_oeste,
                'sul' => $sul,
                'nordeste' => $nordeste,
                'norte' => $norte
            ];

            // ------------------------------------------------------------------------Crud--------------------------------------------------------------------------------------------------

            $this->util->enviaArangoDB('ccee', 'PLD', $date, $resultados);

            return response()->json([
                'site' => 'https://www.ccee.org.br/preco_adm/precos/historico/semanal/',
                'responsabilidade' => 'Realizar a capitura semanal das informações na tabela Html',
                'status' => 'Crawler Ccee semanal realizado com sucesso!'
            ]);
        } else {

            return response()->json([
                'site' => 'https://www.ccee.org.br/preco_adm/precos/historico/semanal/',
                'responsabilidade' => 'Realizar a capitura semanal das informações na tabela Html',
                'status' => 'O crawler não encontrou o arquivo especificado!'
            ]);
        }
    }

    public function getInfoMercadoGeralAndIndividual()
    {
        set_time_limit(-1);

        $carbon = Carbon::now();
        $date = $carbon->format('Y-m-d');

        $url_base = 'https://www.ccee.org.br/';
        $url_base_1 = 'https://www.ccee.org.br/portal/js/informacoes_mercado.js?_=1524754496465';
        $url_base_2 = 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/oquefazemos/infos/abas_infomercado.jspx';

        $this->client->request('GET', $url_base_1, array('allow_redirects' => true));
        $crawler = $this->client->request('POST', $url_base_2, array('allow_redirects' => true, 'aba' => 'aba_info_mercado_mensal'));


        $cookieJar = $this->client->getCookieJar();
        $get_response_site = $this->client->getResponse();
        $get_client = $this->client->getClient();

        $downloads = [];

        if ($get_response_site->getStatus() == 200) {

            $downloads = [
                'geral' => $url_dowload_geral = $this->regexCceeInfoMercadoGeral->capturaUrlDownloadGeral($crawler->html()),
                'individual' => $url_dowload_individual = $this->regexCceeInfoMercadoGeral->capturaUrlDownloadIndividual($crawler->html()),
            ];

            foreach ($downloads as $key => $download) {

                $mont_url_download = $url_base . $download;

                $jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookieJar->all(), $url_base);
                $response = $get_client->get($mont_url_download, ['cookies' => $jar, 'allow_redirects' => true]);

                $result_download = Curl::to($mont_url_download)
                    ->setCookieJar('down')
                    ->allowRedirect(true)
                    ->withContentType('application/xlsx')
                    ->download('');

                if ($key == 'geral') {
                    $resultado['mensal']['geral']['file'] = $this->storageDirectory->saveDirectory('ccee/mensal/' . $key . '/' . $date . '/', 'InfoMercado_Dados_Gerais.xlsx', $result_download);

                    // Importação dos dados da planilha
                    $resultado['mensal']['geral'] = $this->importServiceCcee->importInfoGeral($resultado, $date,
                        5,
                        1,
                        27,
                        7,
                        24,
                        10,
                        25);

                } else {
                    $resultado['individual'][$date]['file'] = $this->storageDirectory->saveDirectory('ccee/mensal/' . $key . '/' . $date . '/', 'InfoMercado_Dados_Individuais.xlsx', $result_download);

                    // Importação dos dados da planilha
                    $resultado['individual'] = $this->importServiceCcee->importInfoIndividual($resultado, 3, $date);
                }
            }

            foreach ($resultado as $chave => $item)
            {
                if ($chave === 'geral') {
                    $this->util->enviaArangoDB('ccee', 'geral', $date, $resultado['geral']);
                }
                else{
                    $this->util->enviaArangoDB('ccee', 'individual', $date, $resultado['individual']);
                }
            }

            return response()->json([
                'site' => 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/oquefazemos/infos/abas_infomercado.jspx',
                'responsabilidade' => 'Realizar o download do arquivo info-mercado',
                'status' => 'Crawler Ccee Info-Mercado-Geral e Individual mensal realizado com sucesso!'
            ]);

        } else {
            return response()->json([
                'site' => 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/oquefazemos/infos/abas_infomercado.jspx',
                'responsabilidade' => 'Realizar o download do arquivo info-mercado',
                'status' => 'O crawler não encontrou o arquivo especificado!'
            ]);
        }
    }


    public function deckNewwave()
    {
        $date = Carbon::now()->format('Ym');
        $date_format = Carbon::now()->format('m-Y');
        $date_banco = Carbon::now()->format('Y-m-d');

        $url_base = 'https://www.ccee.org.br/';

        $downloads = [
            'newave' => $url_base . 'ccee/documentos/NW' . $date,
            'decomp' => $url_base . 'ccee/documentos/DC' . $date,
        ];

        foreach ($downloads as $key => $download) {
            $resultDownload[$key] = Curl::to($download)
                ->setCookieJar('down')
                ->allowRedirect(true)
                ->withContentType('application/zip')
                ->download('');
            if ($key === 'newave') {
                $resultado[$date_format]['file']['newave'] = $this->storageDirectory->saveDirectory('ccee/mensal/' . $date_format . '/newave/', 'newave_' . $date_format . '.zip', $resultDownload);
            } else {
                $resultado[$date_format]['file']['decomp'] = $this->storageDirectory->saveDirectory('ccee/mensal/' . $date_format . '/decomp/', 'decomp_' . $date_format . '.zip', $resultDownload);
            }
        }

        $path = storage_path('app');
        $zip = new \ZipArchive;

        if ($zip->open($path .'/ccee/mensal/' . $date_format . '/newave/newave_' . $date_format . '.zip') === TRUE) {
            $zip->extractTo($path . '/public/ccee/' . $date_banco . '/newave/', 'SISTEMA.DAT');
            $zip->close();
        }

        $arquivoNewave = file($path . '/public/ccee/' . $date_banco . '/newave/SISTEMA.DAT');
        $dados['semanal']['Newave'] = $this->newaveDecompController->cargaNewWave($arquivoNewave);

        $this->newaveDecompController->unzip('decomp_' . $date_format . '.zip', $path .'/ccee/mensal/' . $date_format . '/decomp/',  $path .'/ccee/mensal/' . $date_format . '/decomp/');

        for($i = 1; $i <= 6; $i++) {
            if ($zip->open($path . '/ccee/mensal/' . $date_format . '/decomp/DC201807-sem' . $i . '.zip') === TRUE) {
                $zip->extractTo($path . '/public/ccee/' . $date_banco . '/decomp/', 'DADGER.RV' . ($i - 1));
                $zip->close();
                $arquivoDecomp[$i] = file($path . '/public/ccee/' . $date_banco . '/decomp/' . 'DADGER.RV' . ($i - 1));
            }
        }

        foreach ($arquivoDecomp as $key => $arquivo) {
            $dados['semanal']['Decomp']['Semana '. $key] = $this->newaveDecompController->cargaDecomp($arquivo);
        }

        //Exportação para o banco
        foreach ($dados as $info => $dado) {
            if ($info === 'Newave') {
                $this->util->enviaArangoDB('ccee', 'newave', $date_banco, $dados['Newave']);
            }
            else {
                $this->util->enviaArangoDB('ccee', 'decomp', $date_banco, $dados['Decomp']);
            }
        }
    }

    public function leiloesConsolidado()
    {
        set_time_limit(-1);

        $carbon = Carbon::now();
        $date = $carbon->format('m_Y');
        $date_format = $carbon->format('d-m-Y');

        $url_base = 'https://www.ccee.org.br/ccee/documentos/';
        $crawler = $this->client->request('GET', 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/bibliotecavirtual/lista_biblioteca_virtual.jspx', array('allow_redirects' => true))->html();
        $result_status = $this->client->getResponse();

        if ($result_status->getStatus() == 200) {

            $url_leilao = $this->regexCcee->getUrlLeilao($crawler);
            $url_download = $url_base . $url_leilao;
            $download = $this->util->download($url_download, 'xlsx');
            $resultado['mensal']['file'] = $this->storageDirectory->saveDirectory('ccee/mensal/leilao/' . $date_format . '/', 'leilao_resultado_consolidado_' . $date . '.xlsx', $download);

            $resultado['mensal']['data'] = $this->importServiceCcee->leiloes($resultado);

            $this->util->enviaArangoDB('ccee', 'leiloes', $date_format, $resultado);

            return response()->json([
                'site' => 'https://www.ccee.org.br/',
                'responsabilidade' => 'Realizar o download do arquivo de leiloes',
                'status' => 'Crawler realizado com sucesso!'
            ]);

        } else {
            return response()->json([
                'site' => 'https://www.ccee.org.br/',
                'responsabilidade' => 'Realizar o download do arquivo de leiloes',
                'status' => 'O crawler não encontrou o arquivo especificado!'
            ]);
        }
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
                $result_status = $this->client->getResponse();

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

