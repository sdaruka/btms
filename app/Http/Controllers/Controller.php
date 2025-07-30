<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController; // Import the base Laravel Controller

abstract class Controller extends BaseController // Extend the imported BaseController
{
    use AuthorizesRequests, ValidatesRequests; // Use these traits for authorization and validation
}
