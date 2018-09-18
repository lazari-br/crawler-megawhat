<?php

namespace Crawler\Model;

use Illuminate\Database\Eloquent\Model;

class Pld extends Model
{
    protected $table = 'pld';

    protected $guarded = 'id';

    protected $fillable = [
        'id',
        'fonte',
        'frequencia',
        'ano',
        'mes',
        'dia',
        'inicio',
        'fim',
        'norte',
        'nordeste',
        'sul',
        'sudeste_centro-oeste',
        'leve',
        'medio',
        'pesado',
    ];
}
