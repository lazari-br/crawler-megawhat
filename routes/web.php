<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('aneel')->group(function () {
    Route::get('/proinfa', 'AneelController@proInfa')->name('proinfa');
    Route::get('/conta-desenv-energ', 'AneelController@contaDesenvEnerg')->name('conta_desenv_energ');
    Route::get('/cde-audiencia', 'AneelController@cdeAudiencia')->name('cde-audiencia');
    Route::get('/ceg', 'AneelController@cegGeracao')->name('ceg');
    Route::get('/expansao', 'AneelController@expansaoGeracao')->name('expansao');
});

Route::prefix('ons')->group(function () {
    Route::get('/sdro-semanal', 'OnsController@sdroSemanal')->name('sdro_semanal');
    Route::get('/sdro-diario', 'OnsController@sdroDiario')->name('sdro_diario');
    Route::get('/mlt-enas-diario', 'OnsController@operacaoEnasDiario')->name('mlt_enas_diario');
    Route::get('/acervo-digital-ipdo', 'OnsController@getAcervoDigitalIpdoDiario')->name('acervo_digital_ipdo');
    Route::get('/acervo-digital-pmo', 'OnsController@getAcervoDigitalPmoSemanal')->name('acervo_digital_pmo');
    Route::get('/pmo-cdre', 'OnsController@pmoCdre')->name('pmo-cdre');

    //historico
    //enas
    Route::get('/historico-enas-mensal', 'HistoricoOnsController@historico_enas_mensal')->name('historico-enas-mensal');
    Route::get('/historico-enas-diario', 'HistoricoOnsController@historico_enas_diario')->name('historico-enas-diario');
    Route::get('/historico-enas-anual', 'HistoricoOnsController@historico_enas_anual')->name('historico-enas-anual');
    Route::get('/historico-enas-semanal', 'HistoricoOnsController@historico_enas_semanal')->name('historico-enas-semanal');
    //carga
    Route::get('/historico-carga-anual', 'HistoricoOnsController@historico_carga_anual')->name('historico-carga-anual');
    Route::get('/historico-carga-mensal', 'HistoricoOnsController@historico_carga_mensal')->name('historico--carga-mensal');
    Route::get('/historico-carga-diario', 'HistoricoOnsController@historico_carga_diario')->name('historico-carga-diario');
    //intercambio
    Route::get('/historico-intercambio-diario', 'HistoricoOnsController@historico_intercambio_diario')->name('historico-intercambio-diario');
    //cmo
    Route::get('/historico-cmo-semanal', 'HistoricoOnsController@historico_cmo_semanal')->name('historico-cmo-semanal');
    Route::get('/historico-cmo-patamar', 'HistoricoOnsController@historico_cmo_patamar')->name('historico-cmo-patamar');
    //geracao
    Route::get('/historico-geracao-diario', 'HistoricoOnsController@historico_geracao_diario')->name('historico-geracao-diario');
    Route::get('/historico-geracao-mensal', 'HistoricoOnsController@historico_geracao_mensal')->name('historico-geracao-mensal');
    Route::get('/historico-geracao-anual', 'HistoricoOnsController@historico_geracao_anual')->name('historico-geracao-anual');
    //ear
    Route::get('/historico-ear-diario', 'HistoricoOnsController@historico_ear_diario')->name('historico-ear-diario');
    Route::get('/historico-ear-semanal', 'HistoricoOnsController@historico_ear_semanal')->name('historico-ear-semanal');
    Route::get('/historico-ear-mensal', 'HistoricoOnsController@historico_ear_mensal')->name('historico-ear-mensal');
    //pmo-cdre
    Route::get('/historico-cdre-cronograma', 'HistoricoOnsController@historico_cdre_cronograma')->name('historico-cdre-cronograma');
    Route::get('/historico-cdre-memorial', 'HistoricoOnsController@historico_cdre_memorial')->name('historico-cdre-memorial');
    //sdro
    Route::get('/historico-sdro-semanal', 'HistoricoOnsController@historico_sdro_semanal')->name('historico-sdro-semanal');
    Route::get('/historico-sdro-diario', 'HistoricoOnsController@historico_sdro_diario')->name('historico-sdro-diario');
});

Route::prefix('ccee')->group(function () {
    Route::get('/pld-semanal', 'CceeController@historicoPrecoSemanal')->name('pld_semanal');
    Route::get('/info-mercado', 'CceeController@getInfoMercadoGeralAndIndividual')->name('info_mercado');
    Route::get('/pld-mensal', 'CceeController@historicoPrecoMensal')->name('pld_mensal');
    Route::get('/decknewave', 'CceeController@deckNewwave')->name('decomp-neawave');
    Route::get('/leilao', 'CceeController@leiloesConsolidado')->name('leilao');

    //historico
    //pld
    Route::get('/historico-pld-mensal', 'HistoricoCceeController@historico_pld_mensal')->name('historico-pld-mensal');
    Route::get('/historico-pld-semanal', 'HistoricoCceeController@historico_pld_semanal')->name('historico-pld-semanal');
    //infomercado
    Route::get('/historico-infoMercado-geral', 'HistoricoCceeController@historico_infoMercado_geral')->name('historico-infoMercado-geral');
    Route::get('/historico-infoMercado-individual', 'HistoricoCceeController@historico_infoMercado_individual')->name('historico-infoMercado-individual');
});

Route::get('/cde-eletrobras', 'EletroBrasController@getCde')->name('cde-eletrobras');
Route::get('/epe-consumo', 'EpeConsumoController@getConsumo')->name('epe-consumo');
Route::get('/rdh', 'RdhController@enaRdh')->name('rdh_ena');
Route::get('/protheus-pld', 'ProtheusController@pld')->name('pld');

Route::get('/epe-historico', 'EpeConsumoController@historico_epe')->name('epe-historico');
Route::get('/historico-protheus-pld', 'ProtheusController@historico_pld_protheus')->name('historico-pld-protheus');



        use Crawler\StorageDirectory\StorageDirectory;

        Route::get('teste', function (StorageDirectory $storage){

         $date = '2018_09_01_2018_09_07';

         $explode = explode('_', $date);
         $inicio = $explode[2].'/'.$explode[1].'/'.$explode[0];
         $fim = $explode[5].'/'.$explode[4].'/'.$explode[3];

         dd($inicio, $fim);

        });


////////////////////////////////////////////////////////////////////////////////////////////////////TESTE////////////////////////////////////////////////////////////////////////////////////////////////////


