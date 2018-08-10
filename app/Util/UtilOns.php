<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 01/08/18
 * Time: 10:34
 */

namespace Crawler\Util;

use Crawler\Services\DuskService;
use Laravel\Dusk\Browser;
use Crawler\Regex\RegexOns;


class UtilOns extends Util
{
    private $duskService;
    private $regexOns;

    public function __construct(DuskService $duskService,
                                RegexOns $regexOns)
    {
        $this->duskService = $duskService;
        $this->regexOns = $regexOns;
    }

    public function acessoCdre()
    {
        $url_base = 'https://cdre.ons.org.br';

        $browser = new Browser($this->duskService->remoteDriver());
        $browser->maximize()
                ->visit('https://cdre.ons.org.br/default.aspx')
                ->pause(3000)
                ->type('username','victor.shinohara')
                ->type('password','comerc@12345')
                ->click('input[name="submit.Signin"]')
                ->visit('https://cdre.ons.org.br/PMO/Forms/AllItems.aspx?RootFolder=%2FPMO%2FDOCUMENTOS%20M%C3%89DIO%20PRAZO')
                ->pause(2500);
        $page_inicial = $browser->driver->getPageSource();
        $anos = $this->regexOns->getAno($page_inicial);

        foreach ($anos as $ano) {
            $anoAtual = array_values($ano);
        }

        $browser->visit($url_base . $anoAtual[0])
                ->pause(2500);

        $pageMeses = $browser->driver->getPageSource();
        $mes = $this->validaMes($pageMeses);

        $browser->visit($url_base . $mes)
                ->pause(2500);

        $crawler = $browser->driver->getPageSource();
        $url = $this->regexOns->getPmo($crawler);
dump($url);
//        $browser->driver->navigate($url_base.$url)
        $browser->visit($url_base . $url)
                ->pause(2500)
                ->dump();
die;
        return $browser->driver->getPageSource();
    }

    public function validaMes($info)
    {
        $array = explode('class="ms-cellstyle ms-vb-title"', $info);
        $mes = $this->mesMMMportugues();

        foreach ($array as $key=>$item) {
            if ($this->regexOns->validaMes($array[$key])) {
                if ($this->regexOns->validaMes($array[$key]) === $mes) {
                    return $this->regexOns->getMes($array[$key]);
                }
            }
        }
    }
}