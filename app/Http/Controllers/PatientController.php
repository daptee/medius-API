<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserPatientRequest;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\PatientFile;
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

class PatientController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    public function new_user_patient(NewUserPatientRequest $request)
    {
        $message = "Error al crear usuario paciente";
        $data = $request->validated();

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $new_user = new User($request->all());
                $new_user->password = Str::random(10);
                $new_user->id_user_type = UserType::PACIENTE;
                $new_user->save();

                Audith::new($new_user->id, "Nuevo usuario paciente", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(null, "Nuevo usuario paciente", $request->all(), 500, $e->getMessage());
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
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
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
            // $data = $this->model::where('id_user_type', UserType::PACIENTE)->with($this->model::DATA_WITH)->get();
            $data = $this->model::with(['status'])
                    ->select(['id', 'name', 'last_name','dni', 'email', 'id_user_status', 'data', 'created_at'])
                    ->where('id_user_type', UserType::PACIENTE)
                    ->get();
            Audith::new(Auth::user()->id, "Listado de pacientes", null, 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Listado de pacientes", null, 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function patient_files(Request $request)
    {
        $request->validate([
            'id_user' => 'required',
            'patient_files' => 'required|array',
        ]);

        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $user = User::find($request->id_user);
        
        if($user->id_user_type != UserType::PACIENTE)
            return response(["message" => "El usuario seleccionado no es un Paciente"], 400);

        if(count($request->patient_files) == 0)
            return response(["message" => "Array 'patient_files' vacio"], 400);

        try {
            DB::beginTransaction();
            foreach ($request->patient_files as $patient_file) {
                $file_name = $patient_file->getClientOriginalName();
                $patient_file->move(public_path("patients/{$user->id}/files"), $file_name);
    
                $patient_file = new PatientFile();
                $patient_file->id_patient = $user->id;
                $patient_file->file_name = $file_name;
                $patient_file->save();
            }
    
            Audith::new($user->id, "Carga de archivos a paciente", null, 200, null);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Audith::new($user->id, "Carga de archivos a paciente", null, 500, $e->getMessage());
            return response(["message" => "Error al cargar archivos: " . $e->getMessage()], 500);
        }

        $data = User::getAllDataUser($user->id);
        $message = "Carga de archivos exitoso";
        return response(compact("message", "data"));
    }  
    
    public function delete_patient_files(Request $request)
    {
        $request->validate([
            'ids_files' => 'required|array',
            'id_user' => 'required'
        ]);
        
        $user_id = $request->id_user;

        try {
            DB::beginTransaction();

            foreach ($request->ids_files as $id_file) {
                $patient_file = PatientFile::find($id_file);
                if($patient_file){

                    if($patient_file->id_patient == $user_id){
                        $file_path = public_path("patients/$patient_file->id_patient/files/$patient_file->file_name");
                        
                        if (file_exists($file_path)){
                            $patient_file->delete();
                            unlink($file_path);
                        }

                        // En caso de quedarse sin archivos borrar carpeta en public/patients ?
                    }
                }
            }
            Audith::new($user_id, "Eliminación de archivos de paciente", null, 200, null);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Audith::new($user_id, "Eliminación de archivos de paciente", null, 500, $e->getMessage());
            return response(["message" => "Error al eliminar archivos: " . $e->getMessage()], 500);
        }

        $data = User::getAllDataUser($user_id);
        $message = "Eliminación de archivos exitoso";
        return response(compact("message", "data"));
    }
}
