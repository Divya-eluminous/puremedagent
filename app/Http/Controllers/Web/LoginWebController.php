<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PatientsModel;
use Session;

class LoginWebController extends Controller
{
    public function __construct(
       
        PatientsModel $PatientsModel
    )
    {
        $this->BaseModel = $PatientsModel;   
    }

    

    /*---------------------------------
    |   LOGIN
    ------------------------------------------*/

        public function login()
        {
            return view('web.appointment.login');
        }

        public function checkLogin(Request $request)
        {

            // dd($request->all());
            // input data
			$this->validate($request, [
            	'email' => 'required',
            	'password' => 'required',
        	]);

            $this->JsonData['status'] = 'error';
            $this->JsonData['url'] = url('/login');
            $this->JsonData['msg'] = 'Incorrect login details';
           
            $email           = $request['email'];
            $password        = $request['password'];
            $strPasswordEncd    = base64_encode($request['password']);

            // admin credentials
            $userCredentials['email']=$email;
            $userCredentials['password']=$password;
            // $userCredentials['status']=1;

            if (auth()->attempt($userCredentials)) 
            {  
                $this->JsonData['url'] = url('/online-appointments');
                $this->JsonData['status'] = 'success';
                $this->JsonData['msg'] = 'You have successfully logged in...Please wait.';
            }

            return response()->json($this->JsonData);
		}   

        public function logout()
        {
            auth()->logout();
            return redirect('/');
        }
}
