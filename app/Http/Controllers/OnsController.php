<?php

namespace Crawler\Http\Controllers;

use Carbon\Carbon;
use Crawler\Regex\RegexOns;
use Crawler\Regex\RegexSdroDiario;
use Crawler\Regex\RegexSdroSemanal;
use Crawler\StorageDirectory\StorageDirectory;
use Crawler\Util\Util;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Goutte\Client;
use Crawler\Regex\RegexMltEnas;
use Crawler\Model\ArangoDb;
use Crawler\Excel\ImportExcelOns;


class OnsController extends Controller
{
    private $regexSdroSemanal;
    private $storageDirectory;
    private $regexSdroDiario;
    private $client;
    private $regexMltEnas;
    private $arangoDb;
    private $regexOns;
    private $importExcelOns;


    public function __construct(RegexSdroSemanal $regexSdroSemanal,
                                StorageDirectory $storageDirectory,
                                Client $client,
                                ArangoDb $arangoDb,
                                RegexMltEnas $regexMltEnas,
                                RegexOns $regexOns,
                                RegexSdroDiario $regexSdroDiario,
                                ImportExcelOns $importExcelOns
)
    {
        $this->regexSdroSemanal = $regexSdroSemanal;
        $this->storageDirectory = $storageDirectory;
        $this->regexSdroDiario = $regexSdroDiario;
        $this->client = $client;
        $this->regexMltEnas = $regexMltEnas;
        $this->arangoDb = $arangoDb;
        $this->regexOns = $regexOns;
        $this->importExcelOns = $importExcelOns;

    }


    public function sdroSemanal()
    {

        $carbon = Carbon::now();
        $date = $carbon->format('Y-m-d');

        $url_base = "http://sdro.ons.org.br/SDRO/semanal/";

        $date_format = Util::getDateIso();


        $response = Curl::to($url_base)
            ->returnResponseObject()
            ->get();

        if ($response->status == 200) {

            $url = $this->regexSdroSemanal->capturaUrlAtual($response->content);
            $response_2 = Curl::to($url_base . $url)
                ->get();

            $data_de_ate = $this->regexSdroSemanal->capturaUrlData($url);
            $url_download_xls = $this->regexSdroSemanal->capturaUrlDownloadExcel($response_2);
            $url_download_xls_name = $this->regexSdroSemanal->capturaUrlDownloadName($url_download_xls);


            $results_download = Curl::to($url_base . $data_de_ate . $url_download_xls)
                ->withContentType('application/xlsx')
                ->download('');
            $url_download[$date_format]['url_download_semanal'] = $this->storageDirectory->saveDirectory('ons/semanal/' . $date_format . '/', $url_download_xls_name, $results_download);

            // Importação dos dados da planilha
            $sheet = 7; // 07-Motivo de Dispacho Térmico
            $startRow = 6;
            $takeRows = 200;
            $resultado[$date_format]['Motivo do dispacho termoelétrico']['MWh'] = $this->importExcelOns->onsMotDispMWh(
                storage_path('app') . '/' . $url_download[$date_format]['url_download_semanal'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 12; // 12-Grandezas Hidroenergéticas
            $resultado[$date_format]['ENA']['MWm'] = $this->importExcelOns->onsEnaSemanalMWm(
                storage_path('app') . '/' . $url_download[$date_format]['url_download_semanal'][0],
                $sheet,
                $carbon
            );
            $sheet = 12; // 12-Grandezas Hidroenergéticas
            $resultado[$date_format]['ENA']['% MLT'] = $this->importExcelOns->onsEnaSemanalPerc(
                storage_path('app') . '/' . $url_download[$date_format]['url_download_semanal'][0],
                $sheet,
                $carbon
            );


            try {
                if ($this->arangoDb->collectionHandler()->has('ons')) {

                    $this->arangoDb->documment()->set('ons_semanal', $url_download);
                    $this->arangoDb->documment()->set('ons_semanal', $resultado);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('ons');
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                    $this->arangoDb->documment()->set('ons_semanal', $url_download);
                    $this->arangoDb->documment()->set('ons_semanal', $resultado);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());
                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }

            return response()->json([
                'site' => 'http://sdro.ons.org.br/SDRO/semanal/',
                'responsabilidade' => 'Realizar download do arquivo semanal Ons',
                'status' => 'Crawler Sdro Semanal realizado com sucesso!'
            ]);
        } else {
            return response()->json([
                'site' => 'http://sdro.ons.org.br/SDRO/semanal/',
                'responsabilidade' => 'Realizar download do arquivo semanal Ons',
                'status' => 'O Crawler não encontrou o arquivo especificado!'
            ]);
        }
    }


    public function sdroDiario()
    {

        $carbon = Carbon::now();

        $url_base = "http://sdro.ons.org.br/SDRO/DIARIO/";

        $date_format = Util::getDateIso();

        $response = Curl::to($url_base)
            ->returnResponseObject()
            ->get();

        if ($response->status == 200) {

            $url = $this->regexSdroDiario->capturaUrlAtual($response->content);
            $response_2 = Curl::to($url_base . $url)
                ->get();

            $url_download_xls = $this->regexSdroDiario->capturaUrlDownloadExcel($response_2);
            $url_download_xls_name = $this->regexSdroDiario->capturaUrlDownloadName($url_download_xls);
            $capitura_name = $this->regexSdroDiario->capturaUrlData($url_download_xls_name);
            $mont_url_dowload = $url_base . $capitura_name . '/' . $url_download_xls;

            $results_download = Curl::to($mont_url_dowload)
                ->withContentType('application/xlsx')
                ->download('');

            $url_download[$date_format]['file'] = $this->storageDirectory->saveDirectory('ons/diaria/' . $date_format . '/', $url_download_xls_name, $results_download);
            // ------------------------------------------------------------------------Crud--------------------------------------------------------------------------------------------------

              // Importação dos dados das planilhas
            $sheet = 8; // 08-Produção Hidráulica
            $startRow = 4;
            $takeRows = 9;
            $url_download[$date_format]['data']['diário']['Produção']['Hidráulica']['GWh'] = $this->importExcelOns->onsProdHidGWh(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 8; // 08-Produção Hidráulica
            $startRow = 12;
            $takeRows = 17;
            $url_download[$date_format]['data']['diário']['Produção']['Hidráulica'] ['MWmed'] = $this->importExcelOns->onsProdHidMWm(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 8; // 08-Produção Hidráulica
            $startRow = 23;
            $takeRows = 300;
            $url_download[$date_format]['data']['diário']['Produção'] ['Hidráulica'] ['por Usina'] = $this->importExcelOns->onsProdHidUsina(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 9; // 09-Produção Térmica
            $startRow = 4;
            $takeRows = 8;
            $url_download[$date_format]['data']['diário']['Produção']['Térmica']['GWh'] = $this->importExcelOns->onsProdTerGWh(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 9; // 09-Produção Térmica
            $startRow = 11;
            $takeRows = 15;
            $url_download[$date_format]['data']['diário']['Produção']['Térmica'] ['MWmed'] = $this->importExcelOns->onsProdTerMWm(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 9; // 09-Produção Térmica
            $startRow = 21;
            $takeRows = 300;
            $url_download[$date_format]['data']['diário']['Produção'] ['Térmica'] ['por Usina'] = $this->importExcelOns->onsProdTerUsina(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 10; // 10-Produção Eólica
            $startRow = 4;
            $takeRows = 8;
            $url_download[$date_format]['data']['diário']['Produção']['Eólica']['GWh'] = $this->importExcelOns->onsProdEolGWh(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 10; // 10-Produção Eólica
            $startRow = 11;
            $takeRows = 15;
            $url_download[$date_format]['data']['diário']['Produção']['Eólica'] ['MWmed'] = $this->importExcelOns->onsProdEolMWm(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 10; // 10-Produção Eólica
            $startRow = 21;
            $takeRows = 300;
            $url_download[$date_format]['data']['diário']['Produção'] ['Eólica'] ['por Usina'] = $this->importExcelOns->onsProdEolUsina(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 11; // 11-Produção Solar
            $startRow = 4;
            $takeRows = 8;
            $url_download[$date_format]['data']['diário']['Produção']['Solar']['GWh'] = $this->importExcelOns->onsProdSolGWh(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 11; // 11-Produção Solar
            $startRow = 11;
            $takeRows = 15;
            $url_download[$date_format]['data']['diário']['Produção']['Solar'] ['MWmed'] = $this->importExcelOns->onsProdSolMWm(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 11; // 11-Produção Solar
            $startRow = 21;
            $takeRows = 300;
            $url_download[$date_format]['data']['diário']['Produção'] ['Solar'] ['por Usina'] = $this->importExcelOns->onsProdSolUsina(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 21; // 21-Energia Natural Afluente
            $startRow = 4;
            $takeRows = 7;
            $url_download[$date_format]['data']['diário']['ENA'] = $this->importExcelOns->onsEna(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 21; // 21-Energia Natural Afluente
            $startRow = 4;
            $takeRows = 7;
            $url_download[$date_format]['data']['diário']['ENA'] = $this->importExcelOns->onsEnaTotal(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 15; // 15-Carga Diária por Subsistema
            $startRow = 6;
            $takeRows = 10;
            $url_download[$date_format]['data']['diário']['Carga']['GWh'] = $this->importExcelOns->onsCargaGWh(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 15; // 15-Carga Diária por Subsistema
            $startRow = 13;
            $takeRows = 17;
            $url_download[$date_format]['data']['diário']['Carga']['MWmed'] = $this->importExcelOns->onsCargaMWm(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 20; // 20-Variação Energia Armazenada
            $startRow = 5;
            $takeRows = 7;
            $url_download[$date_format]['data']['diário']['EAR (MWmês)'] = $this->importExcelOns->onsEar(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );
            $sheet = 20; // 20-Variação Energia Armazenada
            $startRow = 5;
            $takeRows = 7;
            $url_download[$date_format]['data']['diário']['EAR (MWmês)'] = $this->importExcelOns->onsEarTotal(
                storage_path('app') . '/' . $url_download[$date_format]['file'][0],
                $sheet,
                $startRow,
                $takeRows,
                $carbon
            );




            try {
                if ($this->arangoDb->collectionHandler()->has('ons')) {

                    $this->arangoDb->documment()->set('ons_boletim_diario', $url_download);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('ons');
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                    $this->arangoDb->documment()->set('ons_boletim_diario', $url_download);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());
                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }
            return response()->json([
                'site' => 'http://sdro.ons.org.br/SDRO/DIARIO/',
                'responsabilidade' => 'Realizar download do arquivo diario Ons',
                'status' => 'Crawler Sdro Diario realizado com sucesso!'
            ]);
        } else {
            return response()->json([
                'site' => 'http://sdro.ons.org.br/SDRO/DIARIO/',
                'responsabilidade' => 'Realizar download do arquivo diario Ons',
                'status' => 'O Crawler não encontrou o arquivo especificado!'
            ]);
        }
    }

    public function operacaoEnasDiario()
    {

        $url_base = "https://agentes.ons.org.br/";

        $date_format = Util::getDateIso();

        $crawler = $this->client->request('GET', 'https://pops.ons.org.br/ons.pop.federation/?ReturnUrl=https%3a%2f%2fagentes.ons.org.br%2foperacao%2fenas_subsistemas.aspx', array('allow_redirects' => true));
        $get_response_site = $this->client->getResponse();

        if ($get_response_site->getStatus() == 200) {

            $form = $crawler->selectButton('Entrar')->form();
            $this->client->submit($form, array('username' => 'victor.shinohara', 'password' => 'comerc@12345'));
            $this->client->getCookieJar();

            $response = $this->client->request('GET', 'https://agentes.ons.org.br/operacao/enas_subsistemas.aspx');

            $results = $this->regexMltEnas->capturaDowloadMltEnas($response->html());
            $captura_name = $this->regexMltEnas->capturaNameArquivo($results);

            $url_dowload = $url_base . $results;

            $results_download = Curl::to($url_dowload)
                ->withContentType('application/xlsx')
                ->download('');

            $url_download[$date_format]['url_download_mlt_semanal'] = $this->storageDirectory->saveDirectory('ons/mlt/diario/' . $date_format . '/', $captura_name, $results_download);
            // ------------------------------------------------------------------------Crud--------------------------------------------------------------------------------------------------

            try {
                if ($this->arangoDb->collectionHandler()->has('ons')) {

                    $this->arangoDb->documment()->set('ons_enas_diario', $url_download);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('ons');
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                    $this->arangoDb->documment()->set('ons_enas_diario', $url_download);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());
                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }
            return response()->json([
                'site' => 'https://agentes.ons.org.br/',
                'responsabilidade' => 'Realizar download do arquivo enas diario',
                'status' => 'Crawler Enas Diario realizado com sucesso!'
            ]);
        } else {

            return response()->json([
                'site' => 'https://agentes.ons.org.br/',
                'responsabilidade' => 'Realizar download do arquivo enas diario',
                'status' => 'O Crawler não encontrou o arquivo especificado!'
            ]);
        }
    }

    public function getAcervoDigitalIpdoDiario()
    {

        $date_format = Util::getDateBrSubDays('br', 1);
        $ext = '.pdf';

        $crawler = $this->client->request('GET', 'http://ons.org.br/_layouts/download.aspx?SourceUrl=http://ons.org.br/AcervoDigitalDocumentosEPublicacoes/IPDO-' . $date_format . $ext);
        $cookieJar = $this->client->getCookieJar();
        $this->client->getClient();
        \GuzzleHttp\Cookie\CookieJar::fromArray($cookieJar->all(), 'http://ons.org.br/');

        $results_download = Curl::to($crawler->getBaseHref())
            ->withContentType('application/pdf')
            ->download('');
        $url_download[$date_format]['url_download_ipdo_diario'] = $this->storageDirectory->saveDirectory('ons/ipdo/' . $date_format . '/', 'IPDO-' . $date_format . $ext, $results_download);

        // ------------------------------------------------------------------------Crud--------------------------------------------------------------------------------------------------

        try {
            if ($this->arangoDb->collectionHandler()->has('ons')) {

                $this->arangoDb->documment()->set('ons_ipdo', $url_download);
                $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());

            } else {

                // create a new collection
                $this->arangoDb->collection()->setName('ons');
                $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                $this->arangoDb->documment()->set('ons_ipdo', $url_download);
                $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());
            }
        } catch (ArangoConnectException $e) {
            print 'Connection error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoClientException $e) {
            print 'Client error: ' . $e->getMessage() . PHP_EOL;
        } catch (ArangoServerException $e) {
            print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
        }

        return response()->json([
            'site' => 'http://ons.org.br/',
            'responsabilidade' => 'Realizar download do arquivo IPDO(informativo preliminar diário operacional).',
            'status' => 'Crawler IPDO realizado com sucesso!'
        ]);
    }

    public function getAcervoDigitalPmoSemanal(\Crawler\Curl\Curl $curl)
    {

        $date_format = Util::getDateIso();

        $data_raw = '<Request xmlns="http://schemas.microsoft.com/sharepoint/clientquery/2009" SchemaVersion="15.0.0.0" LibraryVersion="16.0.0.0" ApplicationName="Javascript Library"><Actions><Query Id="28" ObjectPathId="11"><Query SelectAllProperties="true"><Properties /></Query><ChildItemQuery SelectAllProperties="true"><Properties /></ChildItemQuery></Query><Query Id="30" ObjectPathId="15"><Query SelectAllProperties="true"><Properties /></Query></Query></Actions><ObjectPaths><Method Id="11" ParentId="8" Name="GetItems"><Parameters><Parameter TypeId="{3d248d7b-fc86-40a3-aa97-02a75d69fb8a}"><Property Name="DatesInUtc" Type="Boolean">true</Property><Property Name="FolderServerRelativeUrl" Type="Null" /><Property Name="ListItemCollectionPosition" Type="Null" /><Property Name="ViewXml" Type="String">&lt;View Scope=\'Recursive\'&gt;   &lt;Query&gt;&lt;Where&gt;&lt;Eq&gt;   &lt;FieldRef Name=\'Categoria\' /&gt;   &lt;Value Type=\'Choice\'&gt;Relatório PMO&lt;/Value&gt;&lt;/Eq&gt;&lt;/Where&gt;       &lt;OrderBy&gt;           &lt;FieldRef Name=\'Data\' Ascending=\'desc\' /&gt;       &lt;/OrderBy&gt;   &lt;/Query&gt;   &lt;RowLimit&gt;10&lt;/RowLimit&gt;&lt;/View&gt;</Property></Parameter></Parameters></Method><Method Id="15" ParentId="13" Name="GetByInternalNameOrTitle"><Parameters><Parameter Type="String">Categoria</Parameter></Parameters></Method><Method Id="8" ParentId="6" Name="GetByTitle"><Parameters><Parameter Type="String">Acervo Digital - Documentos e Publicações</Parameter></Parameters></Method><Property Id="13" ParentId="8" Name="Fields" /><Property Id="6" ParentId="4" Name="Lists" /><Property Id="4" ParentId="2" Name="RootWeb" /><Property Id="2" ParentId="0" Name="Site" /><StaticProperty Id="0" TypeId="{3747adcd-a3c3-41b9-bfab-4a64dd2f1e0a}" Name="Current" /></ObjectPaths></Request>';

        $url_base = 'http://ons.org.br';

        $page = $curl->exeCurl(
            [
                CURLOPT_URL => $url_base . "/pt/paginas/conhecimento/acervo-digital/documentos-e-publicacoes?categoria=Relat%C3%B3rio+PMO",
            ]

        );
dd($page);
        if($curl->statuspageCurl() == 200) {

            $get_disgest = $this->regexOns->capturaRequestDigest($page);
dd($get_disgest);
            $headers = ['X-Requested-With: XMLHttpRequest', 'Content-Type: text/xml', 'X-RequestDigest: ' . $get_disgest];
            $result = $curl->exeCurl(
                [
                    CURLOPT_URL => $url_base . "/_vti_bin/client.svc/ProcessQuery",
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS => $data_raw
                ]

            );

            $results = explode('_ObjectType_":"SP.ListItem', $result);

            foreach ($results as $result) {
                $get_url_download = $this->regexOns->getUrlDownload($result);
            }

            $get_name_download = $this->regexOns->getNameDownload($get_url_download);
            $mont_url = $url_base . $get_url_download;

            $results_download = $curl->exeCurl(array(
                    CURLOPT_URL => $mont_url
                )
            );

            $url_download[$date_format]['url_download_pmo_semanal'] = $this->storageDirectory->saveDirectory('ons/pmo/' . $date_format . '/', $get_name_download, $results_download);

            // ------------------------------------------------------------------------Crud--------------------------------------------------------------------------------------------------

            try {
                if ($this->arangoDb->collectionHandler()->has('ons')) {

                    $this->arangoDb->documment()->set('ons_pmo_semanal', $url_download);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());

                } else {

                    // create a new collection
                    $this->arangoDb->collection()->setName('ons');
                    $this->arangoDb->collectionHandler()->create($this->arangoDb->collection());
                    $this->arangoDb->documment()->set('ons_pmo_semanal', $url_download);
                    $this->arangoDb->documentHandler()->save('ons', $this->arangoDb->documment());
                }
            } catch (ArangoConnectException $e) {
                print 'Connection error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoClientException $e) {
                print 'Client error: ' . $e->getMessage() . PHP_EOL;
            } catch (ArangoServerException $e) {
                print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
            }
            return response()->json([
                'site' => 'https://agentes.ons.org.br/',
                'responsabilidade' => 'Realizar download do arquivo enas diario',
                'status' => 'Crawler Pmo Semanal Realizado com sucesso!'
            ]);
        }else{
            return response()->json(['site' => 'https://agentes.ons.org.br/',
                'responsabilidade' => 'Realizar download do arquivo pmo semanal',
                'status' => 'O Crawler não encontrou o arquivo especificado!']);
        }
    }


    public function pmoCdre(\Crawler\Curl\Curl $curl)
    {
        set_time_limit(1000);


//        $date_format = Util::getDateIso();
//
//        $data_raw = 'wa=wsignin1.0&wtrealm=+https%3a%2f%2fcdre.ons.org.br%2f_trust%2f&wctx=https%3a%2f%2fcdre.ons.org.br%2f_layouts%2f15%2fAuthenticate.aspx%3fSource%3d%252F&wreply=https%3a%2f%2fcdre.ons.org.br%2f_trust%2fdefault.aspx';
//
////        $url_base = 'http://ons.org.br';
//
//        $page = $curl->exeCurl(
//            [
//                CURLOPT_URL => "https://pops.ons.org.br/ons.pop.federation/?",
//            ]
//
//        );
//
//
//dd($page);
//
//
//
//        $crawler = $this->client->request('GET', 'https://pops.ons.org.br/ons.pop.federation/?', array('allow_redirects' => true));
//            $form = $crawler->selectButton('Entrar')->form();
//            $this->client->submit($form, array('username' => 'victor.shinohara', 'password' => 'comerc@12345'));
//            $this->client->getCookieJar();
//
//        $response = $this->client->request('GET', 'https://pops.ons.org.br/ons.pop.federation/redirect/?wa=wsignin1.0&wtrealm=+https%3a%2f%2fcdre.ons.org.br%2f_trust%2f&wctx=https%3a%2f%2fcdre.ons.org.br%2f_layouts%2f15%2fAuthenticate.aspx%3fSource%3d%252F&wreply=https%3a%2f%2fcdre.ons.org.br%2f_trust%2fdefault.aspx/', array('allow_redirects' => true));
//
//        $response3 = $this->client->request('POST', 'https://cdre.ons.org.br/_trust/', array('allow_redirects' => true));
//
//        $request = $this->client->getInternalResponse()->getHeader('SPRequestGuid');
//        $teste = $this->client->request('GET', 'https://cdre.ons.org.br/', array('allow_redirects'=> true, 'headers' => ['SPRequestGuid'=> $this->client->getInternalResponse()->getHeader('SPRequestGuid')]));
//
//dd($this->client->getResponse());

        // Importação dos dados da planilha
        //CRONOGRAMA
//        $sheet = 0; // UFV
//        $resultado ['Cronograma']['UFV'] = $this->importExcelOns->pmoUsina(
//            storage_path('app') .'/Cronograma_Reuniao_DMSE_jun18.xlsx',
//            $sheet
//        );
//        $sheet = 1; // UEE
//        $resultado ['Cronograma']['UEE'] = $this->importExcelOns->pmoUsina(
//            storage_path('app') .'/Cronograma_Reuniao_DMSE_jun18.xlsx',
//            $sheet
//        );
//        $sheet = 2; // BIO
//        $resultado ['Cronograma']['BIO'] = $this->importExcelOns->pmoUsinaComb(
//            storage_path('app') .'/Cronograma_Reuniao_DMSE_jun18.xlsx',
//            $sheet
//        );
//        $sheet = 3; // UTE
//        $resultado ['Cronograma']['UTE'] = $this->importExcelOns->pmoUsinaComb(
//            storage_path('app') .'/Cronograma_Reuniao_DMSE_jun18.xlsx',
//            $sheet
//        );
//        $sheet = 4; // PCH
//        $resultado ['Cronograma']['PCH'] = $this->importExcelOns->pmoUsina(
//            storage_path('app') .'/Cronograma_Reuniao_DMSE_jun18.xlsx',
//            $sheet
//        );
//        $sheet = 5; // UHE
//        $resultado ['Cronograma']['UHE'] = $this->importExcelOns->pmoUsina(
//            storage_path('app') .'/Cronograma_Reuniao_DMSE_jun18.xlsx',
//            $sheet
//        );

        //NÃO SIMULADAS
        $sheet = 2; // Existentes_CCEE
        $resultado ['Não Simulada']['Existentes'] = $this->importExcelOns->pmoNaoSimuladasExistente(
            storage_path('app') .'/Mem¢ria de C lculo das Usinas NÆo Simuladas Individualmente - PMO jun-18.xlsx',
            $sheet
        );
        $sheet = 3; // Expansão (440-2011 e 476-2012)
        $resultado ['Não Simulada']['Expansão'] = $this->importExcelOns->pmoNaoSimuladasExpansao(
            storage_path('app') .'/Mem¢ria de C lculo das Usinas NÆo Simuladas Individualmente - PMO jun-18.xlsx',
            $sheet
        );


dd($resultado);

        }

}
