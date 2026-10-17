@extends('errors.layout')
@section('code', '419')
@section('title', 'Page expired')
@section('message', 'The form was open too long, so it expired for your security. Go back, reload the page, and try again.')
@section('action')<a href="{{ url()->previous() }}" class="btn">Go back</a>@endsection
