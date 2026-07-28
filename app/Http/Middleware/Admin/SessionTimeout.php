<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Session\Store;
//Trait
use App\Traits\GeneralTrait;
class SessionTimeout {

    protected $session;
    protected $timeout = 2700;//45 minutes
    
    use GeneralTrait;
    public function __construct(Store $session){
        $this->session = $session;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //dd(time() - $this->session->get('lastActivityTime'),$this->timeout);
        $isLoggedIn = $request->path() != 'admin/logout';
        if(!session('lastActivityTime'))
            $this->session->put('lastActivityTime', time());
        elseif(time() - $this->session->get('lastActivityTime') > $this->timeout){
            // dd(time() - $this->session->get('lastActivityTime'),$this->timeout);
            $this->session->forget('lastActivityTime');
            $cookie = cookie('intend', $isLoggedIn ? url()->current() : 'dashboard');
            $email = $request->user()->email;

            auth()->logout();
            self::_setCompanyId('');
            return redirect('/admin/login')->with('message','You had no activity in '.$this->timeout/60 .' minutes ago.');;
           // return message('You had not activity in '.$this->timeout/60 .' minutes ago.', 'warning', 'login')->withInput(compact('email'))->withCookie($cookie);
        }
        $isLoggedIn ? $this->session->put('lastActivityTime', time()) : $this->session->forget('lastActivityTime');
        return $next($request);
    }

}