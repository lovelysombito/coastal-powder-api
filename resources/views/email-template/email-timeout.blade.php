@extends('layouts.email-layout')
@section('email-timeout')
	<div>
		<p style="margin: 0;" style="text-align: center; font-family: 'Calibri';"> 
			<a style="font-weight: bold; font-family: 'Calibri';"></a>{{ $data['message'] }}</p>
	</div>
@endsection