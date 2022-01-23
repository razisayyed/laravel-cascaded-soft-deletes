<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class Page extends Model {

    use SoftDeletes;
    use CascadedSoftDeletes;

    protected $table = 'pages';
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = array('name');

    public $timestamps = false;

    public function blocks()
    {
        return $this->hasMany(Block::class, 'page_id', 'id');
    }

    protected function getCascadedSoftDeletes()
    {
        return ['blocks'];
    }


}


