<?php

namespace App\Http\Controllers;

class OrderMoneyRecalculationController extends Controller
{
    public function index()
    {
        abort_if((! user_can('Show Restaurant')), 403);

        return view('superadmin.order-money-recalculation');
    }
}
