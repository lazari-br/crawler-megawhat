<?php

namespace Crawler\Model;

use Illuminate\Database\Eloquent\Model;

class Ena extends Model
{
    protected $table = 'ena';

    protected $guarded = 'id';

    protected $fillable = [
        'id',
        'fonte',
        'frequencia',
        'subsistema',
        'ano',
        'mes',
        'dia',
        'inicio',
        'fim',
        'percent_mlt',
        'percent_mlt_armazenavel',
        'mwmed',
        'mwmed_armazenavel',
    ];
}
