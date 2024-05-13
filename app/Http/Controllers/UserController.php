<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserPatientRequest;
use App\Http\Requests\NewUserProfesionalRequest;
use App\Models\Audith;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    public function update(Request $request)
    {
        $id = Auth::user()->id;
        
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

        $message = "Error al actualizar usuario";

        try {
            DB::beginTransaction();
                $user = User::find($id);
                $user->update($request->all());

                Audith::new($id, "Actualización de usuario", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id, "Actualización de usuario", $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function user_plan(Request $request)
    {
        $request->validate([
            'id_plan' => 'required|numeric|exists:App\Models\Plan,id',
        ]);

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al guardar plan";

        try {
            DB::beginTransaction();
                $user = User::find(Auth::user()->id);
                $user->id_plan = $request->id_plan;
                $user->save();

                $user_plan = new UserPlan();
                $user_plan->id_user = $user->id;
                $user_plan->id_plan = $request->id_plan;
                $user_plan->save();
    
                Audith::new(Auth::user()->id, "Asignación de plan a usuario", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Asignación de plan a usuario", $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($user->id);
        $message = "Registro de nuevo plan exitoso";
        return response(compact("message", "data"));
    }

    public function new_user_profesional(NewUserProfesionalRequest $request)
    {
        $message = "Error al crear usuario profesional";
        $data = $request->validated();

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $new_user = new User($request->all());
                $new_user->id_user_type = UserType::PROFESIONAL;
                $new_user->save();

                Audith::new($new_user->id, "Nuevo usuario profesional", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(null, "Nuevo usuario profesional", $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
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
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function profile_picture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|file|max:2048',
        ]);

        $user = Auth::user();
        
        if($user->profile_picture){
            $file_path = public_path($user->profile_picture);
        
            if (file_exists($file_path))
                 unlink($file_path);
        }

        $path = $this->save_image_public_folder($request->profile_picture, "users/profiles/", null);
        
        $user->profile_picture = $path;
        $user->save();

        Audith::new($user->id, "Carga foto de perfil", null, 200, null);

        $message = "Usuario actualizado exitosamente";

        return response(compact("message", "user"));
    }

    public function save_image_public_folder($file, $path_to_save, $variable_id)
    {
        $fileName = Str::random(5) . time() . '.' . $file->extension();
                        
        if($variable_id){
            $file->move(public_path($path_to_save . $variable_id), $fileName);
            $path = "/" . $path_to_save . $variable_id . "/$fileName";
        }else{
            $file->move(public_path($path_to_save), $fileName);
            $path = "/" . $path_to_save . $fileName;
        }

        return $path;
    }

    public function get_professionals()
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener registros";
        $data = null;
        try {
            $data = $this->model::where('id_user_type', UserType::PROFESIONAL)->with($this->model::DATA_WITH)->get();
            Audith::new(Auth::user()->id, "Listado de profesionales", null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new(Auth::user()->id, "Listado de profesionales", null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_user_patient(NewUserPatientRequest $request)
    {
        $message = "Error al crear usuario paciente";
        $data = $request->validated();

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $new_user = new User($request->all());
                $new_user->id_user_type = UserType::PACIENTE;
                $new_user->save();

                Audith::new($new_user->id, "Nuevo usuario paciente", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(null, "Nuevo usuario paciente", $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($new_user->id);
        $message = "Registro de usuario paciente exitoso";
        return response(compact("message", "data"));
    }

    public function update_user_patient(Request $request, $id)
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

        $message = "Error al actualizar usuario paciente";

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $user = User::find($id);
        
        if($user->id_user_type != UserType::PACIENTE)
            return response(["message" => "El usuario seleccionado no es un Paciente"], 400);

        try {
            DB::beginTransaction();

                $user->update($request->all());

                Audith::new($id, "Actualización usuario paciente", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id, "Actualización usuario paciente", $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($id);
        $message = "Usuario actualizado con exitoso";
        return response(compact("message", "data"));
    }

    public function get_patients()
    {
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener registros";
        $data = null;
        try {
            $data = $this->model::where('id_user_type', UserType::PACIENTE)->with($this->model::DATA_WITH)->get();
            Audith::new(Auth::user()->id, "Listado de pacientes", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de pacientes", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
