<?php

namespace App\Models\Helpdesk\Ticketit;

use Illuminate\Database\Eloquent\Model;

class AgentRate extends Model
{
    protected $table = 'ticketit_agent_rate';

    /**
     * Indicates that this model should not be timestamped.
     *
     * @var bool
     */
    
    public $timestamps = false;
}
