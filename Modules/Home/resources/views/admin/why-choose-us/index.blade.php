@extends('adminlte::page')

@section('title', 'Why Choose Us')

@section('content_header')
    <h1>Why Choose Us</h1>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="row">
        <div class="col-md-10">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Section Content</h3>
                </div>

                <form action="{{ route('admin.home.why_choose_us.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="small_title">Small Title</label>
                                    <input type="text" name="small_title" id="small_title"
                                        class="form-control @error('small_title') is-invalid @enderror"
                                        value="{{ old('small_title', $whyChooseUs->small_title ?? $defaults['small_title']) }}">
                                    @error('small_title')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Main Title</label>
                                    <input type="text" name="title" id="title"
                                        class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title', $whyChooseUs->title ?? $defaults['title']) }}">
                                    @error('title')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="left_paragraph_1">Left Column - Paragraph 1</label>
                                    <textarea name="left_paragraph_1" id="left_paragraph_1" rows="5"
                                        class="form-control @error('left_paragraph_1') is-invalid @enderror">{{ old('left_paragraph_1', $whyChooseUs->left_paragraph_1 ?? $defaults['left_paragraph_1']) }}</textarea>
                                    @error('left_paragraph_1')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="right_paragraph_1">Right Column - Paragraph 1</label>
                                    <textarea name="right_paragraph_1" id="right_paragraph_1" rows="5"
                                        class="form-control @error('right_paragraph_1') is-invalid @enderror">{{ old('right_paragraph_1', $whyChooseUs->right_paragraph_1 ?? $defaults['right_paragraph_1']) }}</textarea>
                                    @error('right_paragraph_1')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="left_paragraph_2">Left Column - Paragraph 2</label>
                                    <textarea name="left_paragraph_2" id="left_paragraph_2" rows="5"
                                        class="form-control @error('left_paragraph_2') is-invalid @enderror">{{ old('left_paragraph_2', $whyChooseUs->left_paragraph_2 ?? $defaults['left_paragraph_2']) }}</textarea>
                                    @error('left_paragraph_2')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="right_paragraph_2">Right Column - Paragraph 2</label>
                                    <textarea name="right_paragraph_2" id="right_paragraph_2" rows="5"
                                        class="form-control @error('right_paragraph_2') is-invalid @enderror">{{ old('right_paragraph_2', $whyChooseUs->right_paragraph_2 ?? $defaults['right_paragraph_2']) }}</textarea>
                                    @error('right_paragraph_2')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" id="is_active" value="1"
                                    class="form-check-input @error('is_active') is-invalid @enderror" role="switch"
                                    {{ old('is_active', $whyChooseUs->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                                @error('is_active')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop