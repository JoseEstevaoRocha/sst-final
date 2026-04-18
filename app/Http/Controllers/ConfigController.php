<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage, Hash};
use App\Models\User;

class ConfigController extends Controller {
    public function index() {
        $configs = DB::table('system_configs')->pluck('value','key');
        return view('config.index', compact('configs'));
    }

    public function save(Request $r) {
        $r->validate(['system_name'=>'required|min:2|max:60']);
        self::set('system_name', $r->system_name);
        return back()->with('success','Configurações salvas!');
    }

    public function logo(Request $r) {
        $r->validate(['logo'=>'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048']);
        $old = self::get('system_logo');
        if ($old) Storage::disk('public')->delete($old);
        $path = $r->file('logo')->store('logos','public');
        self::set('system_logo', $path);
        return back()->with('success','Logo atualizada!');
    }

    public function usuarios() {
        $user  = auth()->user();
        $query = User::with('empresa')->withoutTrashed();
        if (!$user->isSuperAdmin()) $query->where('empresa_id',$user->empresa_id);
        $usuarios = $query->orderBy('name')->paginate(20);
        $empresas = $user->isSuperAdmin() ? \App\Models\Empresa::ativas()->get() : collect();
        return view('config.usuarios', compact('usuarios','empresas'));
    }

    public function storeUsuario(Request $r) {
        $r->validate([
            'name'       => 'required|min:3',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8|confirmed',
            'empresa_id' => 'nullable|exists:empresas,id',
            'role'       => 'required|in:admin,gestor,operador,visualizador',
        ]);
        $user = User::create([
            'name'       => $r->name,
            'email'      => $r->email,
            'password'   => Hash::make($r->password),
            'empresa_id' => $r->empresa_id ?? auth()->user()->empresa_id,
            'cargo'      => $r->cargo,
            'active'     => true,
        ]);
        $user->assignRole($r->role);
        return back()->with('success','Usuário criado!');
    }

    public function updateUsuario(Request $r, User $user) {
        $r->validate(['name'=>'required','email'=>"required|email|unique:users,email,{$user->id}"]);
        $user->update(['name'=>$r->name,'email'=>$r->email,'cargo'=>$r->cargo,'active'=>$r->boolean('active')]);
        if ($r->role) { $user->syncRoles([$r->role]); }
        if ($r->password) {
            $r->validate(['password'=>'min:8|confirmed']);
            $user->update(['password'=>Hash::make($r->password)]);
        }
        return back()->with('success','Usuário atualizado!');
    }

    public function destroyUsuario(User $user) {
        if ($user->id === auth()->id()) return back()->withErrors(['error'=>'Não é possível excluir seu próprio usuário.']);
        $user->delete();
        return back()->with('success','Usuário excluído!');
    }

    public static function get(string $key, string $default=''): string {
        return DB::table('system_configs')->where('key',$key)->value('value') ?? $default;
    }
    public static function set(string $key, $value): void {
        DB::table('system_configs')->updateOrInsert(['key'=>$key],['value'=>$value,'updated_at'=>now(),'created_at'=>now()]);
    }
}
