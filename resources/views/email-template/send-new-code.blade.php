@extends('layouts.email-layout')
@section('new-code')
	<div>
		<p style="margin: 0;" style="text-align: center; font-family: 'Calibri';"> 
			Your code is : <h2 style="font-weight: bold; font-family: 'Calibri'; margin-top: 5px">{{$data['code']}}</h2></p>
	</div>
@endsection