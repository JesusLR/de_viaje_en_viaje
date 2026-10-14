<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('perfil.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'cPrimerApellido' => ['required', 'string', 'max:100'],
            'cSegundoApellido' => ['nullable', 'string', 'max:100'],
            'cTelefono' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed', 'different:current_password'],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
        ], [
            'current_password.current_password' => 'La contraseña actual es incorrecta.',
            'current_password.required_with' => 'Ingresa tu contraseña actual para cambiarla.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.different' => 'La nueva contraseña debe ser diferente de la actual.',
            'foto.max' => 'La foto no debe superar los 2 MB.',
            'foto.mimes' => 'Selecciona una foto JPG, PNG o WebP.',
        ]);

        $user = $request->user();
        $fotoAnterior = $user->profile_photo_path;
        $fotoNueva = $request->hasFile('foto') ? $request->file('foto')->store('profiles', 'local') : null;
        $user->fill(collect($datos)->only(['name', 'cPrimerApellido', 'cSegundoApellido', 'cTelefono'])->all());
        if ($fotoNueva) {
            $user->profile_photo_path = $fotoNueva;
        }
        if ($request->filled('password')) {
            $user->password = $datos['password'];
            $user->lCambiarPassword = false;
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
        }
        try {
            $user->save();
        } catch (\Throwable $error) {
            if ($fotoNueva) {
                Storage::disk('local')->delete($fotoNueva);
            }
            throw $error;
        }
        if ($fotoNueva && $fotoAnterior) {
            Storage::disk('local')->delete($fotoAnterior);
        }

        return redirect()->route('perfil.edit')->with('success', 'Tu perfil se actualizó correctamente.');
    }

    public function photo(Request $request)
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, no-cache']);
    }
}
