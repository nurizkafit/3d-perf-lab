@extends('errors.layout')
@section('code', '403')
@section('title', 'Not allowed')
@section('message', 'Your account cannot open this page. If you think it should, ask an administrator.')
@section('action')<a href="{{ url()->previous() }}" class="btn">Go back</a>@endsection
