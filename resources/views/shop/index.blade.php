@extends('layouts.guest')

@section('content')

@livewire('shop.cart', ['tableID' => $tableHash ?? null, 'roomID' => (isHotelModuleEnabled($restaurant ?? null) ? ($roomHash ?? null) : null), 'restaurant' => $restaurant ?? null, 'shopBranch' => $shopBranch ?? null , 'getTable' => $getTable ?? false, 'canCreateOrder'=> $canCreateOrder])

@livewire('customer.signup', ['restaurant' => $restaurant])

@endsection
