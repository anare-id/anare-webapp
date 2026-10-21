<?php

namespace App\Http\Controllers;

use App\Support\AdminDemoContent;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        if ($request->session()->get('admin_demo_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return response()->view('admin.login')->header('Cache-Control', 'no-store, private');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        if ($credentials['username'] !== 'admin' || $credentials['password'] !== 'anare123') {
            return back()->withErrors(['username' => 'Username atau kata sandi tidak sesuai. Silakan gunakan akun demo di bawah.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_demo_authenticated', true);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_demo_authenticated');
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('admin_message', 'Kamu berhasil keluar dari panel admin.');
    }

    public function index(string $section = 'dashboard')
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

        return view('admin.workspace', [
            'section' => $section,
            'page' => $sections[$section],
            'seed' => AdminDemoContent::make(),
        ]);
    }
}
