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
    Route::get('/historico-enas-mensal', 'OnsController@historico_enas_mensal')->name('historico-enas-mensal');
    Route::get('/historico-enas-diario', 'OnsController@historico_enas_diario')->name('historico-enas-diario');
    Route::get('/historico-enas-anual', 'OnsController@historico_enas_anual')->name('historico-enas-anual');
    Route::get('/historico-enas-semanal', 'OnsController@historico_enas_semanal')->name('historico-enas-semanal');
    //carga
    Route::get('/historico-carga-anual', 'OnsController@historico_carga_anual')->name('historico-carga-anual');
    Route::get('/historico-carga-mensal', 'OnsController@historico_carga_mensal')->name('historico--carga-mensal');
    Route::get('/historico-carga-semanal', 'OnsController@historico_carga_semanal')->name('historico-carga-semanal');
    Route::get('/historico-carga-diario', 'OnsController@historico_carga_diario')->name('historico-carga-diario');
    //intercambio
    Route::get('/historico-intercambio-diario', 'OnsController@historico_intercambio_diario')->name('historico-intercambio-diario');
    //cmo
    Route::get('/historico-cmo-semanal', 'OnsController@historico_cmo_semanal')->name('historico-cmo-semanal');
    //geracao
    Route::get('/historico-geracao-diario', 'OnsController@historico_geracao_diario')->name('historico-geracao-diario');
    Route::get('/historico-geracao-semanal', 'OnsController@historico_geracao_semanal')->name('historico-geracao-semanal');
    Route::get('/historico-geracao-mensal', 'OnsController@historico_geracao_mensal')->name('historico-geracao-mensal');
    Route::get('/historico-geracao-anual', 'OnsController@historico_geracao_anual')->name('historico-geracao-anual');
    //ear
    Route::get('/historico-ear-diario', 'OnsController@historico_ear_diario')->name('historico-ear-diario');
    Route::get('/historico-ear-semanal', 'OnsController@historico_ear_semanal')->name('historico-ear-semanal');
    Route::get('/historico-ear-mensal', 'OnsController@historico_ear_mensal')->name('historico-ear-mensal');
    //pmo-cdre
    Route::get('/historico-cdre', 'OnsController@historico_cdre')->name('historico-cdre');



});

Route::prefix('ccee')->group(function () {
    Route::get('/historico-semanal', 'CceeController@historicoPrecoSemanal')->name('historico_semanal');
    Route::get('/info-mercado', 'CceeController@getInfoMercadoGeralAndIndividual')->name('info_mercado');
    Route::get('/historico-mensal', 'CceeController@historicoPrecoMensal')->name('historico_mensal');
    Route::get('/decknewave', 'CceeController@deckNewwave')->name('decomp-neawave');
    Route::get('/leilao', 'CceeController@leiloesConsolidado')->name('leilao');

    //historico
    //pld
    Route::get('/historico-pld-mensal', 'CceeController@historico_pld_mensal')->name('historico-pld-mensal');
    Route::get('/historico-pld-semanal', 'CceeController@historico_pld_semanal')->name('historico-pld-semanal');
    //infomercado
    Route::get('/historico-infoMercado-geral', 'CceeController@historico_infoMercado_geral')->name('historico-infoMercado-geral');
    Route::get('/historico-infoMercado-individual', 'CceeController@historico_infoMercado_individual')->name('historico-infoMercado-individual');

});


Route::get('/cde-eletrobras', 'EletroBrasController@getCde')->name('cde-eletrobras');
Route::get('/epe-consumo', 'EpeConsumoController@getConsumo')->name('epe-consumo');
Route::get('/rdh', 'RdhController@enaRdh')->name('rdh_ena');
Route::get('/protheus-pld', 'ProtheusController@pld')->name('pld');

Route::get('/epe-historico', 'EpeConsumoController@historico_epe')->name('epe-historico');

Route::get('teste', function ($date = '5/6/2018'){



       dump(\Carbon\Carbon::now()->format('m y'));


});


////////////////////////////////////////////////////////////////////////////////////////////////////TESTE////////////////////////////////////////////////////////////////////////////////////////////////////


