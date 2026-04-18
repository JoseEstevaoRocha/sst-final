<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{WhatsappMensagem, Colaborador, Clinica, ASO, Empresa};
use Illuminate\Support\Facades\DB;

class WhatsAppController extends Controller {
    public function index(Request $r) {
        $q = WhatsappMensagem::with(['colaborador.funcao','clinica']);
        if ($r->status)    $q->where('status',$r->status);
        if ($r->tipo_exame)$q->where('tipo_exame',$r->tipo_exame);
        $mensagens = $q->orderByDesc('created_at')->paginate(20)->withQueryString();
        $stats = ['total'=>WhatsappMensagem::count(),'pendentes'=>WhatsappMensagem::where('status','pendente')->count(),'enviados'=>WhatsappMensagem::where('status','enviado')->count(),'agendados'=>WhatsappMensagem::where('status','agendado')->count()];
        $clinicas = Clinica::ativas()->get();
        $empresas = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        $config = DB::table('whatsapp_configs')->where('empresa_id',auth()->user()->empresa_id)->first();
        return view('whatsapp.index',compact('mensagens','stats','clinicas','empresas','config'));
    }
    public function preparar(Request $r) {
        $r->validate(['colaborador_id'=>'required','clinica_id'=>'required','tipo_exame'=>'required']);
        $colab   = Colaborador::with(['funcao','setor','empresa'])->findOrFail($r->colaborador_id);
        $clinica = Clinica::findOrFail($r->clinica_id);
        abort_unless($clinica->whatsapp, 422, 'Clínica sem WhatsApp.');
        $cfg    = DB::table('whatsapp_configs')->where('empresa_id', auth()->user()->empresa_id)->first();
        $modelo = $cfg?->modelo_mensagem ?? "*SOLICITAÇÃO DE AGENDAMENTO*\nEmpresa: {empresa}\nColaborador: {nome}\nCPF: {cpf}\nRG: {rg}\nNasc: {nasc}\nExame: {tipo}\nSetor Atual: {setor}\nFunção Atual: {funcao}\nData: {data} às {horario}";

        $cpfFmt  = $colab->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $colab->cpf) : '—';
        $nascFmt = $colab->data_nascimento ? $colab->data_nascimento->format('d/m/Y') : '—';
        $dataFmt = $r->data_agendada ? \Carbon\Carbon::parse($r->data_agendada)->format('d/m/Y') : '—';
        $horario = $r->horario_agendado ?? '—';

        $msg = str_replace(
            ['{nome}','{funcao}','{empresa}','{tipo}','{cpf}','{rg}','{nasc}','{setor}','{data}','{horario}','{clinica}'],
            [
                strtoupper($colab->nome),
                $colab->funcao ? ($colab->funcao->nome . ($colab->funcao->cbo ? ' ' . $colab->funcao->cbo : '')) : '—',
                strtoupper($colab->empresa?->razao_social ?? $colab->empresa?->nome_display ?? '—'),
                strtoupper($r->tipo_exame),
                $cpfFmt,
                $colab->rg ?? '—',
                $nascFmt,
                strtoupper($colab->setor?->nome ?? '—'),
                $dataFmt,
                $horario,
                strtoupper($clinica->nome),
            ],
            $modelo
        );

        $asoId = null;
        if ($r->criar_aso) {
            $aso   = ASO::create(['colaborador_id'=>$colab->id,'empresa_id'=>$colab->empresa_id,'tipo'=>strtolower(str_replace(' ','_',$r->tipo_exame)),'status_logistico'=>'agendado','clinica_id'=>$clinica->id,'clinica_nome'=>$clinica->nome]);
            $asoId = $aso->id;
        }
        WhatsappMensagem::create(['empresa_id'=>auth()->user()->empresa_id,'colaborador_id'=>$colab->id,'clinica_id'=>$clinica->id,'aso_id'=>$asoId,'tipo_exame'=>$r->tipo_exame,'mensagem_texto'=>$msg,'status'=>'pendente','usuario_envio'=>auth()->user()->name]);
        return redirect()->route('whatsapp.index')->with('success','Solicitação criada!');
    }
    public function enviar(WhatsappMensagem $msg) {
        $msg->update(['status'=>'enviado','data_envio'=>now()]);
        return redirect()->route('whatsapp.url',$msg);
    }
    public function getUrl(WhatsappMensagem $msg) {
        $num = '55'.preg_replace('/\D/','',$msg->clinica->whatsapp??'');
        return redirect('https://wa.me/'.$num.'?text='.rawurlencode($msg->mensagem_texto));
    }
    public function resposta(Request $r, WhatsappMensagem $msg) {
        $r->validate(['data_agendada'=>'required|date']);
        $msg->update(['status'=>'agendado','data_agendada'=>$r->data_agendada,'horario_agendado'=>$r->horario_agendado]);
        if ($msg->aso_id) ASO::find($msg->aso_id)?->update(['data_exame'=>$r->data_agendada,'status_logistico'=>'agendado']);
        return back()->with('success','Agendamento confirmado!');
    }
    public function concluir(WhatsappMensagem $msg) {
        $msg->update(['status'=>'concluido']);
        if ($msg->aso_id) ASO::find($msg->aso_id)?->update(['status_logistico'=>'finalizado','resultado'=>'apto']);
        return back()->with('success','Concluído!');
    }
    public function config() { $config=DB::table('whatsapp_configs')->where('empresa_id',auth()->user()->empresa_id)->first(); return view('whatsapp.config',compact('config')); }
    public function saveConfig(Request $r) {
        $eid = auth()->user()->empresa_id;
        DB::table('whatsapp_configs')->updateOrInsert(
            ['empresa_id' => $eid],
            [
                'modelo_mensagem'      => $r->modelo_mensagem,
                'modelo_mudanca_funcao'=> $r->modelo_mudanca_funcao,
                'incluir_cpf'          => $r->boolean('incluir_cpf'),
                'telefone_retorno'     => $r->telefone_retorno,
                'updated_at'           => now(),
                'created_at'           => now(),
            ]
        );
        return back()->with('success','Configuração salva!');
    }
}
