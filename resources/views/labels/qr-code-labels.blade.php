<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml">

<head>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style>
		body {
			margin: 0;
			padding: 0;
			min-width: 100% !important;
		}
		.card {
			margin: 15rem;
			padding: 0;
		}
		.row.no-gutter {
			margin-left: 0;
			margin-right: 0;
		}

		.row.no-gutter > div[class*="col-"] {
			padding-left: 0;
			padding-right: 0;
		}

		.float-child {
			width: 50%;
			float: left;
		}  
		.column {
			float: left;
			width: 50%;
			margin-left: 20px;
		}

		/* Clear floats after the columns */
		.row:after {
		content: "";
		display: table;
		clear: both;
		}

	</style>
</head>
<body bgcolor="#ffffff" style="min-width: 100% !important; margin: 0; padding: 0; font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">

	<!-- <div class="card" style="width: 222.9px;"> -->
	<div class="card" style="width: 500px; height:550px">
		<div class="card-body">
			<center><img src="./img/coastal-logo.png" alt="Logo" height="80" width="160" style="display: block; margin-left: auto; margin-right: auto;"></center>
			<hr />
			<span style="font-size: 12px;">To:</span> <br>
			<h4>{{ $company_name }}</h4>
			<hr />
			<div class="row">
				<div class="column">
					<p style="font-size: 12px;">Purchase Order</p>
					<p>{{ $po_number }}</p>
				</div>
				<div class="column">
					<p style="font-size: 12px;">Invoice Number</p>
					<p>{{ $inv_number}}</p>
				</div>
			</div>
			<hr />
			<div>
				<div class="float-child">
					<span style="font-size: 12px;">Colour</span><br>
					{{ $colour }}
					<hr />
					<span style="font-size: 12px;">Payment Terms: {{ $payment_terms }}</span>
					@if($client_on_hold)
						<h6>ACCOUNT ON HOLD</h6><br>
					@endif
				</div>
				<div class="float-child">
					<img src="data:image/png;base64, {{$qr_code}} ">
				</div>
			</div>
		</div>
	</div>
</body>
</html>