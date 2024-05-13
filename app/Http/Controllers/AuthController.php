<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\RecoverPasswordMailable;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\BranchOffice;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserType;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    // public function __construct()
    // {
    //     # By default we are using here auth:api middleware
    //     $this->middleware('auth:api', ['except' => ['auth_login']]);
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
    
                Audith::new($new_user->id, "Registro de usuario", $data['user'], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($new_user->id, "Registro de usuario", $data['user'], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }


        if($new_user){
            try {
                Mail::to($new_user->email)->send(new WelcomeUserMailable($new_user));
            } catch (Exception $error) {
                Log::debug(["message" => "Error al enviar mail de bienvenida.", "error" => $error->getMessage(), "line" => $error->getLine()]);
                // Retornamos que no se pudo enviar el mail o no hace falta solo queda en el log?
            }
        }

        $data = $this->model::getAllDataUser($new_user->id);
        $message = "Registro de {$this->s} exitoso";
        return response(compact("message", "data"));
    }

    public function auth_login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        try{
            $user = User::where('email' , $credentials['email'])->first();

            if(!$user)
                return response()->json(['message' => 'Usuario y/o clave no válidos.'], 400);

            if (! $token = auth()->attempt($credentials)) {
                return response()->json(['message' => 'Usuario y/o clave no válidos.'], 401);
            }

            Audith::new($user->id, "Login de usuario", $credentials['email'], 200, null);

        }catch (Exception $e) {
            Audith::new($user->id, "Login de usuario", $credentials['email'], 500, $e->getMessage());
            Log::debug(["message" => "No fue posible crear el Token de Autenticación.", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response()->json(['message' => 'No fue posible crear el Token de Autenticación.'], 500);
        }
    
        return $this->respondWithToken($token);
    }

    public function auth_account_recovery(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        
        $user = User::where('email', $request->email)->first();

        if(!$user)
            return response()->json(['message' => 'El correo ingresado no fue encontrado.'], 400);

        // return new RecoverPasswordMailable($user);
        try {
            Mail::to($user->email)->send(new RecoverPasswordMailable($user));
            Audith::new($user->id, "Recupero de contraseña", $request->email, 200, null);
        } catch (Exception $e) {
            Audith::new($user->id, "Recupero de contraseña", $request->email, 500, $e->getMessage());
            return response(["error" => $e->getMessage()], 500);
        }
        
        return response()->json(['message' => 'Correo enviado con exito.'], 200);
    }

    public function auth_password_recovery(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $decrypted_email = Crypt::decrypt($request->email);
            
            $user = User::where('email', $decrypted_email)->first();

            if(!$user)
                return response()->json(['message' => 'Datos incompletos para procesar el cambio de contraseña.'], 400);

            DB::beginTransaction();
            
                $user->password = $request->password;
                $user->save();
            
                Audith::new($user->id, "Cambio de contraseña", $request->email, 200, null);
            DB::commit();
        } catch (DecryptException $e) {
            DB::rollBack();
            Audith::new($user->id, "Cambio de contraseña", $request->email, 500, $e->getMessage());
            Log::debug(["message" => "Error al realizar el decrypt / actualizar contraseña.", "error" => $e->getMessage(), "line" => $e->getLine()]);
        }

        return response()->json(['message' => 'Contraseña actualizada con exito.'], 200);
    }

    public function auth_password_recovery_token(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required',
        ]);

        try {
            
            $user = User::find(Auth::user()->id);

            if(!Hash::check($request->old_password, $user->password))
                return response()->json(['message' => 'Contraseña anterior incorrecta.'], 400);

            DB::beginTransaction();
            
                $user->password = $request->password;
                $user->save();
            
                Audith::new($user->id, "Cambio de contraseña", $user->email, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($user->id, "Cambio de contraseña", $user->email, 500, $e->getMessage());
            Log::debug(["message" => "Error al actualizar contraseña.", "error" => $e->getMessage(), "line" => $e->getLine()]);
        }

        return response()->json(['message' => 'Contraseña actualizada con exito.'], 200);
    }

    public function logout()
    {
        $email = Auth::user()->email;
        $user_id = Auth::user()->id; 
        try{
            auth()->logout();

            Audith::new($user_id, "Logout", $email, 200, null);
            return response()->json(['message' => 'Logout exitoso.']);
        }catch (Exception $e) {
            Audith::new($user_id, "Logout", $email, 500, $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    protected function respondWithToken($token)
    {
        $data = [ 
            'access_token' => $token,
            'user' => User::getAllDataUser(Auth::user()->id)
        ];

        return response()->json([
            'message' => 'Login exitoso.',
            'data' => $data
        ]);
    }

}
