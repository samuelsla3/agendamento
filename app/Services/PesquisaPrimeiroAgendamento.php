<?php

namespace App\Services;

use App\Models\{Horario, ProntuarioSessao, RegistroAtendimento, Usuario};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt, DB};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PesquisaPrimeiroAgendamento
{
    public const FUSO = 'America/Bahia';
    public const INICIO = '2026-10-22 00:00:00';
    public const FIM = '2026-11-21 00:00:00'; // limite exclusivo: inclui todo o dia 20/11
    public const AGRADECIMENTO = 'Obrigado por responder a esta pesquisa. Sua opinião anônima foi registrada!';
    public const PERGUNTAS = [
        'p1' => ['texto' => 'Você conseguiu realizar o que pretendia no site?', 'opcoes' => [
            'sim' => 'Sim', 'parcialmente' => 'Parcialmente', 'nao' => 'Não']],
        'p2' => ['texto' => 'Como foi encontrar e marcar um horário?', 'opcoes' => [
            'muito_dificil' => 'Muito difícil', 'dificil' => 'Difícil', 'neutro' => 'Nem fácil nem difícil',
            'facil' => 'Fácil', 'muito_facil' => 'Muito fácil', 'nao_tentei' => 'Não tentei']],
        'p3' => ['texto' => 'Você precisou de ajuda de outra pessoa para usar o site?', 'opcoes' => [
            'nao' => 'Não', 'sim' => 'Sim', 'nao_consegui' => 'Não consegui usar, mesmo com ajuda']],
        'p4' => ['texto' => 'Como você avalia a privacidade oferecida pela forma de agendamento?', 'opcoes' => [
            'muito_ruim' => 'Muito ruim', 'ruim' => 'Ruim', 'regular' => 'Regular', 'boa' => 'Boa',
            'muito_boa' => 'Muito boa', 'nao_sei' => 'Não sei avaliar']],
        'p5' => ['texto' => 'Em comparação com a ficha, como foi agendar pelo site?', 'opcoes' => [
            'muito_mais_dificil' => 'Muito mais difícil', 'mais_dificil' => 'Mais difícil', 'sem_diferenca' => 'Sem diferença',
            'mais_facil' => 'Mais fácil', 'muito_mais_facil' => 'Muito mais fácil', 'nunca_usei' => 'Nunca usei a ficha']],
    ];
    public static function ativa(): bool
    {
        $agora = now(self::FUSO);
        return $agora->gte(Carbon::parse(self::INICIO, self::FUSO)) && $agora->lt(Carbon::parse(self::FIM, self::FUSO));
    }
    public static function podeConsultar(Request $request): bool
    {
        $usuario = $request->user();
        if (!$usuario) return false;
        if ($usuario->tipo === 'psicologa') return SegurancaPsicologa::sessaoAtual($request, $usuario)
            && SegurancaPsicologa::configurada($usuario);
        return in_array($usuario->tipo, ['estudante', 'aluno'], true) && (string) $usuario->matricula === '20231180003';
    }
    /** Consultado antes de gravar a nova reserva, dentro da transação da agenda. */
    public static function temHistorico(Usuario $usuario): bool
    {
        return DB::table('pesquisa_participacoes')->where('usuario_id', $usuario->id)->exists()
            || Horario::where('matricula', $usuario->matricula)->where('disponivel', 0)->exists()
            || RegistroAtendimento::where('matricula', $usuario->matricula)->exists()
            || ProntuarioSessao::where('aluno_id', $usuario->id)->exists();
    }
    public static function convidar(Usuario $usuario, bool $tinhaHistorico): bool
    {
        if ($tinhaHistorico || !self::ativa() || !in_array($usuario->tipo, ['estudante', 'aluno'], true)) return false;
        return DB::table('pesquisa_participacoes')->insertOrIgnore(['usuario_id' => $usuario->id, 'estado' => 'convidado']) === 1;
    }
    public static function exibir(Usuario $usuario): bool
    {
        if (!self::ativa() || !in_array($usuario->tipo, ['estudante', 'aluno'], true)) return false;
        // A apresentação pode ser reivindicada apenas uma vez, inclusive entre abas.
        return DB::table('pesquisa_participacoes')->where('usuario_id', $usuario->id)->where('estado', 'convidado')
            ->update(['estado' => 'exibido']) === 1;
    }
    public static function regras(): array
    {
        $regras = ['estrelas' => 'required|integer|between:0,5', 'sugestao' => 'nullable|string|max:2000'];
        foreach (self::PERGUNTAS as $campo => $pergunta) $regras[$campo] = ['required', 'string', Rule::in(array_keys($pergunta['opcoes']))];
        return $regras;
    }
    public static function responder(Usuario $usuario, array $dados): void
    {
        DB::transaction(function () use ($usuario, $dados) {
            $participacao = DB::table('pesquisa_participacoes')->where('usuario_id', $usuario->id)->lockForUpdate()->first();
            abort_unless(self::ativa(), 410, 'O período desta pesquisa terminou. Seu agendamento continua confirmado.');
            // Repetir a mesma tentativa após falha de rede não gera uma segunda resposta.
            if ($participacao?->estado === 'respondido') return;
            abort_unless($participacao?->estado === 'exibido', 403, 'Você não possui um convite ativo para esta pesquisa.');
            $resposta = array_intersect_key($dados, array_flip(['p1', 'p2', 'p3', 'p4', 'p5', 'estrelas']));
            $sugestao = trim((string) ($dados['sugestao'] ?? ''));
            $resposta['id'] = (string) Str::uuid();
            $resposta['sugestao'] = $sugestao === '' ? null : Crypt::encryptString($sugestao);
            DB::table('pesquisa_respostas')->insert($resposta);
            DB::table('pesquisa_participacoes')->where('usuario_id', $usuario->id)->update(['estado' => 'respondido']);
        });
    }
    public static function dispensar(Usuario $usuario): void
    {
        DB::table('pesquisa_participacoes')->where('usuario_id', $usuario->id)->where('estado', 'exibido')->update(['estado' => 'dispensado']);
    }
}
