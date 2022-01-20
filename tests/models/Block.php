<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use \RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class Block extends Model {

    use SoftDeletes;
    use CascadedSoftDeletes;

    protected $table = 'blocks';

    protected $fillable = array('page_id', 'name');

    public $timestamps = false;

    public function page() 
    {
        return $this->belongsTo(Page::class);
    }

    public function plugins()
    {
        return $this->hasMany(Plugin::class);
    }

    protected function getCascadedSoftDeletes()
    {
        return ['plugins'];
    }

}



