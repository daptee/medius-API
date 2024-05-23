<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewUserProfesionalRequest;
use App\Mail\WelcomeUserMailable;
use App\Models\Audith;
use App\Models\ProfessionalRestHour;
use App\Models\ProfessionalSchedule;
use App\Models\ProfessionalSpecialDate;
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

    public function professional_schedules(Request $request)
    {
        $request->validate([
            'id_professional' => 'required',
            'schedules' => 'required'
        ]);

        $message = "Error al cargar horarios de profesional";
        
        $user_request = User::find($request->id_professional);
        if($user_request->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "id professional invalido"], 400);

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $this->deleteSchedulesProfessional($request->id_professional);

                foreach ($request->schedules as $schedule) {
                    $professional_schedule = new ProfessionalSchedule($schedule);
                    $professional_schedule->id_professional = $request->id_professional;
                    $professional_schedule->save();
        
                    if(isset($schedule['rest_hours'])){
                        foreach ($schedule['rest_hours'] as $rest_hour) {
                            $professional_rest_hours = new ProfessionalRestHour($rest_hour);
                            $professional_rest_hours->id_professional_schedule = $professional_schedule->id;
                            $professional_rest_hours->save();
                        }
                    }
                }

                Audith::new(Auth::user()->id, "Carga de horarios usuario profesional", $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error en carga de horarios usuario profesional", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = ProfessionalSchedule::with('rest_hours')->where('id_professional', $request->id_professional)->get();
        $message = "Carga de horarios exitosa";
        return response(compact("message", "data"));
    }

    public function deleteSchedulesProfessional($id_professional)
    {
        try {
            DB::beginTransaction();
                $professional_schedules = ProfessionalSchedule::where('id_professional', $id_professional)->get();
                foreach ($professional_schedules as $professional_schedule) {
                    ProfessionalRestHour::where('id_professional_schedule', $professional_schedule->id)->delete();
                    $professional_schedule->delete();
                }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => "Error al eliminar horarios de profesional", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al eliminar horarios de profesional", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(["message" => "Horarios eliminados correctamente"], 200); 
    }

    public function get_professional($id_professional)
    {
        $user = User::find($id_professional);
        if($user->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "id professional invalido"], 400);

        $data = User::getAllDataUserProfessional($id_professional);

        return response(compact("data"));
    }

    public function professional_special_dates(Request $request)
    {
        $request->validate([
            'id_professional' => 'required',
        ]);

        $message = "Error al cargar fechas especiales";
        
        $user_request = User::find($request->id_professional);
        if($user_request->id_user_type != UserType::PROFESIONAL)
            return response(["message" => "id professional invalido"], 400);

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 400);

        try {
            DB::beginTransaction();
                $this->deleteSpecialDateProfessional($request->id_professional);

                if($request->special_dates){
                    
                    foreach ($request->special_dates as $special_date) {
                        $professional_special_date = new ProfessionalSpecialDate($special_date);
                        $professional_special_date->id_professional = $request->id_professional;
                        $professional_special_date->save();
                    }

                    Audith::new(Auth::user()->id, "Carga de fechas especiales usuario profesional", $request->all(), 200, null);
                }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new(Auth::user()->id, "Error en carga de fechas especiales usuario profesional", $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = ProfessionalSpecialDate::where('id_professional', $request->id_professional)->get();
        $message = "Carga de fechas especiales exitosa";
        return response(compact("message", "data"));
    }

    public function deleteSpecialDateProfessional($id_professional)
    {
        try {
            DB::beginTransaction();
                ProfessionalSpecialDate::where('id_professional', $id_professional)->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => "Error al eliminar fechas especiales de profesional", "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => "Error al eliminar fechas especiales de profesional", "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(["message" => "Fechas especiales eliminadas correctamente"], 200); 
    }

    public function get_professional_special_dates($id_professional)
    {
        $data = ProfessionalSpecialDate::where('id_professional', $id_professional)->get();
        return response(compact("data"));
    }

    public function get_professional_schedules($id_professional)
    {
        $data = ProfessionalSchedule::with('rest_hours')->where('id_professional', $id_professional)->get();
        return response(compact("data"));
    }
}
