@extends('adminlte::page')

@section('title', 'My Payments')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>My Payments</h1>
        <a href="{{ route('user.dashboard') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="row mb-3 g-3">
        <div class="col-sm-4">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $recordCount }}</h3>
                    <p>Payments recorded</p>
                </div>
                <div class="icon"><i class="fa-solid fa-coins"></i></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $pendingCount }}</h3>
                    <p>Pending verification</p>
                </div>
                <div class="icon"><i class="fa-regular fa-clock"></i></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $currency }} {{ number_format($verifiedTotal, 2) }}</h3>
                    <p>Verified amount</p>
                </div>
                <div class="icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payment history</h3>
        </div>
        <div class="card-body">
            <x-adminlte-datatable id="payments-table" :heads="[
                'Date',
                'Payment',
                'Booking',
                'Service',
                'Method',
                'Type',
                'Reference',
                'Amount',
                'Status',
                ['label' => 'Actions', 'no-export' => true],
            ]" :config="[
                'order' => [[0, 'desc']],
                'pageLength' => 10,
                'lengthMenu' => [[5, 10, 25, 50], [5, 10, 25, 50]],
                'autoWidth' => false,
                'layout' => ['topStart' => 'pageLength', 'topEnd' => 'search', 'bottomStart' => 'info', 'bottomEnd' => 'paging'],
                'columnDefs' => [['orderable' => false, 'searchable' => false, 'targets' => 9]],
                'language' => ['search' => 'Search:', 'lengthMenu' => 'Show _MENU_ entries', 'info' => 'Showing _START_ to _END_ of _TOTAL_ entries', 'emptyTable' => 'No payments found.'],
            ]" hoverable compressed>
                @forelse ($payments as $payment)
                    <tr>
                        <td data-order="{{ $payment->created_at->timestamp }}">
                            {{ $payment->created_at->format('M d, Y') }}<br>
                            <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                        </td>
                        <td>#{{ $payment->id }}</td>
                        <td>
                            <a href="{{ route('bookings.show', $payment->booking->booking_reference) }}"
                                class="text-decoration-none">
                                {{ $payment->booking->booking_reference }}
                            </a>
                        </td>
                        <td>
                            <strong>{{ $payment->booking->service_title }}</strong><br>
                            <small class="text-muted">{{ $payment->booking->booking_type_label }}</small>
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $payment->method ?? 'manual')) }}</td>
                        <td><span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span></td>
                        <td>{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</td>
                        <td>{{ $payment->amount_display }}</td>
                        <td>
                            <span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            You don't have any payments yet.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>
@stop