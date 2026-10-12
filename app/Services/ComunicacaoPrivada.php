<?php

namespace App\Services;

use App\Models\ConfiguracaoSistema;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ComunicacaoPrivada
{
    public function __construct(private AnexosComunicacao $anexos) {}

    public static function contexto(Usuario $usuario): string
    {
        return hash_hmac('sha256', 'mensagens-interface|' . $usuario->id, (string) config('app.key'));
    }

    public function usuario(Request $request, bool $conteudo = false, bool $travar = false): Usuario
    {
        abort_unless($request->user(), 401);
        $query = Usuario::whereKey($request->user()->getAuthIdentifier());
        if ($travar) $query->lockForUpdate();
        $usuario = $query->firstOrFail();
        // Uma página aberta por outra conta não pode enviar/consultar como a conta nova.
        // Downloads continuam usando autorização da conversa, sem depender deste header.
        if (!$request->routeIs('mensagens.anexo')) {
            abort_unless(hash_equals(self::contexto($usuario), (string) $request->header('X-Mensagens-Contexto', '')), 403,
                'A conta de acesso mudou. Atualize a página e entre novamente.');
        }
        if ($usuario->tipo === 'psicologa') {
            abort_unless(SegurancaPsicologa::sessaoAtual($request, $usuario) && SegurancaPsicologa::configurada($usuario), 403);
            if ($conteudo) abort_unless(AcessoProntuario::valido($request, $usuario), 423, 'Informe a senha do prontuário para abrir as conversas.');
        } else {
            abort_unless(in_array($usuario->tipo, ['estudante', 'aluno'], true) && !$usuario->cadastro_provisorio, 403);
        }
        return $usuario;
    }

    public function instalado(): void
    {
        abort_unless(Schema::hasTable('mensagens_conversas') && Schema::hasTable('mensagens_anexos'), 503,
            'O contato interno ainda não está disponível. Execute a migration de comunicação.');
    }

    private function travar(): void
    {
        abort_unless(DB::table('travas_operacoes')->where('nome', 'mensagens')->lockForUpdate()->first(), 503,
            'Execute a migration de comunicação antes de usar este recurso.');
    }

    private function conversa(Usuario $usuario, int $id, bool $travar = false): object
    {
        $query = DB::table('mensagens_conversas')->where('id', $id);
        if ($usuario->tipo !== 'psicologa') $query->where('aluno_id', $usuario->id);
        if ($travar) $query->lockForUpdate();
        $conversa = $query->first();
        abort_unless($conversa, 404);
        return $conversa;
    }

    public function resumo(Request $request): array
    {
        $this->instalado();
        $usuario = $this->usuario($request);
        $psicologa = $usuario->tipo === 'psicologa';
        $query = DB::table('mensagens_internas as m')->join('mensagens_conversas as c', 'c.id', '=', 'm.conversa_id')
            ->where('m.origem', $psicologa ? 'aluno' : 'psicologa')
            ->whereColumn('m.id', '>', $psicologa ? 'c.lida_psicologa_ate' : 'c.lida_aluno_ate');
        if (!$psicologa) $query->where('c.aluno_id', $usuario->id);
        $liberado = !$psicologa || AcessoProntuario::valido($request, $usuario);
        return ['nao_lidas' => $query->count(), 'psicologa' => $psicologa, 'desbloqueado' => $liberado,
            'agora' => now()->timestamp, 'expira_em' => $psicologa && $liberado
                ? (int) $request->session()->get('prontuario_ultimo_acesso') + 60 * config('psicologia.prontuario_inatividade_minutos', 30) : null];
    }

    public function abrir(Request $request, ?int $alunoId): array
    {
        $this->instalado();
        $usuario = $this->usuario($request, true);
        $alvo = $usuario->tipo === 'psicologa' ? $alunoId : $usuario->id;
        abort_unless($alvo, 422, 'Selecione um aluno.');
        $id = DB::transaction(function () use ($request, $alvo) {
            $this->travar(); $usuario = $this->usuario($request, true, true);
            if ($usuario->tipo !== 'psicologa') abort_unless((int) $alvo === (int) $usuario->id, 403);
            abort_unless(Usuario::whereKey($alvo)->whereIn('tipo', ['estudante', 'aluno'])->exists(), 404);
            $conversa = DB::table('mensagens_conversas')->where('aluno_id', $alvo)->first();
            return $conversa ? (int) $conversa->id : DB::table('mensagens_conversas')->insertGetId([
                'aluno_id' => $alvo, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
        return $this->detalhes($usuario, $this->conversa($usuario, $id));
    }

    private function detalhes(Usuario $usuario, object $conversa): array
    {
        $aluno = Usuario::findOrFail($conversa->aluno_id);
        $psicologa = $usuario->tipo === 'psicologa';
        $email = $psicologa ? $aluno->email : ConfiguracaoSistema::whereKey(1)->value('atendimento_email');
        $email = trim((string) $email);
        return ['id' => (int) $conversa->id, 'aluno_id' => (int) $aluno->id,
            'nome' => $psicologa ? $aluno->nome : 'Setor de Psicologia',
            'turma' => $psicologa ? $aluno->turma_formatada : '', 'matricula' => $psicologa ? $aluno->matricula : '',
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null,
            'email_confirmado' => $psicologa ? $aluno->emailConfirmado() : true];
    }

    public function historico(Request $request, int $id): array
    {
        $usuario = $this->usuario($request, true);
        $conversa = $this->conversa($usuario, $id);
        $depois = (int) $request->query('depois', 0); $antes = (int) $request->query('antes', 0);
        $query = DB::table('mensagens_internas')->where('conversa_id', $id);
        if ($depois) $query->where('id', '>', $depois)->orderBy('id');
        else { if ($antes) $query->where('id', '<', $antes); $query->orderByDesc('id'); }
        $itens = $query->limit(51)->get(); $mais = $itens->count() > 50;
        $itens = $itens->take(50); if (!$depois) $itens = $itens->reverse()->values();
        return ['conversa' => $this->detalhes($usuario, $conversa),
            'mensagens' => $this->apresentar($usuario, $itens), 'mais' => $mais,
            'agora' => now()->timestamp, 'expira_em' => $usuario->tipo === 'psicologa'
                ? (int) $request->session()->get('prontuario_ultimo_acesso') + 60 * config('psicologia.prontuario_inatividade_minutos', 30) : null];
    }

    private function apresentar(Usuario $usuario, $itens): array
    {
        $anexos = DB::table('mensagens_anexos')->whereIn('mensagem_id', $itens->pluck('id'))->get()->groupBy('mensagem_id');
        return $itens->map(function ($item) use ($usuario, $anexos): array {
            try { $texto = Crypt::decryptString($item->conteudo); }
            catch (\Illuminate\Contracts\Encryption\DecryptException $e) { $texto = 'Não foi possível ler esta mensagem. Procure o responsável técnico.'; }
            return ['id' => (int) $item->id, 'minha' => (int) $item->remetente_id === (int) $usuario->id,
                'origem' => $item->origem, 'texto' => $texto,
                'hora' => \Carbon\Carbon::parse($item->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
                'anexos' => $anexos->get($item->id, collect())->map(function ($anexo) {
                    try { $nome = Crypt::decryptString($anexo->nome); }
                    catch (\Illuminate\Contracts\Encryption\DecryptException $e) { $nome = 'Arquivo anexado'; }
                    return ['nome' => $nome, 'tamanho' => (int) $anexo->tamanho, 'url' => route('mensagens.anexo', ['anexo' => $anexo->uuid])];
                })->values()->all()];
        })->values()->all();
    }

    public function enviar(Request $request, int $id, array $dados): array
    {
        $usuario = $this->usuario($request, true); $this->conversa($usuario, $id);
        $arquivos = $this->anexos->preparar($request->file('arquivos', []));
        $texto = trim((string) ($dados['texto'] ?? ''));
        $chave = hash_hmac('sha256', 'mensagem|' . $usuario->id . '|' . strtolower($dados['chave_envio']), (string) config('app.key'));
        $hash = hash_hmac('sha256', json_encode([$id, $texto, array_map(fn ($a) => [$a['nome'], $a['mime'], $a['tamanho'], $a['hash']], $arquivos)], JSON_THROW_ON_ERROR), (string) config('app.key'));
        try {
            $mensagemId = DB::transaction(function () use ($request, $id, $texto, $chave, $hash, $arquivos) {
                $this->travar(); $usuario = $this->usuario($request, true, true);
                $conversa = $this->conversa($usuario, $id, true);
                $anterior = DB::table('mensagens_internas')->where('chave_envio', $chave)->first();
                if ($anterior) {
                    abort_unless((int) $anterior->conversa_id === $id && (int) $anterior->remetente_id === (int) $usuario->id
                        && hash_equals($anterior->resumo_hash, $hash), 409, 'A tentativa anterior usou outro conteúdo. Confira o resultado antes de iniciar outra mensagem.');
                    return (int) $anterior->id;
                }
                $limite = 'mensagem:' . hash_hmac('sha256', (string) $usuario->id, (string) config('app.key'));
                $maximo = config($usuario->tipo === 'psicologa' ? 'comunicacao.mensagens_por_hora_psicologa' : 'comunicacao.mensagens_por_hora_aluno');
                abort_if(RateLimiter::tooManyAttempts($limite, $maximo), 429, 'Limite de mensagens neste intervalo. Aguarde antes de tentar novamente.');
                $total = array_sum(array_column($arquivos, 'tamanho'));
                if ($total) {
                    $diario = DB::table('mensagens_anexos as a')->join('mensagens_internas as m', 'm.id', '=', 'a.mensagem_id')
                        ->where('m.remetente_id', $usuario->id)->where('m.created_at', '>=', now('America/Sao_Paulo')->startOfDay()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'))->sum('a.tamanho');
                    if ($diario + $total > config('comunicacao.anexos_diarios_bytes')) throw ValidationException::withMessages(['arquivos' => 'Limite diário de anexos atingido. Tente em outro dia ou entre em contato com o setor.']);
                    if (DB::table('mensagens_anexos')->sum('tamanho') + $total > config('comunicacao.armazenamento_maximo_bytes')) {
                        throw ValidationException::withMessages(['arquivos' => 'O armazenamento de anexos atingiu o limite. Procure o responsável pelo sistema.']);
                    }
                }
                if ($usuario->tipo === 'psicologa') AcessoProntuario::exigir($request, $usuario);
                $mensagemId = DB::table('mensagens_internas')->insertGetId(['conversa_id' => $id, 'remetente_id' => $usuario->id,
                    'origem' => $usuario->tipo === 'psicologa' ? 'psicologa' : 'aluno', 'chave_envio' => $chave,
                    'resumo_hash' => $hash, 'conteudo' => Crypt::encryptString($texto), 'created_at' => now(), 'updated_at' => now()]);
                $this->anexos->gravar($mensagemId, $arquivos);
                DB::table('mensagens_conversas')->where('id', $id)->update(['ultima_mensagem_em' => now(), 'updated_at' => now()]);
                RateLimiter::hit($limite, 3600);
                return $mensagemId;
            }, 3);
        } catch (\Throwable $e) { $this->anexos->limparNaoRegistrados($arquivos); throw $e; }
        $this->anexos->limparNaoRegistrados($arquivos);
        $usuario = $this->usuario($request, true);
        return $this->apresentar($usuario, DB::table('mensagens_internas')->where('id', $mensagemId)->get())[0];
    }

    public function marcarLidas(Request $request, int $id, int $ate): void
    {
        DB::transaction(function () use ($request, $id, $ate) {
            $this->travar(); $usuario = $this->usuario($request, true, true);
            $conversa = $this->conversa($usuario, $id, true);
            abort_unless(DB::table('mensagens_internas')->where('conversa_id', $id)->where('id', $ate)->exists(), 422);
            $campo = $usuario->tipo === 'psicologa' ? 'lida_psicologa_ate' : 'lida_aluno_ate';
            if ((int) $conversa->{$campo} < $ate) DB::table('mensagens_conversas')->where('id', $id)->update([$campo => $ate]);
        }, 3);
    }

    private function filtrar($query, Request $request, string $prefixo = ''): void
    {
        $like = fn ($v) => '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim((string) $v)) . '%';
        foreach (['nome', 'matricula'] as $campo) {
            if ($request->filled($campo)) $query->whereRaw($prefixo . $campo . " LIKE ? ESCAPE '!'", [$like($request->query($campo))]);
        }
        if ($request->filled('turma')) {
            $campo = $prefixo . 'turma_codigo';
            $query->whereRaw("(CASE WHEN {$campo} REGEXP ? THEN SUBSTRING({$campo}, 7) ELSE {$campo} END) LIKE ? ESCAPE '!'",
                ['^[0-9]{4}[12]\\.[0-9]+\\.[0-9]+\\.[A-Za-z0-9]+$', $like($request->query('turma'))]);
        }
    }

    public function contatos(Request $request): array
    {
        $usuario = $this->usuario($request, true); abort_unless($usuario->tipo === 'psicologa', 403);
        $query = DB::table('mensagens_conversas as c')->join('usuarios as u', 'u.id', '=', 'c.aluno_id')->whereNotNull('c.ultima_mensagem_em');
        $this->filtrar($query, $request, 'u.');
        $naoLidas = DB::table('mensagens_internas as m')->selectRaw('COUNT(*)')->whereColumn('m.conversa_id', 'c.id')
            ->where('m.origem', 'aluno')->whereColumn('m.id', '>', 'c.lida_psicologa_ate');
        $pagina = $query->select(['c.id', 'u.id as aluno_id', 'u.nome', 'u.turma_codigo', 'u.matricula'])
            ->selectSub($naoLidas, 'nao_lidas')->orderByDesc('c.ultima_mensagem_em')->orderByDesc('c.id')->paginate(30);
        return ['contatos' => $pagina->getCollection()->map(fn ($c) => ['id' => (int) $c->id, 'aluno_id' => (int) $c->aluno_id,
            'nome' => $c->nome, 'turma' => Usuario::formatarTurma($c->turma_codigo), 'matricula' => $c->matricula, 'nao_lidas' => (int) $c->nao_lidas])->all(),
            'pagina' => $pagina->currentPage(), 'ultima_pagina' => $pagina->lastPage(), 'turmas' => $this->turmas()];
    }

    public function alunos(Request $request): array
    {
        $usuario = $this->usuario($request, true); abort_unless($usuario->tipo === 'psicologa', 403);
        $query = Usuario::whereIn('tipo', ['estudante', 'aluno']); $this->filtrar($query, $request);
        $alunos = $query->orderBy('nome')->limit(50)->get(['id', 'nome', 'turma_codigo', 'matricula']);
        return ['alunos' => $alunos->map(fn ($a) => ['id' => (int) $a->id, 'nome' => $a->nome,
            'turma' => $a->turma_formatada, 'matricula' => $a->matricula])->all(), 'turmas' => $this->turmas()];
    }

    private function turmas(): array
    {
        $catalogo = ['1.18.1I', '2.18.1I', '3.18.1I', '4.18.1I', '1.18.2I', '2.18.2I', '3.18.2I', '4.18.2I',
            '1.28.1I', '2.28.1I', '3.28.1I', '4.28.1I', '1.28.2I', '2.28.2I', '3.28.2I', '4.28.2I'];
        return collect($catalogo)->merge(Usuario::whereIn('tipo', ['estudante', 'aluno'])->whereNotNull('turma_codigo')
            ->distinct()->pluck('turma_codigo')->map(fn ($t) => Usuario::formatarTurma($t)))->unique()->sort(SORT_NATURAL)->values()->all();
    }

    public function anexo(Request $request, string $uuid): array
    {
        $usuario = $this->usuario($request, true);
        $anexo = DB::table('mensagens_anexos as a')->join('mensagens_internas as m', 'm.id', '=', 'a.mensagem_id')
            ->where('a.uuid', $uuid)->select(['a.*', 'm.conversa_id'])->first();
        abort_unless($anexo, 404); $this->conversa($usuario, (int) $anexo->conversa_id);
        if ($usuario->tipo === 'psicologa') AcessoProntuario::exigir($request, $usuario);
        return ['conteudo' => $this->anexos->ler($anexo), 'nome' => Crypt::decryptString($anexo->nome), 'mime' => $anexo->mime];
    }
}
