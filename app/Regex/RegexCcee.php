<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 10/04/18
 * Time: 18:17
 */

namespace Crawler\Regex;


class RegexCcee extends AbstractRegex
{

    public  function getUrlLeilao($page_acesso)
    {
        $regex = '/\>Resultado.Consolidado\<[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function  getSuprimento($page_acesso)
    {
        $regex = '/[^-]+-[^-]+-([^-]+)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

}