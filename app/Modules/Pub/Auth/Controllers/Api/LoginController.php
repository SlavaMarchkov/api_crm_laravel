<?php

namespace App\Modules\Pub\Auth\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{

	public function login( Request $request )
	{
		var_dump($request->all());
		exit('q');
	}

}
