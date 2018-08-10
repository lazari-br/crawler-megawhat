<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 04/05/18
 * Time: 10:09
 */

namespace Crawler\Regex;


class RegexCceePldMensal extends AbstractRegex
{

    public function clearHtml($page_acesso)
    {
        return $this->convert_str($page_acesso);
    }
    public function capturaMes($page_acesso)
    {
        $regex = '/class="linebt".[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Mês']);
    }
    public function capturaSeCo($page_acesso)
    {
        $regex = '/class="linebt".[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Sudeste_Centro-Oeste']);
    }
    public function capturaS($page_acesso)
    {
        $regex = '/class="linebt".[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Sul']);
    }
    public function capturaNe($page_acesso)
    {
        $regex = '/class="linebt".[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Nordeste']);
    }
    public function capturaN($page_acesso)
    {
        $regex = '/class="linebt".[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Norte']);
    }
}