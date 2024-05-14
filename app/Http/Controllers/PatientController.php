<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\PatientFile;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
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
