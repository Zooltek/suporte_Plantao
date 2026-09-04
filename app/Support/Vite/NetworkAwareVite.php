<?php

namespace App\Support\Vite;

use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;

class NetworkAwareVite extends Vite
{
    public function isRunningHot()
    {
        if (! parent::isRunningHot()) {
            return false;
        }

        $request = app()->bound('request') ? app(Request::class) : null;

        return ViteRequestPolicy::shouldUseHotFile($request);
    }
}
