<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style>
		body {
			margin: 0;
			padding: 0;
			min-width: 100% !important;
		}
	</style>
</head>

<body bgcolor="#ffffff" style="min-width: 100% !important; margin: 0; padding: 0; font-family: Roboto,RobotoDraft,Helvetica,Arial,sans-serif;">
	
	<table width="100%" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<table class="content" align="center" cellpadding="0" cellspacing="0" border="0"
					style="width: 100%; max-width: 800px; margin-top: 50px;">
					<tr style="text-align: center">
						<td><img style="width: 50%;" src="{{ env("APP_URL") }}/img/logo.svg"/></td>
					</tr>
					<tr>
						<td>
							<hr />
						</td>
					</tr>
					<tr>
						<td>
							<table width="100%" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0"
								style="margin-top: 80px; text-align:center;">
								<tr>
									<td>
										<!-- Over due section -->
										@if ($type === "overdue_email")
											@yield('overdue-email')

										@elseif ($type === "archive")
											<!-- Archive -->
											@yield('archive')

										@elseif ($type === "packing_slip")
											<!-- Packing Slip -->
											@yield('packing-slip')

										@elseif ($type === "user_invitation")
											<!-- User Invitation -->
											@yield("user-invitation")

										@elseif ($type === "new_code")
											<!-- New Code-->
											@yield("new-code")

										@elseif ($type === "qc")
											<!-- QC -->
											@yield('qc')

										@elseif ($type === "timeout")
											<!-- Time Out -->
											@yield('email-timeout')

										@else
											<p>Unknown type of email</p>

										@endif
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table width="100%" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0"
								style="margin-top: 150px; text-align:center;">
								<tr>
									<td>
										<hr />
										<p style="margin-top: 50px;"><a style="font-weight: bold; size: 16px; text-decoration: none;" href="{{env('UX_URL')}}" target="blank">C O A S T A L </a></p>
									</td>
								</tr>
							</table> 
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

</body>
</html>
