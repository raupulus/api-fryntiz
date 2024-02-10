<?php

namespace App\Http\Middleware;

use App\Models\Platform;
use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Illuminate\Http\Request;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array
     */
    public function hosts(): array
    {
        return array_merge([
            $this->allSubdomainsOfApplicationUrl(),
        ], Platform::getAllDomains());
    }
}
