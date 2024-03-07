<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Mail\ValidatorEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator; 

class AuthController extends Controller
{
     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api',['except' => ['login','register','activate']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
       
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(["user"=>auth()->user(),"Revisando"=> auth()->user()->rol_id]);
    }

    /**
     * Log the user out (Invalidate the token).
     *  
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out'],201);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL(60)
        ]);
    }


    public function register(Request $request)
    {
        $validate = Validator::make(
            $request->all(),[
                "name"=>"required|max:30",
                "email"=>"required|unique:users|email",
                "password"=>"required|min:8|string"
            ]
            );

            if($validate->fails())
            {
                return response()->json(["msg"=>"Datos incorrectos","data"=>$validate->errors()],422);
            }

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();
            $signedroute = URL::temporarySignedRoute(
                'activate',
                now()->addMinutes(10),
                ['user' => $user->id]
            );
            Mail::to($request->email)->send(new ValidatorEmail($signedroute));
            return response()->json(["msg"=>"Se mando un mensaje a tu correo","data"=>$user],201);
    }

    public function activate(User $user)
    {
        $user->is_active=true;
        $user->save();
        
    }
}
