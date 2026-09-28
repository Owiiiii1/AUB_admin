@extends('privacy.layout')

@section('title', __('privacy.page_privacy'))

@section('content')
    <h1>{{ __('privacy.page_privacy') }}</h1>
    <p>{{ $controllerName }} · AUB</p>
    @if ($controllerAddress)
        <p>{{ $controllerAddress }}</p>
    @else
        <p>{{ __('privacy.address_missing') }}</p>
    @endif
    @if ($contactEmail)
        <p>{{ __('privacy.contact_email', ['email' => $contactEmail]) }}</p>
    @else
        <p>{{ __('privacy.email_missing') }}</p>
    @endif
    <p><a href="{{ route('privacy.deletion') }}">{{ __('privacy.deletion_link') }}</a></p>

    @foreach (__('privacy.sections') as $section)
        <h2>{{ $section['heading'] }}</h2>
        @foreach ($section['body'] as $paragraph)
            <p>{{ str_replace([':name', ':deletion'], [$controllerName, route('privacy.deletion')], $paragraph) }}</p>
        @endforeach
    @endforeach
@endsection
