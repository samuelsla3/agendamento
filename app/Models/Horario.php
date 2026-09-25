<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';
    protected $fillable = ['data', 'hora', 'disponivel', 'nome', 'matricula', 'confirmado', 'justificativa_cancelamento', 'token_cancelamento'];
    protected $hidden = ['token_cancelamento'];
    public function usuario() { return $this->belongsTo(Usuario::class, 'matricula', 'matricula'); }
    public function versao(): string
    {
        return hash('sha256', implode('|', [$this->id, $this->data, $this->hora,
            (int) $this->disponivel, (int) $this->confirmado, $this->token_cancelamento ?? '',
            $this->getRawOriginal('updated_at') ?? '']));
    }
    public function conferirVersao($versao): void
    {
        abort_unless(is_string($versao) && hash_equals($this->versao(), $versao), 409,
            'Este horário mudou. Atualize a agenda antes de continuar.');
    }
    /** Chamar somente dentro de DB::transaction(). */
    public function bloquearReservaAtual(): self
    {
        $atual = self::whereKey($this->getKey())->lockForUpdate()->first();
        abort_if(!$atual || (int) $atual->disponivel !== 0 || (int) $atual->confirmado === 1
            || empty($this->token_cancelamento) || $atual->token_cancelamento !== $this->token_cancelamento,
            409, 'Este agendamento foi alterado ou encerrado. Atualize a página.');
        return $atual;
    }
}
