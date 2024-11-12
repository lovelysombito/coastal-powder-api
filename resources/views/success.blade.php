<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Coastal Powder</title>
</head>
<body>
    @if(!empty($successMsg))
        <div class="container-fluid py-5">
            <div class="d-flex justify-content-center ">
                <div class="col-4">
                    <div class="card shadow rounded" style="border: none">
                        <div class="card-body">
                            <div class="logo-content text-center my-2" style="padding: 40px 0">
                                <img src="{{ asset('img/logo.svg') }}" width="100%" >
                            </div>

                            <h3 style="text-align: center; font-family: 'Calibri'; color:gray">{{$successMsg}}</h3>
                                
                            <footer class="mt-2">
                                <h4 style="text-align: center; font-family: 'Calibri'; color:darkgray"> 
                                    THANK YOU </h4>
                            </footer>
                            
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="alert alert-success center" style="text-align: center"> {{ $successMsg }}</div> --}}
    @endif
    @if(!empty($errorMsg))
        <div class="alert alert-danger center" style="text-align: center"> {{ $errorMsg }}</div>
    @endif

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>
