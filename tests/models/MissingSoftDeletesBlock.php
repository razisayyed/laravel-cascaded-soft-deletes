<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use \RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class MissingSoftDeletesBlock extends Model {

    use CascadedSoftDeletes;

    protected $table = 'blocks';

    protected $fillable = array('page_id', 'name');

    public $timestamps = false;

    public function page() 
    {
        return $this->belongsTo(MissingSoftDeletesPage::class, 'page_id', 'id');
    }

    public function plugins()
    {
        return $this->hasMany(Plugin::class, 'block_id', 'id');
    }

    protected function getCascadedSoftDeletes()
    {
        return ['plugins'];
    }

}



