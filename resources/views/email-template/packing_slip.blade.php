@extends('layouts.email-layout')
@section('packing-slip')
	<b>Dispatched Deal:</b> {{$data['deal_name']}}</p>
	<p>{{$data['content']}}</p>
@endsection

	