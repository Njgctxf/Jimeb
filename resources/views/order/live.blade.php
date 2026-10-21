@extends('layouts.app')

@section('content')

@livewire('order.order-live', ['orderId' => $id])

@endsection
