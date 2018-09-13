<?php

namespace Crawler\Util;


use Carbon\Carbon;


class UtilProtheus extends Util
{
    public function curlProtheus($url, $headers, $raw)
    {
        $curl = new \Crawler\Curl\Curl;
        return $curl->exeCurl(
            [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $raw
            ]);
    }

    public function setDateProtheus()
    {
        $carbon = new Carbon();
        $diaSemana = (float)$carbon->format('N');
        $diff = 0;
//        $atraso = ;

        if ($diaSemana === '5'){
            $diff = $diaSemana;
        } elseif ($diaSemana > 5) {
            $diff = 7 - $diaSemana;
        } else {
            $diff = 2 + $diaSemana;
        }

        $date = $carbon->subDays($diff);
dd($date);
        return $date;
    }

    public function setInicioProtheus()
    {
        return $this->setDateProtheus()->subDays(7)->format('Ymd');
    }

    public function setFimProtheus()
    {
        return $this->setDateProtheus()->format('Ymd');
    }

}