<?php

namespace App\Http\Controllers;

use App\Services\CmsContent;
use App\Services\CmsAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class AdminController extends Controller
{
    public function login(Request $request): Response|RedirectResponse
    {
        if (Auth::check() && CmsAccess::can($request->user(), 'admin.access')) {
            return redirect()->to(CmsAccess::landing($request->user()));
        }
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->view('admin.login')->header('Cache-Control', 'no-store, private');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:4096'],
        ]);

        if (!Auth::attempt($credentials) || !CmsAccess::can($request->user(), 'admin.access')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['username' => 'Username atau kata sandi tidak sesuai.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->to(CmsAccess::landing($request->user()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('admin_message', 'Kamu berhasil keluar dari panel admin.');
    }

    public function index(Request $request, string $section = 'dashboard'): View
    {
        $sections = [
            'dashboard' => ['title' => 'Dashboard', 'description' => 'Semua konten Anare, dalam satu tempat.'],
            'beranda' => ['title' => 'Beranda', 'description' => 'Ciptakan kesan pertama yang istimewa untuk pengunjung.'],
            'skin' => ['title' => 'Anare Skin', 'description' => 'Kelola treatment, kategori, dan konten perawatan kulit.'],
            'shape' => ['title' => 'Anare Shape', 'description' => 'Kelola layanan Endolipo, Slimming, dan Tightening.'],
            'dokter' => ['title' => 'Dokter', 'description' => 'Kelola profil, jadwal praktik, dan keahlian tim dokter.'],
            'artikel' => ['title' => 'Artikel', 'description' => 'Bagikan cerita dan informasi perawatan dari Anare Clinic.'],
            'pengaturan' => ['title' => 'Website Settings', 'description' => 'Atur identitas, kontak, dan akun sosial website.'],
        ];
        abort_unless(isset($sections[$section]), 404);
        abort_unless(CmsAccess::can($request->user(), CmsAccess::section($section)), 403);

        return view('admin.workspace', [
            'section' => $section,
            'page' => $sections[$section],
            'seed' => app(CmsContent::class)->state($request->user()),
        ]);
    }
}
