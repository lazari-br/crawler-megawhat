<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 10/04/18
 * Time: 18:17
 */

namespace Crawler\Regex;


class RegexAneel extends AbstractRegex
{

    public function capturaNorma($page_acesso)
    {
        $regex = '/\>Norma[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['norma']);
    }

    public function capturaMaterial($page_acesso)
    {
        $regex = '/\>Material[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['material']);
    }

    public function capturaDataAssinatura($page_acesso)
    {
        $regex = '/\>Data de assinatura[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Data_assinatura']);
    }

    public function capturaDataPublicacao($page_acesso)
    {
        $regex = '/\>Data de publica...[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Data_publicacao']);
    }

    public function capturaEmenta($page_acesso)
    {
        $regex = '/\>Ementa[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Ementa']);
    }

    public function capturaOrgaoDeOriem($page_acesso)
    {
        $regex = '/\>Órgão de origem[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['orgao_de_origem']);
    }

    public function capturaEsfera($page_acesso)
    {
        $regex = '/\>Esfera[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['esfera']);
    }

    public function capturaSituacao($page_acesso)
    {
        $regex = '/\>Situa.[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['situacao']);
    }

    public function capturaTextoIntegral($page_acesso)
    {
        $regex = '/\>Texto Integral[^>]+>[^>]+>[^>]+>([^<]+)+</';
        $results = $this->regexAll($regex, $page_acesso, 0, ['texto_integral']);

        foreach ($results as $key => $result) {
            $name_arquivo = preg_replace('/http.(.*)\//', ' ', $result);
            $resultados[$key] = [
                'texto_integral' => $result['texto_integral'],
                'name_arquivo' => trim($name_arquivo['texto_integral'])
            ];
        }
        return $resultados;
    }

    public function capturaVoto($page_acesso)
    {
        $regex = '/\>Voto[^>]+>[^>]+>[^>]+>([^<]+)+</';
        $results = $this->regexAll($regex, $page_acesso, 0, ['voto']);

        foreach ($results as $key => $result) {
            $name_arquivo = preg_replace('/http.(.*)\//', ' ', $result);
            $resultados[$key] = [
                'voto' => $result['voto'],
                'name_arquivo' => trim($name_arquivo['voto'])
            ];
        }
        return $resultados;
    }

    public function capturaNotaTecnica($page_acesso)
    {
        $regex = '/\>Nota Técnica.[^>]+>[^>]+>[^>]+>([^<]+)+</';
        $results = $this->regexAll($regex, $page_acesso, 0, ['nota_tecnica']);

        foreach ($results as $key => $result) {
            $name_arquivo = preg_replace('/http.(.*)\//', ' ', $result);
            $resultados[$key] = [
                'nota_tecnica' => $result['nota_tecnica'],
                'name_arquivo' => trim($name_arquivo['nota_tecnica'])
            ];
        }
        return $resultados;
    }

    function tratarImput($dado)
    {
        return str_replace(" ", "+", $dado);
    }

    public function capturaAudiencia($page_acesso)
    {
        $regex = '/href..(.*)"\>/';
        $result = $this->regexFirst($regex, $page_acesso);

        return $this->pregReplaceString('amp;', '', $result);
    }

    public function capturaResultados($page_acesso)
    {
        $regex = '/Resultados[^>]+>[^>]+>[^>]+>[^>]+href..(.*?downloadAnyFile)/';
        $result = $this->regexFirst($regex, $page_acesso);
        return $this->pregReplaceString('amp;', '', $result);
    }

    public function capturaDataDeContribuicao($page_acesso)
    {
        //De 01/11/2017 a 30/11/2017

        $regex = '/periodo-contribuicao-data[^>]+>[^>]+>(.*?[0-9]{4})/';
        $de = $this->formataDataBr($this->regexFirst($regex, $page_acesso));

        $regex = '/periodo-contribuicao-data[^>]+>[^>]+>[^>]+>[^>]+>(.*?[0-9]{4})/';
        $ate = $this->formataDataBr($this->regexFirst($regex, $page_acesso));

        return $de . '_a_' . $ate;

    }

    public function getDataExpansao($page_acesso)
    {
        $regex = '/\>Pequenas Centrais Hidrelétricas...([^>]+)+</';
        return preg_replace('/\&nbsp;/', ' ', $this->regexFirst($regex, $page_acesso, 0, 'Data'));
    }

    public function getPequenasCentrais($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Pequenas Centrais Hidrelétricas'), 0, 'Pequenas Centrais Hidrelétricas');
    }

    public function getEolicas($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Usinas Eólicas'), 0, 'Usinas Eólicas');
    }

    public function getHidreletricas($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Usinas Hidrelétricas'), 0, 'Usinas Hidrelétricas');
    }

    public function getTermeletricas($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Usinas Termelétricas'), 0, 'Usinas Termelétricas');
    }

    public function getBiomassa($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Pequenas Centrais Hidrelétricas'), 0, 'Usinas Termelétricas a Biomassa');
    }

    public function getFotovoltaicas($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Usinas Fotovoltáicas'), 0, 'Usinas Fotovoltáicas');
    }

    public function getResumo($page_acesso)
    {
        $regex = '/\>Relatórios.[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^>]+)+>/';
        $link = '/\"(.*?)\"/';
        return $this->regexFirst($link, $this->regexFirst($regex, $page_acesso, 0, 'Resumo Geral'), 0, 'Resumo Geral');
    }

    public function getTotal($page_acesso)
    {
        $regex = '/Quantidade: &nbsp;.(.*?)&nbsp/';
        return $this->regexFirst($regex, $page_acesso, 0);
    }

    public function getCeg($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>([^<]+)+</';

        $cegs=  $this->regexAll($regex, $page_acesso, 0, ['CEG']);

        foreach ($cegs as $key=>$resultado)
        {
            $resultado = array_values($resultado);
            foreach ($resultado as $chave=>$subCeg)
            {
                $subceg[] = $array[$chave] = $this->regexFirst('/([0-9]{6})-/', $subCeg, 0);
            }
        }

        $resultados[] = '';
        foreach ($cegs as $keys => $conteudo)
        {
            $resultados[$keys] = $cegs[$keys];
            $resultados[$keys]['Sub-CEG'] = $subceg[$keys];
        }
        return $resultados;
    }

    public function getTipo($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Tipo']);
    }

    public function getEmpreendimento($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Nome Empreendimento']);
    }

    public function getPotencia($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Potência']);
    }

    public function getGarantia($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Garantia Física']);
    }

    public function getFonte($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Fonte']);
    }

    public function getRio($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Rio']);
    }

    public function getData($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Data Operação']);
    }

    public function getSituacao($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        return $this->regexAll($regex, $page_acesso, 0, ['Situação Atual']);
    }

    public function getProprietario($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        $conteudo = $this->regexAll($regex, $page_acesso, 0, ['Proprietário']);

        $array = [];
        if ($conteudo)
        {
            foreach ($conteudo as $key => $item) {
                foreach ($item as $chave => $prop) {
                    if ($prop) {
                        $isola = explode('),', $prop);

                        foreach ($isola as $keys => $value)
                        {
                            $array[$key]['Proprietário']['Nome da empresa'] = $this->regexFirst('/(.*?)\(/', $value, 0, 'Nome da empresa');
                            $array[$key]['Proprietário']['CNPJ'] = preg_replace('/(\))/', '', $this->regexFirst('/\((.*)/', $value, 0, 'CNPJ'));
                        }

                    } else {
                        $array[$key]['Proprietário'] = ['Nome da empresa' => '',
                            'CNPJ' => ''];
                    }
                }
            }
        }
        return $array;
    }


    public function getMunicipio($page_acesso)
    {
        $regex = '/\<td align=left nowrap title="[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)+</';
        $conteudo = $this->regexAll($regex, $page_acesso, 0, ['Município']);

        $array = [];
        if ($conteudo)
        {
            foreach ($conteudo as $key => $item) {
                foreach ($item as $chave => $prop) {
                    if ($prop) {
                        $isola = explode('),', $prop);

                        foreach ($isola as $keys => $value)
                        {
                            $array[$key]['Município']['Cidade'] = $this->regexFirst('/(.*?)\(/', $value, 0, 'Cidade');
                            $array[$key]['Município']['Estado'] = preg_replace('/(\))/', '', $this->regexFirst('/\((.*)/', $value, 0, 'Estado'));
                        }

                    } else {
                        $array[$key]['Município'] = ['Cidade' => '',
                            'Estado' => ''];
                    }
                }
            }
        }
        return $array;
    }

}
