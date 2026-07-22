@extends('documents.layouts.document')

@section('content')
    @include('documents.budgets.partials.header')

    <br>

    @include('documents.budgets.partials.items-table', [
        'items' => $viewModel->items(),
        'totals' => $viewModel->totals(),
    ])

    <br>

    {{-- @include('documents.budgets.partials.totals', ['totals' => $viewModel->totals()]) --}}

    @include('documents.budgets.partials.shipping', [
        'carrier' => $viewModel->carrier(),
        'totals' => $viewModel->totals(),
    ])

    <br>

    @include('documents.budgets.partials.footer', ['notes' => $viewModel->notes()])
@endsection
