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

});

Route::prefix('ccee')->group(function () {
    Route::get('/historico-semanal', 'CceeController@historicoPrecoSemanal')->name('historico_semanal');
    Route::get('/info-mercado', 'CceeController@getInfoMercadoGeralAndIndividual')->name('info_mercado');
    Route::get('/historico-mensal', 'CceeController@historicoPrecoMensal')->name('historico_mensal');
    Route::get('/decknewave', 'CceeController@deckNewwave')->name('decomp-neawave');
    Route::get('/leilao', 'CceeController@leiloesConsolidado')->name('leilao');

});


Route::get('/cde-eletrobras', 'EletroBrasController@getCde')->name('cde-eletrobras');
Route::get('/epe-consumo', 'EpeConsumoController@getConsumo')->name('epe-consumo');
Route::get('/rdh', 'RdhController@enaRdh')->name('rdh_ena');


Route::get('/protheus-pld', 'ProtheusController@pld')->name('pld');



Route::get('/treino', 'AAAtreino@api')->name('treino');


////////////////////////////////////////////////////////////////////////////////////////////////////TESTE////////////////////////////////////////////////////////////////////////////////////////////////////


