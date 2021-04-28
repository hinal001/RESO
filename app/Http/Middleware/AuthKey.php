<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
 	$token = $request->header('APP_KEY');
	if($token == "")
	{
		return response()->json(['message' => 'Token not found'], 401);
	}	
       	return $next($request);
    }
}
