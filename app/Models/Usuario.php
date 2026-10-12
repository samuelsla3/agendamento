<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
    'nome',
    'matricula',
    'email',
    'turma_codigo',
    'cadastro_provisorio',
    'senha',
    'tipo',
    'data_nascimento',
    'cidade',
];

    protected $hidden = ['senha', 'password', 'email_escolhido', 'email_confirmado_em', 'email_confirmado_hash',
        'email_pendente', 'email_codigo_hash', 'email_codigo_expira_em', 'email_codigo_criado_em',
        'email_codigo_tentativas', 'email_codigo_versao', 'email_verificacao_aviso_id', 'senha_prontuario', 'psicologa_conta_teste',
        'psicologa_senha_local_definida', 'psicologa_configurada_em',
        'psicologa_autenticacao_versao', 'psicologa_prontuario_versao'];

    protected $casts = ['cadastro_provisorio' => 'boolean', 'email_escolhido' => 'boolean',
        'email_confirmado_em' => 'datetime', 'email_pendente' => 'encrypted',
        'email_codigo_expira_em' => 'datetime', 'email_codigo_criado_em' => 'datetime',
        'email_codigo_tentativas' => 'integer', 'email_codigo_versao' => 'integer',
        'psicologa_conta_teste' => 'boolean', 'psicologa_senha_local_definida' => 'boolean',
        'psicologa_configurada_em' => 'datetime', 'psicologa_autenticacao_versao' => 'integer',
        'psicologa_prontuario_versao' => 'integer'];

    public function emailConfirmado(): bool
    {
        $email = mb_strtolower(trim((string) $this->email), 'UTF-8');
        return $this->email_confirmado_em !== null
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && is_string($this->email_confirmado_hash)
            && hash_equals($this->email_confirmado_hash, hash('sha256', $email));
    }

    
    public function getAuthPassword()
    {
        return $this->senha;
    }

    public static function formatarTurma(?string $codigo): string
{
    $codigo = trim($codigo ?? '');

    if ($codigo === '') {
        return 'Não informada';
    }

    // Exemplo: 20261.4.18.1I → 4.18.1I
    return preg_replace(
        '/^\d{4}[12]\.(?=\d+\.\d+\.[A-Za-z0-9]+$)/',
        '',
        $codigo
    ) ?? $codigo;
}

public function getTurmaFormatadaAttribute(): string
{
    return self::formatarTurma($this->turma_codigo);
}
}
