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
        $regex = '/resultado.consolidado.dos.leilões.\<[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getSuprimento($page_acesso)
    {
        $regex = '/[^-]+-[^-]+-([^-]+)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function get_sub_ceg($page_acesso)
    {
        $regex = '/([0-9]{6})/';
        return $this->regexFirst($regex, $page_acesso);
    }

    public function get_tipo_usina($page_acesso)
    {
        $regex = '/([A-Z]{3})./';
        return $this->regexFirst($regex, $page_acesso);
    }

}