<?php
/**
 * Created by PhpStorm.
 * User: leandrolazari
 * Date: 01/06/18
 * Time: 14:18
 */

namespace Crawler\Regex;


class RegexCceeNewaveDecomp extends AbstractRegex
{
    public function limpaString($page_acesso)
    {
        $pattern = ['-', '+', '&'];
        $regex = $this->pregReplaceString($pattern, "", $this->convert_str($page_acesso));
        return $regex;
    }

    public function setInicioDecomp($page_acesso)
    {
        $regex = '/CARGA DOS SUBSISTEMAS(.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setFimDecomp($page_acesso)
    {
        $regex = '/(.*?).BLOCO./';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function selecionaLinha($page_acesso)
    {
        $regex = '/DP.(.*)/';
        return $this->regexAll($regex, $page_acesso, 0, ['teste']);
    }

    public function setInicioNewave($page_acesso)
    {
        $regex = '/MERCADO DE ENERGIA TOTAL  XXX(.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setFimNewave($page_acesso)
    {
        $regex = '/(.*) GERACAO DE USINAS NAO SIMULADAS/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setSeCo($page_acesso)
    {
        $regex = '/ 1 (.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setS($page_acesso)
    {
        $regex = '/ 2 (.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setNe($page_acesso)
    {
        $regex = '/ 3 (.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setN($page_acesso)
    {
        $regex = '/ 4 (.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setAno1($page_acesso)
    {
        $regex = '/2018.(.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function setOutrosAnos($page_acesso)
    {
        $regex = '/2019 (.*)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function findNewave($page_acesso, $date)
    {
        $regex = '/\>Newave 24_L.[^>]+>[^>]+>([^>]+)+>/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function validaDecomp($page_acesso)
    {
        $regex = '/([0-9]{2}.0)/';
        if ($this->regexFirst($regex, $page_acesso, 0)) {
            return 'encontrado';
        }
    }

    public function validaDecompMult($page_acesso)
    {
        $regex = '/([0-9]{3}.0)/';
        if ($this->regexFirst($regex, $page_acesso, 0)) {
            return 'encontrado';
        }
    }
}