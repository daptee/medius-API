<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserProfesionalRequest;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProfessionalController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";
    
    public function new_user_profesional(NewUserProfesionalRequest $request)
    {
        $message = "Error al crear usuario profesional";
        $data = $request->validated();

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $new_user = new User($request->all());
                $new_user->password = Str::random(10);
                $new_user->id_user_type = UserType::PROFESIONAL;
                $new_user->save();

                Audith::new($new_user->id, "Nuevo usuario profesional", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(null, "Nuevo usuario profesional", $request->all(), 500, $e->getMessage());
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

        $data = User::getAllDataUser($new_user->id);
        $message = "Registro de usuario profesional exitoso";
        return response(compact("message", "data"));
    }

    public function update_user_profesional(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id),
            ],
            'dni' => [
                'required',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'required|string|min:8',
            'phone' => 'required',
            'data' => 'required',
        ]);

        $message = "Error al actualizar usuario profesional";

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $user = User::find($id);
                $user->update($request->all());

                Audith::new($id, "Actualización usuario profesional", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id, "Actualización usuario profesional", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function get_professionals()
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener registros";
        $data = null;
        try {
            $data = $this->model::with(['status'])
                    ->select(['id', 'name', 'last_name','dni', 'email', 'id_user_status', 'data', 'created_at'])
                    ->where('id_user_type', UserType::PROFESIONAL)
                    ->get();
            // $data = $this->model::where('id_user_type', UserType::PROFESIONAL)->with($this->model::DATA_WITH)->get();
            Audith::new(Auth::user()->id, "Listado de profesionales", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new(Auth::user()->id, "Listado de profesionales", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
