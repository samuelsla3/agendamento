<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AvisosEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmailsController extends Controller
{
    private function autorizar(): void
    {
        abort_unless(auth()->check() && auth()->user()->tipo === 'psicologa', 403);
    }

    public function index(Request $request)
    {
        $this->autorizar();
        $filtros = $request->validate([
            'estado' => 'nullable|in:problemas,todos,pendente,em_envio,falhou,revisar,sem_contato,enviado,descartado',
            'tipo' => 'nullable|in:todos,confirmacao,lembrete,cancelamento,verificacao_email,legado',
            'inicio' => 'nullable|date_format:Y-m-d',
            'fim' => 'nullable|date_format:Y-m-d|after_or_equal:inicio',
        ]);
        $estado = $filtros['estado'] ?? 'problemas';
        $tipo = $filtros['tipo'] ?? 'todos';
        $query = DB::table('avisos_email');
        if ($estado === 'problemas') {
            $query->where(fn ($q) => AvisosEmail::problemas($q));
        } elseif ($estado !== 'todos') {
            $query->where('estado', $estado);
        }
        if ($tipo !== 'todos') {
            $query->where('tipo', $tipo);
        }
        if (!empty($filtros['inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['inicio']);
        }
        if (!empty($filtros['fim'])) {
            $query->whereDate('created_at', '<=', $filtros['fim']);
        }
        $avisos = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $itens = $avisos->getCollection()->map(fn ($aviso) => $this->apresentar($aviso));
        $matriculas = $itens->pluck('matricula')->filter()->unique();
        $alunos = Usuario::whereIn('matricula', $matriculas)->get(['matricula', 'nome', 'email', 'email_confirmado_em', 'email_confirmado_hash'])->keyBy('matricula');
        foreach ($itens as $item) {
            $aluno = $alunos->get($item->matricula);
            $item->nome = $aluno?->nome ?? $item->nome;
            $item->email_reenvio = null;
            if ($item->estado === 'sem_contato' && $item->tipo_codigo !== 'verificacao_email' && $aluno?->emailConfirmado()) {
                $item->rotulo = 'Contato confirmado';
                $item->orientacao = 'O aluno confirmou o contato. Tente novamente para enviar o aviso ao endereço atual.';
                $item->email_reenvio = $aluno->email;
            }
        }
        $avisos->setCollection($itens);
        $resumo = DB::table('avisos_email')->select('estado')->selectRaw('COUNT(*) AS total')
            ->groupBy('estado')->pluck('total', 'estado');
        $problemas = AvisosEmail::contarProblemas();
        return response()->view('emails.index', compact('avisos', 'resumo', 'problemas', 'estado', 'tipo', 'filtros'))
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function tentarNovamente(Request $request, int $aviso)
    {
        $this->autorizar();
        $dados = $request->validate([
            'versao' => 'required|string|size:64', 'confirmou_incerto' => 'nullable|boolean',
        ]);
        AvisosEmail::tentarNovamente($aviso, $dados['versao'], $request->boolean('confirmou_incerto'), (int) auth()->id());
        return response()->json(['status' => 'success',
            'message' => 'Tentativa solicitada. Confira a situação atualizada na lista. O agendamento não foi alterado.']);
    }

    private function apresentar(object $aviso): object
    {
        $contexto = [];
        $tipo = $aviso->tipo;
        $recuperavel = true;
        try {
            $contexto = AvisosEmail::contexto($aviso);
            $tipo = AvisosEmail::tipo($aviso);
        } catch (Throwable $e) {
            $recuperavel = false;
        }
        $parado = $aviso->estado === 'em_envio' && Carbon::parse($aviso->updated_at)->lt(now()->subMinutes(15));
        $rotulos = ['pendente' => 'Aguardando envio', 'em_envio' => 'Em envio',
            'falhou' => 'Falha no envio', 'revisar' => 'Envio incerto', 'sem_contato' => 'Sem e-mail válido',
            'enviado' => 'Enviado', 'descartado' => 'Descartado'];
        $orientacoes = [
            'pendente' => 'O aviso foi registrado e ainda aguarda uma tentativa.',
            'em_envio' => $parado ? 'O envio está sem confirmação há mais de 15 minutos. Peça ao responsável para conferir o processo antes de uma nova tentativa.' : 'Há uma tentativa em andamento. Atualize a lista para conferir o resultado.',
            'falhou' => 'O envio não foi concluído. Após resolver o problema, tente novamente.',
            'revisar' => 'Houve falha sem confirmação conclusiva. O aluno pode ter recebido o e-mail. Confira antes de tentar novamente.',
            'sem_contato' => 'Peça ao aluno para informar e confirmar um e-mail na própria conta ou avise por outro meio. A nova tentativa usa o contato confirmado atual.',
            'enviado' => 'O transporte aceitou a mensagem. Isso não confirma leitura nem recebimento na caixa de entrada.',
            'descartado' => 'A reserva foi encerrada, alterada ou o horário já passou. O aviso ficou desatualizado e não será enviado.',
        ];
        if (!$recuperavel) {
            $orientacao = 'Não foi possível recuperar a identificação deste aviso. Procure o responsável pelo sistema.';
        } elseif ($aviso->motivo_codigo === 'email_nao_confirmado') {
            $orientacao = 'O aluno precisa confirmar o e-mail com o código recebido, na própria conta. Depois, tente enviar novamente ou avise por outro meio.';
        } elseif ($aviso->motivo_codigo === 'vinculo_contato_ausente') {
            $orientacao = 'Este registro antigo não permite conferir o contato do aluno com segurança. Avise por outro meio.';
        } elseif ($aviso->motivo_codigo === 'codigo_expirado') {
            $orientacao = 'O código expirou, foi substituído ou já utilizado. O aluno deve solicitar outro código na própria conta.';
        } elseif ($aviso->motivo_codigo === 'preparacao') {
            $orientacao = 'Não foi possível preparar a mensagem. Peça ao responsável para verificar a configuração antes de tentar novamente.';
        } elseif ($aviso->motivo_codigo === 'envio_interrompido') {
            $orientacao = 'O envio foi interrompido antes de uma confirmação do transporte. Peça ao responsável para conferir a configuração.';
        } else {
            $orientacao = $orientacoes[$aviso->estado] ?? 'Confira este registro com o responsável pelo sistema.';
        }
        return (object) [
            'id' => $aviso->id, 'versao' => AvisosEmail::versao($aviso), 'estado' => $aviso->estado,
            'tipo_codigo' => $tipo,
            'tipo' => ['verificacao_email' => 'Confirmação de e-mail', 'confirmacao' => 'Confirmação', 'lembrete' => 'Lembrete', 'cancelamento' => 'Cancelamento', 'legado' => 'Registro anterior'][$tipo] ?? 'Registro anterior',
            'antigo' => $aviso->tipo === 'legado',
            'rotulo' => $aviso->motivo_codigo === 'email_nao_confirmado' ? 'Contato não confirmado'
                : ($parado ? 'Envio sem confirmação' : ($rotulos[$aviso->estado] ?? 'Verificar')),
            'nome' => $contexto['nome'] ?? 'Não preservado neste registro anterior',
            'matricula' => $contexto['matricula'] ?? '', 'email' => $contexto['email'] ?? '',
            'data' => $contexto['data'] ?? null, 'hora' => $contexto['hora'] ?? null,
            'criado_em' => Carbon::parse($aviso->created_at)->format('d/m/Y H:i'),
            'tentado_em' => $aviso->iniciado_em ? Carbon::parse($aviso->iniciado_em)->format('d/m/Y H:i') : null,
            'enviado_em' => $aviso->enviado_em ? Carbon::parse($aviso->enviado_em)->format('d/m/Y H:i') : null,
            'tentativas' => (int) $aviso->tentativas, 'orientacao' => $orientacao,
            'pode_tentar' => $recuperavel && $aviso->motivo_codigo !== 'vinculo_contato_ausente' && !empty($aviso->conteudo)
                && in_array($aviso->estado, ['pendente', 'falhou', 'revisar', 'sem_contato'], true),
        ];
    }
}
