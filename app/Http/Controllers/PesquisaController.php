<?php

namespace App\Http\Controllers;

use App\Services\PesquisaPrimeiroAgendamento as Pesquisa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt, DB};
use Throwable;

class PesquisaController extends Controller
{
    public function convite(Request $request)
    {
        return response()->json(['exibir' => Pesquisa::exibir($request->user())])->header('Cache-Control', 'no-store');
    }
    public function responder(Request $request)
    {
        abort_unless(in_array($request->user()->tipo, ['estudante', 'aluno'], true), 403);
        abort_unless(Pesquisa::ativa(), 410, 'O período desta pesquisa terminou. Seu agendamento continua confirmado.');
        $dados = $request->validate(Pesquisa::regras(), [
            'required' => 'Responda às perguntas de 1 a 6 antes de enviar, ou escolha Agora não.',
            'in' => 'Selecione uma das opções apresentadas.',
            'estrelas.between' => 'Escolha uma avaliação de 0 a 5 estrelas.',
            'sugestao.max' => 'A sugestão pode ter até 2.000 caracteres.',
        ]);
        Pesquisa::responder($request->user(), $dados);
        return response()->json(['status' => 'success', 'message' => Pesquisa::AGRADECIMENTO])->header('Cache-Control', 'no-store');
    }
    public function dispensar(Request $request)
    {
        Pesquisa::dispensar($request->user());
        return response()->json(['status' => 'success'])->header('Cache-Control', 'no-store');
    }
    public function index(Request $request)
    {
        abort_unless(Pesquisa::podeConsultar($request), 403, 'Acesso restrito aos responsáveis pela pesquisa.');
        $total = DB::table('pesquisa_respostas')->count();
        $media = DB::table('pesquisa_respostas')->avg('estrelas');
        $distribuicoes = [];
        foreach (Pesquisa::PERGUNTAS as $campo => $pergunta) {
            $distribuicoes[$campo] = DB::table('pesquisa_respostas')->select($campo)->selectRaw('COUNT(*) as quantidade')
                ->groupBy($campo)->pluck('quantidade', $campo)->all();
        }
        $respostas = DB::table('pesquisa_respostas')->orderBy('id')->paginate(20);
        foreach ($respostas as $resposta) {
            try { $resposta->sugestao = $resposta->sugestao === null ? null : Crypt::decryptString($resposta->sugestao); }
            catch (Throwable $erro) { $resposta->sugestao = 'Não foi possível ler esta sugestão.'; }
        }
        return response()->view('pesquisa.index', compact('total', 'media', 'distribuicoes', 'respostas'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }
    public function excluir(Request $request, string $resposta)
    {
        abort_unless(Pesquisa::podeConsultar($request), 403, 'Acesso restrito aos responsáveis pela pesquisa.');
        DB::table('pesquisa_respostas')->where('id', $resposta)->delete();
        // Excluir uma resposta não reabre o convite nem cria um vínculo com seu autor.
        return redirect()->route('pesquisa.index')->with('sucesso', 'Resposta excluída da pesquisa.');
    }
}
