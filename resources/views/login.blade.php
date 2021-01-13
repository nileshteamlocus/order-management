@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="main-panel">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row w-100">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left p-5" style="text-align: center !important;">
                <div class="brand-logo">
                  <img src="{{asset('images/logo_128x80.png')}}" alt="logo">
                </div>
                <h4>Hello! let's get started</h4>
                <h6 class="font-weight-light">Sign in to continue.</h6>
                <div class="pt-3">
                  <div class="form-group">
                    <input type="email" class="form-control form-control-lg" id="exampleInputEmail1" placeholder="Username">
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" id="exampleInputPassword1" placeholder="Password">
                  </div>
                  <div class="mt-3">
                    <button class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn" id="login">SIGN IN</button>
                  </div>
                  <div class="my-2 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                      <label class="form-check-label text-muted">
                        <input type="checkbox" class="form-check-input">
                        Keep me signed in
                      </label>
                    </div>
                    <a href="#" class="auth-link text-black">Forgot password?</a>
                  </div>  
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>


<style type="text/css">
    .loader-demo-box
    {
        position: fixed;
        top: 30%;
        border: none !important; 
        height: 100%;
        width: 100%;
        top: 0;
        background: beige;
        opacity: 0.7;
    }
    .date-to-div
    {
        margin-left: 20px;
    }
    .input-error
    {
        border-color: #fc7242 !important;
    }
</style>

<div class="loader-demo-box" style="visibility: hidden;">
 <div class="jumping-dots-loader">
  <span></span>
  <span></span>
  <span></span>
</div>
</div>

<script type="text/javascript">

    var wage = document.getElementById("exampleInputEmail1");
    wage.addEventListener("keydown", function (e) {
        if (e.keyCode === 13) {  //checks whether the pressed key is "Enter"
            $( "#login" ).trigger('click');
        }
    });

    var wage = document.getElementById("exampleInputPassword1");
    wage.addEventListener("keydown", function (e) {
        if (e.keyCode === 13) {  //checks whether the pressed key is "Enter"
            $( "#login" ).trigger('click');
        }
    });
    
    $( "#login" ).click(function() {

        var formData = {
            email: $('#exampleInputEmail1').val(),
            password: $('#exampleInputPassword1').val(),
            _token: "{{ csrf_token() }}", 
        }
        $.ajax({
            type: "POST",
            url: "/loginAction",
            data: formData,
            dataType:'json',
            beforeSend: function() {
                    $(".loader-demo-box").css('visibility', 'visible');
                },
            success: function (response) { 
                $(".loader-demo-box").css('visibility', 'hidden');
                if(response.success)
                {
                    window.location = 'neworder';
                }
                else 
                {
                    showDangerToast(response.message);
                }
            },
        });

    });
</script>

@endsection