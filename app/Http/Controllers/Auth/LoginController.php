<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function index()
    {
        \Session::put('isFirstTime', 0);
        return view('login');
    }

    public function loginAction( Request $request )
    {
        $email = $request->input('email'); 
        $password = $request->input('password');

        $userdata = array(
            'email'     => $email,
            'password'  => $password
        ); 

        if (Auth::attempt($userdata)) 
        #if( $email = 'admin' && $password == 'admin' )
        {
            $response = array( 'success' => 1, 'message' => 'Login Successfully.' );
            echo json_encode($response);
            exit;
        }

        $response = array( 'success' => 0, 'message' => 'Login Failed!!' );
        echo json_encode($response);
        exit;
    }
}
