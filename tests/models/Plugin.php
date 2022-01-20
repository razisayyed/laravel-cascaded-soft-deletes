<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;

class Plugin extends Model {

    use SoftDeletes;

    protected $table = 'plugins';

    protected $fillable = array('block_id', 'name');

    public $timestamps = false;

    public function block() 
    {
        return $this->belongsTo(Block::class);
    }

}



