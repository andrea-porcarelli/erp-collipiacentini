<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\UserInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PartnerUserController extends CrudController
{
    public UserInterface $interface;
    public string $path = 'users';

    public function __construct(UserInterface $interface)
    {
        $this->interface = $interface;
    }

    public function store(Request $request, int $partnerId): JsonResponse
    {
        try {
            $request->validate([
                'name'      => 'required|string|max:255',
                'email'     => 'required|email|unique:users,email',
                'password'  => 'required|string|min:8',
                'role'      => 'required|string|' . Rule::in(['partner', 'admin']),
            ], [
                'name.required'     => 'Il nome è obbligatorio',
                'email.required'    => 'L\'email è obbligatoria',
                'email.email'       => 'Inserisci un indirizzo email valido',
                'email.unique'      => 'Questa email è già in uso',
                'password.required' => 'La password è obbligatoria',
                'password.min'      => 'La password deve avere almeno 8 caratteri',
                'role.required'     => 'Il ruolo è obbligatorio',
            ]);

            $user = $this->interface->store([
                'name'       => $request->input('name'),
                'email'      => $request->input('email'),
                'password'   => $request->input('password'),
                'role'       => $request->input('role'),
                'partner_id' => $partnerId,
            ]);

            return $this->success([
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function update(Request $request, int $partnerId, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email,' . $userId,
                'password' => 'nullable|string|min:8',
            ], [
                'name.required'  => 'Il nome è obbligatorio',
                'email.required' => 'L\'email è obbligatoria',
                'email.email'    => 'Inserisci un indirizzo email valido',
                'email.unique'   => 'Questa email è già in uso',
                'password.min'   => 'La password deve avere almeno 8 caratteri',
            ]);

            $user = $this->interface->find($userId);

            $data = [
                'name'  => $request->input('name'),
                'email' => $request->input('email'),
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->input('password'));
            }

            $this->interface->edit($user, $data);

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function destroy(int $partnerId, int $userId): JsonResponse
    {
        try {
            $this->interface->remove($userId);
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
