<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 02/05/18
 * Time: 16:57
 */

namespace Crawler\Regex;


class RegexProtheus extends AbstractRegex
{
    public function getChave($page_acesso)
    {
        $regex = '/\<CHAVE\>(.*?)\</';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getPld($page_acesso)
    {
        $regex = '/\<VALORPLD\>(.*?)\</';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function formataData($page_acesso)
    {
        $regex = '/([0-9])(.)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }
}