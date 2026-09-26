@extends('adminlte::page')

@section('title', 'Message #'.$message->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Message #{{ $message->id }}</h1>
        <a href="{{ route('admin.contact.messages.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Messages
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $message->subject }}</h3>
                    <div class="card-tools">
                        <span class="badge {{ $message->status_color }}">{{ $message->status_label }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <p class="text-muted">
                        Submitted on {{ $message->created_at->format('M d, Y H:i') }}
                        @if ($message->read_at)
                            &middot; Read on {{ $message->read_at->format('M d, Y H:i') }}
                        @endif
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <strong>Name</strong>
                            <p class="mb-0">{{ $message->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Email</strong>
                            <p class="mb-0">
                                <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone</strong>
                            <p class="mb-0">{{ $message->phone ?: '&mdash;' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Subject</strong>
                            <p class="mb-0">{{ $message->subject }}</p>
                        </div>
                    </div>

                    <hr>

                    <h5 class="text-muted">Message</h5>
                    <p style="white-space: pre-wrap;">{{ $message->message }}</p>

                    @if ($message->admin_note)
                        <hr>
                        <h5 class="text-muted">Admin Note</h5>
                        <p style="white-space: pre-wrap;">{{ $message->admin_note }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Update Status</h3>
                </div>

                <form action="{{ route('admin.contact.messages.status', $message) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status"
                                class="form-control @error('status') is-invalid @enderror">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}"
                                        {{ old('status', $message->status) === $status ? 'selected' : '' }}>
                                        {{ \Modules\Contact\Models\ContactMessage::STATUS_LABELS[$status] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="admin_note">Admin Note (internal)</label>
                            <textarea name="admin_note" id="admin_note" rows="4"
                                class="form-control @error('admin_note') is-invalid @enderror"
                                placeholder="Optional notes visible only in the admin panel.">{{ old('admin_note', $message->admin_note) }}</textarea>
                            @error('admin_note')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check me-1"></i> Save Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop