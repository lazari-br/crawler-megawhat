<?php

namespace Crawler\Http\Controllers;

use Crawler\Excel\ImportExcelCcee;
use Crawler\Regex\RegexCceeInfoMercadoGeral;
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
use Crawler\Regex\RegexCceePldMensal;
use Crawler\Regex\RegexCceeNewaveDecomp;


class CceeController extends Controller
{
    private $storageDirectory;
    private $client;
    private $regexCceePldSemanal;
    private $arangoDb;
    private $regexCceeInfoMercadoGeral;
    private $regexCceeNewaveDecomp;
    private $regexCceePldMensal;
    private $importExcelCcee;

    public function __construct(StorageDirectory $storageDirectory,
                                Client $client,
                                RegexCceePldSemanal $regexCceePldSemanal,
                                RegexCceePldMensal $regexCceePldMensal,
                                RegexCceeInfoMercadoGeral $regexCceeInfoMercadoGeral,
                                RegexCceeNewaveDecomp $regexCceeNewaveDecomp,
                                ImportExcelCcee $importExcelCcee,
                                ArangoDb $arangoDb)
    {
        $this->storageDirectory = $storageDirectory;
        $this->client = $client;
        $this->regexCceePldSemanal = $regexCceePldSemanal;
        $this->regexCceePldMensal = $regexCceePldMensal;
        $this->regexCceeNewaveDecomp = $regexCceeNewaveDecomp;
        $this->arangoDb = $arangoDb;
        $this->regexCceeInfoMercadoGeral = $regexCceeInfoMercadoGeral;
        $this->importExcelCcee = $importExcelCcee;
    }
    public function historicoPrecoMensal()
    {
        $url_base = "https://www.ccee.org.br/preco/precoMedio.do";

        $crawler = $this->client->request('GET', $url_base, array('allow_redirects' => true));
        $this->client->getCookieJar();


        $results  = explode('<table class="displaytag-Table_soma">',$this->regexCceePldMensal->clearHtml($crawler->html()));


        foreach ($results as $result) {
            $mes_ano = $this->regexCceePldMensal->capturaMes($result);
        }

         $ano_mes = Util::getMesAno($mes_ano);

        foreach ($results as $result)
        {
            $atual['Mensal'][$ano_mes]= [
                'Sudeste_Centro-oeste' => $this->regexCceePldMensal->capturaSeCo($result),
                'Sul' => $this->regexCceePldMensal->capturaS($result),
                'Nordeste' => $this->regexCceePldMensal->capturaNe($result),
                'Norte' => $this->regexCceePldMensal->capturaN($result),
            ];

        }

        try {

            if ($this->arangoDb->collectionHandler()->has('ccee')) {

                $this->arangoDb->documment()->set('Pld', $atual);
                $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());

            } else {

                // create a new collection
                $this->arangoDb->collection()->setName('ccee');

                $this->arangoDb->documment()->set('Pld', $atual);
                $this->arangoDb->collectionHandler()->create('ccee', $this->arangoDb->documment());
            }
        } catch (ArangoConnectException $e) {
            print 'Connection error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoClientException $e) {
            print 'Client error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoServerException $e) {
            print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
        }

        return response()->json([
            'site' => 'https://www.ccee.org.br//preco/precoMedio.do/',
            'responsabilidade' => 'Realizar a capitura mensal das informações na tabela Html',
            'status' => 'Crawler Ccee mensal realizado com sucesso!'
        ]);
    }

    public function historicoPrecoSemanal()
    {
        $url_base = "https://www.ccee.org.br/preco_adm/precos/historico/semanal/";

        $crawler = $this->client->request('GET', $url_base,array('allow_redirects' => true));
        $result_status = $this->client->getResponse();

        $this->client->getCookieJar();

        if($result_status->getStatus() == 200) {

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

            $resultados = [
                'sudeste_centro_oeste' => $sudeste_centro_oeste,
                'sul' => $sul,
                'nordeste' => $nordeste,
                'norte' => $norte

            ];

            // ------------------------------------------------------------------------Crud--------------------------------------------------------------------------------------------------

            try {

                if ($this->arangoDb->collectionHandler()->has('ccee')) {

                    $this->arangoDb->documment()->set($date, $resultados);
                    $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('ccee');
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                    // create a new documment
                    $this->arangoDb->documment()->set($date, $resultados);
                    $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());

                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }

            return response()->json([
                'site' => 'https://www.ccee.org.br/preco_adm/precos/historico/semanal/',
                'responsabilidade' => 'Realizar a capitura semanal das informações na tabela Html',
                'status' => 'Crawler Ccee semanal realizado com sucesso!'
            ]);
        }else{

            return response()->json([
                'site' => 'https://www.ccee.org.br/preco_adm/precos/historico/semanal/',
                'responsabilidade' => 'Realizar a capitura semanal das informações na tabela Html',
                'status' => 'O crawler não encontrou o arquivo especificado!'
            ]);
        }
    }
    public function getInfoMercadoGeralAndIndividual()
    {

        set_time_limit(1000);

        $carbon = Carbon::now();
        $date = $carbon->format('Y-m-d');

        $url_base = 'https://www.ccee.org.br/';
        $url_base_1 = 'https://www.ccee.org.br/portal/js/informacoes_mercado.js?_=1524754496465';
        $url_base_2 = 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/oquefazemos/infos/abas_infomercado.jspx';

        $this->client->request('GET', $url_base_1,array('allow_redirects' => true));
        $crawler = $this->client->request('POST', $url_base_2,array('allow_redirects' => true,'aba' => 'aba_info_mercado_mensal'));


        $cookieJar = $this->client->getCookieJar();
        $get_response_site = $this->client->getResponse();
        $get_client = $this->client->getClient();

        $downloads = [];

        if($get_response_site->getStatus() == 200) {

            $downloads = [
                'geral' => $url_dowload_geral = $this->regexCceeInfoMercadoGeral->capturaUrlDownloadGeral($crawler->html()),
                'individual' => $url_dowload_individual = $this->regexCceeInfoMercadoGeral->capturaUrlDownloadIndividual($crawler->html()),
            ];

            foreach ($downloads as  $key => $download) {

                $mont_url_download = $url_base . $download;

                $jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookieJar->all(), $url_base);
                $response = $get_client->get($mont_url_download, ['cookies' => $jar, 'allow_redirects' => true]);

                $result_download = Curl::to($mont_url_download)
                    ->setCookieJar('down')
                    ->allowRedirect(true)
                    ->withContentType('application/xlsx')
                    ->download('');

                if($key == 'geral') {
                    $resultado['geral'][$date]['file'] = $this->storageDirectory->saveDirectory('ccee/mensal/'.$key.'/' . $date . '/', 'InfoMercado_Dados_Gerais.xlsx', $result_download);

                    // Importação dos dados da planilha
//                    $sheet = 5; // 003 Consumo; Tabela 001
//                    $startRow = 15;
//                    $takeRows = 86;
//                    $resultado['geral'][$date]['data']['Consumo']['no CG por submercado/semana/patamar']['MWh'] = $this->importExcelCcee->cceeConsCGPatMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['no CG por submercado/semana/patamar']['MWm'] = $this->importExcelCcee->cceeConsCGPatMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 5; // 003 Consumo; Tabela 002
//                    $startRow = 92;
//                    $takeRows = 98;
//                    $resultado['geral'][$date]['data']['Consumo']['no CG por classe de agente']['MWh'] = $this->importExcelCcee->cceeConsCGClMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['no CG por classe de agente']['MWm'] = $this->importExcelCcee->cceeConsCGClMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 5; // 003 Consumo; Tabela 003
//                    $startRow = 104;
//                    $takeRows = 105;
//                    $resultado['geral'][$date]['data']['Consumo']['no CG por ambiente de comercialização']['MWh'] = $this->importExcelCcee->cceeConsCGAmbMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['no CG por ambiente de comercialização']['MWm'] = $this->importExcelCcee->cceeConsCGAmbMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 5; // 003 Consumo; Tabela 004
//                    $startRow = 113;
//                    $takeRows = 127;
//                    $resultado['geral'][$date]['data']['Consumo']['consumidores livres no CG por ramo de atividade']['MWh'] = $this->importExcelCcee->cceeConsLivCGRamoMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['consumidores livres no CG por ramo de atividade']['MWm'] = $this->importExcelCcee->cceeConsLivCGRamoMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 5; // 003 Consumo; Tabela 006
//                    $startRow = 151;
//                    $takeRows = 226;
//                    $resultado['geral'][$date]['data']['Consumo']['no PC por submercado/semana/patamar']['MWh'] = $this->importExcelCcee->cceeConsGerCGMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['no PC por submercado/semana/patamar']['MWm'] = $this->importExcelCcee->cceeConsGerCGMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 5; // 003 Consumo; Tabela 007
//                    $startRow = 232;
//                    $takeRows = 246;
//                    $resultado['geral'][$date]['data']['Consumo']['consumidores livres no PC por ramo de atividade']['MWh'] = $this->importExcelCcee->cceeConsLivPCRamoMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['consumidores livres no PC por ramo de atividade']['MWm'] = $this->importExcelCcee->cceeConsLivPCRamoMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 5; // 003 Consumo; Tabela 008
//                    $startRow = 253;
//                    $takeRows = 263;
//                    $resultado['geral'][$date]['data']['Consumo']['autoprodutores no PC por ramo de atividade']['MWh'] = $this->importExcelCcee->cceeConsAutoProdPCRamoMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Consumo']['autoprodutores no PC por ramo de atividade']['MWm'] = $this->importExcelCcee->cceeConsAutoProdPCRamoMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 3; // 001 Geração; Tabela 001
//                    $startRow = 15;
//                    $takeRows = 27;
//                    $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por fonte']['MWh'] = $this->importExcelCcee->cceeGerCGFontMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por fonte']['MWm'] = $this->importExcelCcee->cceeGerCGFontMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 3; // 001 Geração; Tabela 007
//                    $startRow = 111;
//                    $takeRows = 187;
//                    $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por submercado/semana/patamar']['MWh'] = $this->importExcelCcee->cceeGerCGPatMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Geração']['histórico de geração no CG por submercado/semana/patamar']['MWm'] = $this->importExcelCcee->cceeGerCGPatMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 27; // Demais Dados; Tabela 001
//                    $startRow = 15;
//                    $takeRows = 21;
//                    $resultado['geral'][$date]['data']['número de agentes participantes da contabilização por classe'] = $this->importExcelCcee->cceeNumAgClasse(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 7; // 005 Contratos; Tabela 001
//                    $startRow = 15;
//                    $takeRows = 21;
//                    $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por tipo']['MWh'] = $this->importExcelCcee->cceeMontCGTipoMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por tipo']['MWm'] = $this->importExcelCcee->cceeMontCGTipoMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 7; // 005 Contratos; Tabela 003
//                    $startRow = 58;
//                    $takeRows = 106;
//                    $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por classe do comprador e do vendedor']['MWm'] = $this->importExcelCcee->cceeMontCGClasseCompVendMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $resultado['geral'][$date]['data']['Dados de Contrato']['montates no CG por classe do comprador e do vendedor']['MWh'] = $this->importExcelCcee->cceeMontCGClasseCompVendMWm(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 24; // 022 Incentivadas; Tabela 003
//                    $startRow = 29;
//                    $takeRows = 46;
//                    $resultado['geral'][$date]['data']['Incentivadas']['montante de contratos de compra']['MWm'] = $this->importExcelCcee->cceeIncentContrCompMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $startRow,
//                        $takeRows,
//                        $carbon
//                    );
//                    $sheet = 10; // 008 Encargos; Tabela 001, 009
//                    $resultado['geral'][$date]['data']['ESS']['R$'] = $this->importExcelCcee->cceeEss(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $carbon
//                    );
//                    $sheet = 10; // 008 Encargos; Tabela 001, 009
//                    $resultado['geral'][$date]['data']['ESS']['R$/MWh'] = $this->importExcelCcee->cceeEssPorMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $carbon
//                    );
//                    $sheet = 25; // 023 Reserva; Tabela 007, 008
//                    $resultado['geral'][$date]['data']['EER']['R$'] = $this->importExcelCcee->cceeEer(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $carbon
//                    );
//                    $sheet = 25; // 023 Reserva; Tabela 007, 008
//                    $resultado['geral'][$date]['data']['EER']['R$/MWh'] = $this->importExcelCcee->cceeEerPorMWh(
//                        storage_path('app') . '/' . $resultado['geral'][$date]['file'][0],
//                        $sheet,
//                        $carbon
//                    );


                }else{
                    $resultado['individual'][$date] = $this->storageDirectory->saveDirectory('ccee/mensal/'.$key.'/' . $date . '/', 'InfoMercado_Dados_Individuais.xlsx', $result_download);

                    // Importação dos dados da planilha
                    $sheet = 3; // 002 Usinas
                    $resultado['individual'][$date]['data'] = $this->importExcelCcee->cceeUsinas(
                        storage_path('app') . '/' . $resultado['individual'][$date][0],
                        $sheet,
                        $carbon
                    );

                }
            }

            try {
                if ($this->arangoDb->collectionHandler()->has('ccee')) {

                    $this->arangoDb->documment()->set('geral', $resultado['geral']);
                    $this->arangoDb->documment()->set('individual', $resultado['individual']);
                    $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('ccee');
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());

                    $this->arangoDb->documment()->set('geral', $resultado['geral']);
                    $this->arangoDb->documment()->set('individual', $resultado['individual']);
                    $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());
                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }

            return response()->json([
                'site' => 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/oquefazemos/infos/abas_infomercado.jspx',
                'responsabilidade' => 'Realizar o download do arquivo info-mercado',
                'status' => 'Crawler Ccee Info-Mercado-Geral e Individual mensal realizado com sucesso!'
            ]);

        }else{
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

        $url_base = 'https://www.ccee.org.br/';
        $url_base_1 = 'https://www.ccee.org.br/portal/faces/acesso_rapido_header_publico_nao_logado/biblioteca_virtual?palavrachave=Conjunto+de+arquivos+para+cálculo';
        $url_base_2 = 'https://www.ccee.org.br/portal/faces/oracle/webcenter/portalapp/pages/publico/bibliotecavirtual/lista_biblioteca_virtual.jspx';

        $this->client->request('GET', $url_base_1,array('allow_redirects' => true));
        $crawler = $this->regexCceeNewaveDecomp->limpaString($this->client->request('POST', $url_base_2,array('allow_redirects' => true,'aba' => 'aba_info_mercado_mensal'))->html());

        $teste = $this->regexCceeNewaveDecomp->findNewave($crawler, $date);

        $downloads = [
            'newave' => $url_base.'ccee/documentos/NW'.$date,
            'decomp' => $url_base.'ccee/documentos/DC'.$date,
        ];

        foreach ($downloads as $key => $download) {
            $resultDownload = Curl::to($download)
                ->setCookieJar('down')
                ->allowRedirect(true)
                ->withContentType('application/zip')
                ->download('');

            if ($key == 'newave') {

                $resultado['newave'][$date_format]['file'] = $this->storageDirectory->saveDirectory('ccee/mensal/' . $date_format . '/', 'newave_' . $date_format . '.zip', $resultDownload);


            } else {

                $resultado['decomp'][$date_format]['file'] = $this->storageDirectory->saveDirectory('ccee/mensal/' . $date_format . '/', 'decomp' . $date_format . '.zip', $resultDownload);


            }
        }

        try {
            if ($this->arangoDb->collectionHandler()->has('ccee')) {

                $this->arangoDb->documment()->set('newave', $resultado['newave']);
                $this->arangoDb->documment()->set('decomp', $resultado['decomp']);
                $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());

            } else {

                // create a new collection
                $this->arangoDb->collection()->setName('ccee');
                $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());

                $this->arangoDb->documment()->set('newave', $resultado['newave']);
                $this->arangoDb->documment()->set('decomp', $resultado['decomp']);
                $this->arangoDb->documentHandler()->save('ccee', $this->arangoDb->documment());
            }
        } catch (ArangoConnectException $e) {
            print 'Connection error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoClientException $e) {
            print 'Client error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoServerException $e) {
            print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
        }

        return response()->json([
            'site' => 'https://www.ccee.org.br/portal/faces/acesso_rapido_header_publico_nao_logado/biblioteca_virtual?palavrachave=Conjunto+de+arquivos+para+cálculo',
            'responsabilidade' => 'Realizar o download dos arquivos Newave e Decomp',
            'status' => 'Crawler Ccee Newave e Decomp mensal realizado com sucesso!'
        ]);




    }



}

