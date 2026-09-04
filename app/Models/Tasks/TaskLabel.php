<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;

class TaskLabel extends Model
{
    protected $table = 'label_task';
    public $timestamps = true;
    protected $fillable = ['task_id', 'label_id'];

    public function label() {
	    return $this->belongsTo('App\Models\Tasks\Label');
    }
}