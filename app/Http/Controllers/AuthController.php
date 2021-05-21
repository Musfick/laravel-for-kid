<?php


namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;
use Seshac\Otp\Otp;
use Spatie\Permission\Traits\HasRoles;

class AuthController extends Controller
{
    
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'secret', 'verify']]);
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Login success',
                'data' => [
                    'token' => $token,
                ],
            ], 200);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        if(Auth::user()->hasRole('Admin')){
            return response()->json([
                'user' => $this->guard()->user(),
                'hasRole' => true
            ]);
        }
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
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
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function verify(Request $request){
        $identifier = (string)$request->input('mobile');
        $token = (string)$request->input('token');

        $verify = Otp::setAllowedAttempts(10)->validate($identifier, $token);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Otp Verify',
            'otp' => $verify,
        ], 200);
    }

    public function secret(Request $request){
        $identifier = (string)$request->input('mobile');
        $otp =  Otp::setValidity(1)->setLength(4)->generate($identifier);
        return response()->json([
            'isSuccess' => true,
            'message' => 'Otp Generated',
            'otp' => $otp,
        ], 200);
    }

    public function register(Request $request) {
        //Create role
        //$role = Role::create(['name' => 'Admin']);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            return response()->json([
                'isSuccess' => false,
                'message' => 'User regisration failed',
                'error' => $validator->errors(),
            ], 400);
        }

        $user = User::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password)]
                ));
        
        //Assign role
        $user->assignRole('Admin');

        return response()->json([
            'isSuccess' => false,
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
