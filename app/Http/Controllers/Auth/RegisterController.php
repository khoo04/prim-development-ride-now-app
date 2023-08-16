<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */

    public function AdminRegisterIndex()
    {
        return view('auth.registerAdmin');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'          => ['required', 'min:8', 'confirmed', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[@!$#%^&*()]).*$/'],
            'telno'             => ['required', 'numeric', 'min:10'],
            
        ], [
            'password.regex' => 'Password must contains at least 1 number, 1 uppercase, 1 special character (@!$#%^&*())',
        ]);
    }

    public function registerAdmin(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->createAdmin($request->all())));

        // You can customize this redirect after admin registration
        return redirect(route('home'));
    }
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        //dd($data,isset($data['isAdmin']));
        $user= User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'telno'             => $data['telno'],
            'remember_token'    => $data['_token'],

        ]);

        if (!isset($data['isAdmin'])) {
            $role = DB::table('model_has_roles')->insert([
                'role_id' => 15,
                'model_type' => "App\User",
                'model_id' => $user->id,
            ]);
        }
        
        return $user;

    }
    // to redirect back to intended link even after revalidation
    public function showRegistrationForm(Request $request)
    {
        if (!$request->session()->has('url.intended') && url()->previous() !== url()->current()) {
            $request->session()->put('url.intended', url()->previous());
        }

        return view('auth.register');
    }
    // to redirect back to intended link
    protected function registered(Request $request, $user)
    {
        if ($request->session()->has('url.intended')) {
            $redirectUrl = $request->session()->get('url.intended');
            $request->session()->forget('url.intended');
            return redirect($redirectUrl);
        }
        
        return redirect($this->redirectTo);
    }
}
