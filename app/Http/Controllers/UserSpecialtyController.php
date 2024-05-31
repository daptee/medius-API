<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Specialty;
use App\Models\SpecialtyUser;
use App\Models\SpecialtyUserStatus;
use App\Models\UserType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserSpecialtyController extends Controller
{
    public function get_specialties()
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener listado de especiales asociadas al usuario";
        $data = null;
        try {
            $data = SpecialtyUser::with(['specialty', 'status'])->where('id_user', Auth::user()->id)->get();

            Audith::new(Auth::user()->id, "Listado de especiales asociadas al usuario", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de especiales asociadas al usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_specialty_user(Request $request)
    {
        $request->validate([
            "id_specialty"=> 'required',
            "color"=> 'required',
            "shift_duration"=> 'required',
        ]);

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al cargar especialidad a usuario";
        $data = null;
        try {
            $specialty_user = new SpecialtyUser($request->all());
            $specialty_user->id_user = Auth::user()->id; 
            $specialty_user->id_status = SpecialtyUserStatus::ACTIVO; 
            $specialty_user->save(); 

            Audith::new(Auth::user()->id, "Cargar de especialidad a usuario", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Cargar de especialidad a usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = $specialty_user;

        return response(compact("data"));
    }

    public function update_specialty_user(Request $request, $id)
    {
        $request->validate([
            "color"=> 'required',
            "shift_duration"=> 'required',
        ]);

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al actualizar especialidad de usuario";
        $data = null;
        try {
            $specialty_user = SpecialtyUser::find($id);
            $specialty_user->update($request->all());

            Audith::new(Auth::user()->id, "Actualización de especialidad de usuario", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Actualización de especialidad de usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = $specialty_user;

        return response(compact("data"));
    }

    public function delete_specialty_user($id)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al eliminar especialidad de usuario";
        $data = null;
        try {
            $specialty_user = SpecialtyUser::find($id);
            $specialty_user->delete();

            Audith::new(Auth::user()->id, "Eliminación de especialidad de usuario", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Eliminación de especialidad de usuario", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = "Especialidad eliminada con exito";

        return response(compact("message"));
    }
}
