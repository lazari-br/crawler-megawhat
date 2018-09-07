<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 02/05/18
 * Time: 16:57
 */

namespace Crawler\Regex;


class RegexOns extends AbstractRegex
{

    /** Metodo getAcervoDigitalPmoSemanal */

    public function capturaRequestDigest($page_acesso)
    {
        $regex = '/__REQUESTDIGEST" value="(.*?)"/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getUrlDownload($page_acesso)
    {
        $regex = '/.FileRef.:.(.*).."FileDirRef/';
        $results = $this->regexFirst($regex, $page_acesso, 0);
        $tratativa = $this->pregReplaceString('u002f','',$results);

        return $this->pregReplaceString('\\','/',$tratativa);
    }

    public function getNameDownload($page_acesso)
    {
        $regex = '/es\/(.*)/';
        return $results = $this->regexFirst($regex, $page_acesso, 0);
    }

    public function testString($page_acesso)
    {
       $result = $this->pregReplaceString('src="','src="https://tableau.ons.org.br',$page_acesso);
      return $results = $this->pregReplaceString('href="','src="https://tableau.ons.org.br',$result);
    }

    public function getWa($page_acesso)
    {
        $regex = '/name="wa".value="(.*?)"/';
        return $results = $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getWresult($page_acesso)
    {
        $regex = '/name="wresult".value=\'(.*?).>/';
        return $results = $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getWctx($page_acesso)
    {
        $regex = '/name="wctx".value="(.*?)"/';
        return $results = $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getAno($page_acesso)
    {
        $regex = '/class="ms-listlink" href="(.*?)"/';
        return $this->regexAll($regex, $page_acesso, 0, ['url']);
    }

    public function validaMes($page_acesso)
    {
        $regex = '/alt="PMO_(...)/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getMes($page_acesso)
    {
        $regex = '/\<a href="(.*?);/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getPmo($page_acesso)
    {
        $regex = '/\<a class="ms-listlink ms-draggable" href="(.*?)"/';
        $results = $this->regexAll($regex, $page_acesso, 0, ['url']);

        foreach ($results as $key => $result) {
            if (stripos($result['url'], '.zip')){
                return $this->pregReplaceString('É', '%C3%89', $this->pregReplaceString(' ', '%20', $result['url']));
            }
        }
    }

    public function getNumEnaImport($page_acesso)
    {
        $regex = '/(.*?)_/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

}