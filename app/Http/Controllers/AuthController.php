<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }

    public function logar(Request $request)
    {
        $credenciais = $request->validate([
            'matricula' => 'required|string',
            'senha'     => 'required|string',
        ]);

        $loginSucesso = Auth::attempt([
            'matricula' => $credenciais['matricula'],
            'password'  => $credenciais['senha'] 
        ]);

        if ($loginSucesso) {
            $request->session()->regenerate();

            $usuario = Auth::user();
            
            if ($usuario->tipo === 'psicologa') {
                session([
                    'usuario_tipo' => 'psicologa', 
                    'usuario_nome' => $usuario->nome,
                    'nome'         => $usuario->nome,
                    'tipo'         => 'psicologa'
                ]);
            } else {
                session([
                    'usuario_tipo' => 'aluno', 
                    'usuario_nome' => $usuario->nome,
                    'nome'         => $usuario->nome,
                    'tipo'         => 'estudante',
                    'matricula'    => $usuario->matricula,
                    'email'        => $usuario->email
                ]);
            }

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'matricula' => 'A matrícula ou a senha fornecida não coincidem.',
        ])->withInput($request->only('matricula'));
    }

    public function redirecionarUsuario()
    {
        $usuario = Auth::user();

        if ($usuario) {
            if ($usuario->tipo === 'psicologa') {
                return redirect()->route('psicologa.index');
            }

            if ($usuario->tipo === 'estudante' || $usuario->tipo === 'aluno') {
                return redirect()->route('agenda.index');
            }
        }

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function mostrarRegistro()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('registro');
    }

    public function registrar(Request $request)
    {
        $request->validate([
            'nome'            => 'required|string|max:100',
            'matricula'       => 'required|string|unique:usuarios,matricula|max:20',
            'email'           => 'required|email|unique:usuarios,email|max:100',
            'data_nascimento' => 'required|date',
            'cidade'          => 'required|string|max:100',
            'senha'           => 'required|string|min:4', 
        ], [
            'matricula.unique' => 'Esta matrícula já está cadastrada.',
            'email.unique'     => 'Este e-mail já está cadastrado.',
        ]);

        Usuario::create([
            'nome'            => $request->nome,
            'matricula'       => $request->matricula,
            'email'           => $request->email,
            'data_nascimento' => $request->data_nascimento,
            'cidade'          => $request->cidade,
            'senha'           => Hash::make($request->senha),
            'tipo'            => 'estudante',
        ]);

        return redirect()->route('login')->with('sucesso', 'Conta criada com sucesso! Faça seu login.');
    }
}