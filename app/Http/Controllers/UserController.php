<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class UserController extends Controller
{
    public function user_plan(Request $request)
    {
        $request->validate([
            'id_plan' => 'required|numeric|exists:App\Models\Plan,id',
        ]);

        if(Auth::user()->id_user_type != UserType::ADMIN)
            return response(["message" => "Usuario invalido"], 500);


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
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($user->id);
        $message = "Registro de nuevo plan exitoso";
        return response(compact("message", "data"));
    }
}
