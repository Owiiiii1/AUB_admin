@extends('privacy.layout')

@section('title', __('privacy.page_deletion'))

@section('content')
    <h1>{{ __('privacy.page_deletion') }}</h1>
    <p>{{ $controllerName }} · AUB</p>
    <p>{{ __('privacy.form_intro') }}</p>

    @if (session('deletion_received'))
        <p class="notice">{{ __('privacy.form_received') }}</p>
    @endif

    <form class="card" method="post" action="{{ route('privacy.deletion.store') }}">
        @csrf
        <div class="hp" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <label for="email">{{ __('privacy.form_email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        @error('email')<p class="error">{{ $message }}</p>@enderror

        <label for="role">{{ __('privacy.form_role') }}</label>
        <select id="role" name="role" required>
            @foreach (['student' => 'role_student', 'parent' => 'role_parent', 'teacher' => 'role_teacher', 'other' => 'role_other'] as $value => $key)
                <option value="{{ $value }}" @selected(old('role') === $value)>{{ __('privacy.'.$key) }}</option>
            @endforeach
        </select>
        @error('role')<p class="error">{{ $message }}</p>@enderror

        <label for="message">{{ __('privacy.form_message') }}</label>
        <textarea id="message" name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
        @error('message')<p class="error">{{ $message }}</p>@enderror

        <label class="check"><input type="checkbox" name="confirm" value="1" @checked(old('confirm'))> <span>{{ __('privacy.form_confirm') }}</span></label>
        @error('confirm')<p class="error">{{ $message }}</p>@enderror
        <label class="check"><input type="checkbox" name="privacy" value="1" @checked(old('privacy'))> <span>{{ __('privacy.form_privacy') }}</span></label>
        @error('privacy')<p class="error">{{ $message }}</p>@enderror

        <button type="submit">{{ __('privacy.form_submit') }}</button>
    </form>

    <h2>{{ __('privacy.what_removed') }}</h2>
    <ul>
        <li>{{ __('privacy.removed_login') }}</li>
        <li>{{ __('privacy.removed_chat') }}</li>
        <li>{{ __('privacy.removed_parent') }}</li>
        <li>{{ __('privacy.removed_teacher') }}</li>
        <li>{{ __('privacy.removed_student_photo') }}</li>
    </ul>
    <h2>{{ __('privacy.what_kept') }}</h2>
    <ul>
        <li>{{ __('privacy.kept_records') }}</li>
        <li>{{ __('privacy.kept_logs') }}</li>
    </ul>
    <p>{{ __('privacy.process') }}</p>
    <p><a href="{{ route('privacy.policy') }}">{{ __('privacy.privacy_link') }}</a></p>
@endsection
