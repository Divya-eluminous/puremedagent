<?php

namespace App\Http\Middleware\Api;

use Closure;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // dump('inside jwt middleware');
            $user = JWTAuth::parseToken()->authenticate();
            // dump("user");
            // dump($user);
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                $message = 'Token is Invalid';
                $errors = [
                      "authenticate" => $message,
                  ];
                return response()->json(['message' => $message,'status'=>false,'errors'=>$errors]);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                 //$message = 'Token is Expired'; //commented on 4-oct-24
                $message = 'Das Token ist abgelaufen.'; //added on 4-oct-24
                $errors = [
                      "authenticate" => $message,
                  ];
                return response()->json(['message' => $message,'status'=>false,'errors'=>$errors]);
            }else{
                $message = 'Authorization Token not found';
                $errors = [
                      "authenticate" => $message,
                  ];
                return response()->json(['message' => $message,'status'=>false,'errors'=>$errors]);
            }
        }
        return $next($request);
    }
}
