<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\ClinicHistory;
use App\Models\ClinicHistoryFile;
use App\Models\Specialty;
use App\Models\SpecialtyProfessional;
use App\Models\SpecialtyUser;
use App\Models\SpecialtyUserStatus;
use App\Models\User;
use App\Models\UserType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserClinicHistoryController extends Controller
{
    public function get_clinic_history_patient($id)
    {
        // if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            // return response(["message" => "Usuario invalido"], 400);

        $user = User::find($id);

        if(!$user){
            return response(["message" => "ID invalido"], 400);
        }else if($user->id_user_type != UserType::PACIENTE){
            return response(["message" => "Accion invalida"], 400);
        }
    
        $message = "Error al obtener historia clinica de paciente";
        $data = null;
        try {
            $data = ClinicHistory::with(['professional:id,name,last_name,profile_picture', 'professional.specialties.status', 'professional.specialties.specialty'])->where('id_patient', $id)->get();
            
            foreach ($data as $item) {
                $count = ClinicHistoryFile::where("id_clinic_history", $item->id)->count();
                $item['has_files'] = $count > 0 ? true : false;
            }

            Audith::new(Auth::user()->id, "Get historia clinica de paciente", ['id_patient' => $id], 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Get historia clinica de paciente", ['id_patient' => $id], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function get_clinic_history($id)
    {
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al obtener historia clinica";
        $data = null;
        try {
            $data = ClinicHistory::with(['professional:id,name,last_name,email,profile_picture', 'professional.specialties.status', 'professional.specialties.specialty', 'files'])->find($id);

            Audith::new(Auth::user()->id, "Get historia clinica", ['id_patient' => $id], 200, null);
        } catch (Exception $e) {
            Audith::new(Auth::user()->id, "Get historia clinica", ['id_patient' => $id], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    public function new_clinic_history_patient(Request $request)
    {
        $request->validate([
            'id_patient' => 'required|exists:users,id',
            'id_professional' => 'required|exists:users,id',
            'datetime' => 'required',
            'observations' => 'required',
        ]);
        
        if(Auth::user()->id_user_type != UserType::ADMIN && Auth::user()->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "Usuario invalido"], 400);

        $message = "Error al guardar historia clinica de paciente";
        $data = null;
        try {
            DB::beginTransaction();
            $clinic_history = ClinicHistory::create($request->all());
            $id_patient = $request->id_patient;
            if($request->files_clinic_history){
                foreach ($request->files_clinic_history as $file_clinic_history) {
                    $path = $this->save_image_public_folder($file_clinic_history, "users/clinic_history/patient/$id_patient", null);
                    $clinic_history_file = new ClinicHistoryFile();
                    $clinic_history_file->id_clinic_history = $clinic_history->id;
                    $clinic_history_file->url = $path;
                    $clinic_history_file->save();
                    Audith::new(Auth::user()->id, "Nuevo archivo para historia clinica", ['id_patient' => $request->id_patient], 200, null);
                }
            }
            Audith::new(Auth::user()->id, "Creación de historia clinica de paciente", ['id_patient' => $request->id_patient], 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Creación de historia clinica de paciente", ['id_patient' => $request->id_patient], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = ClinicHistory::with(['professional:id,name,last_name,profile_picture', 'professional.specialties.status', 'professional.specialties.specialty', 'files'])->find($clinic_history->id);
        $message = "Historia clinica guardada con exito";
        return response(compact("data"));
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
}
