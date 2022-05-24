@extends('layouts.app')

@section('content')
<div class="row row-fluid mx-4">
    <div class="row justify-content-center">
        <dashboard :user-data="{{auth()->user()}}"></dashboard>
    </div>
</div>
@endsection
