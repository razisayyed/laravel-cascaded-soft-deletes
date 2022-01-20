<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class PageCallbackCascade extends Model {

    use SoftDeletes;
    use CascadedSoftDeletes;

    protected $table = 'pages';

    protected $fillable = array('name');

    public $timestamps = false;

    public function blocks()
    {
        return $this->hasMany(Block::class, 'page_id', 'id');
    }

    protected function getCascadedSoftDeletes()
    {
        return [
            'blocks' => function() {
                return Block::where('page_id', 2);
            }
        ];
    }


}


