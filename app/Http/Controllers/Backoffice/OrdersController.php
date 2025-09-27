<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class OrdersController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(): View
    {
        return view('backoffice.orders.index');
    }
}
