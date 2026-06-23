<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Gives every controller $this->authorize() and authorizeResource() so
    // policies (e.g. ProjectPolicy) can be enforced from the transport layer.
    use AuthorizesRequests;
}
