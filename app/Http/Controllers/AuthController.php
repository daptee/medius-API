<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\BranchOffice;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";
    
    public function auth_login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        try{
            $user = User::where('email' , $credentials['email'])->get();

            if($user->count() == 0)
                return response()->json(['message' => 'Usuario y/o clave no válidos.'], 400);

            if (! $token = JWTAuth::attempt($credentials))
                return response()->json(['message' => 'Usuario y/o clave no válidos.'], 400);

        }catch (JWTException $e) {
            return response()->json(['message' => 'No fue posible crear el Token de Autenticación '], 500);
        }
    
        // Session::put('applocale', $request);
        return $this->respondWithToken($token, Auth::user()->id);
    }

    // public function login_admin(LoginRequest $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ]);

    //     // User admin 
    //     $user_to_validate = User::where('email', $request->email)->first();
        
    //     if(!isset($user_to_validate) || $user_to_validate->user_type_id != UserType::ADMIN)
    //         return response()->json(['message' => 'Email y/o clave no válidos.'], 400);
        
    //     $credentials = $request->only('email', 'password');

    //     if (! $token = JWTAuth::attempt($credentials))
    //         return response()->json(['message' => 'Email y/o clave no válidos.'], 400);

    //     return $this->respondWithToken($token, Auth::user()->id);
    // }

    public function auth_register(RegisterRequest $request)
    {
        $message = "Error al crear {$this->s} en registro";
        $data = $request->validated();

        try {
            DB::beginTransaction();
                $new_user = new $this->model($data['user']);
                $new_user->save();

                $new_company = new Company($data['company']);
                $new_company->id_user = $new_user->id;
                $new_company->save();

                $new_branch_office = new BranchOffice($data['branch_office']);
                $new_branch_office->id_user = $new_user->id;

                // CHEQUAR CON SEBA: COUNTRY & LOCALITY ID / PROVINCE
                $new_branch_office->id_country = Country::where('name', $data['branch_office']['country'])->first()->id ?? null;
                $new_branch_office->save();
            DB::commit();
        } catch (\Exception $error) {
            DB::rollBack();
            return response(["message" => $message, "error" => $error->getMessage(), "line" => $error->getLine()], 500);
        }

        $data = $this->model::getAllDataUser($new_user->id);
        $message = "Registro de {$this->s} exitoso";
        return response(compact("message", "data"));
    }

    public function logout(){
        try{
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Logout exitoso.']);
        }catch (JWTException $e) {

            return response()->json(['message' => $e->getMessage()])->setstatusCode(500);
        }catch(Exception $e) {

            return response()->json(['message' => $e->getMessage()])->setstatusCode(500);
        }
    }

    protected function respondWithToken($token,$id){
        $expire_in = config('jwt.ttl');
        $data = [ 'user' => User::getAllDataUser($id) ];

        return response()->json([
            'message' => 'Login exitoso.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expire_in * 60,
            'data' => $data
        ]);
    }

}
