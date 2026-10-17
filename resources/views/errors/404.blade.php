@extends('errors.layout')
@section('code', '404')
@section('title', 'Page not found')
@section('message', 'This page does not exist, or it is not public. A report that was unpublished is no longer available.')
@section('action')<a href="{{ url('/reports') }}" class="btn">Published reports</a>@endsection
