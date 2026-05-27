<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // psicóloga Eduarda
        Usuario::create([
            'nome' => 'Eduarda Chaves',
            'email' => 'duda@gmail.com',
            'senha' => Hash::make('sua_senha_aqui'),
            'tipo' => 'psicologa',
            'matricula' => '12345678',
        ]);

        // aluno Caio Valle (importados do projeto antigo)
        Usuario::create([
            'nome' => 'Caio Valle Moraes de Araújo',
            'email' => 'caiolvale0@gmail.com',
            'senha' => Hash::make('sua_senha_aqui'), 
            'tipo' => 'estudante',
            'matricula' => '20211180047',
            'data_nascimento' => '2006-03-02',
            'cidade' => 'Palmeiras',
        ]);
    }
}