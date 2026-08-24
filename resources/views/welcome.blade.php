<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VCF API Documentation · PT. INL</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.5);
            --bg: #0b0f1a;
            --sidebar-bg: #111827;
            --card-bg: #1f2937;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border: rgba(255, 255, 255, 0.08);
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --get: #10b981;
            --post: #3b82f6;
            --put: #f59e0b;
            --delete: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .app-container { display: flex; min-height: 100vh; }

        aside {
            width: 280px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        nav h3 {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            margin-top: 2rem;
            font-weight: 700;
        }

        nav ul { list-style: none; }
        nav ul li { margin-bottom: 0.25rem; }
        nav ul li a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.875rem;
            display: block;
            padding: 0.625rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        nav ul li a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.05); }
        nav ul li a.active { color: #fff; background-color: var(--primary); font-weight: 600; box-shadow: 0 4px 12px var(--primary-glow); }

        main { flex: 1; margin-left: 280px; padding: 4rem 5%; max-width: 1400px; }

        header { margin-bottom: 4rem; }
        header h1 { font-size: 3rem; font-weight: 800; margin-bottom: 1rem; letter-spacing: -0.03em; }
        header p { color: var(--text-muted); font-size: 1.125rem; max-width: 700px; }

        section { margin-bottom: 5rem; scroll-margin-top: 4rem; }
        .section-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; }
        .section-title::after { content: ''; height: 1px; flex: 1; background: var(--border); }

        .endpoint-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .endpoint-card:hover { border-color: rgba(255,255,255,0.2); }

        .endpoint-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .method {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            text-transform: uppercase;
            font-family: 'Fira Code', monospace;
        }

        .method.get { background-color: rgba(16, 185, 129, 0.1); color: var(--get); border: 1px solid rgba(16, 185, 129, 0.2); }
        .method.post { background-color: rgba(59, 130, 246, 0.1); color: var(--post); border: 1px solid rgba(59, 130, 246, 0.2); }
        .method.put { background-color: rgba(245, 158, 11, 0.1); color: var(--put); border: 1px solid rgba(245, 158, 11, 0.2); }
        .method.delete { background-color: rgba(239, 68, 68, 0.1); color: var(--delete); border: 1px solid rgba(239, 68, 68, 0.2); }

        .path { font-family: 'Fira Code', monospace; font-size: 0.9rem; color: #fff; font-weight: 500; }
        .endpoint-desc { margin-left: auto; font-size: 0.8rem; color: var(--text-muted); }

        .endpoint-body { padding: 1.5rem; }
        .sub-title { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin: 1.5rem 0 1rem; }
        .sub-title:first-child { margin-top: 0; }

        .params-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        .params-table th, .params-table td { text-align: left; padding: 0.75rem; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .params-table th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; }
        .param-name { font-family: 'Fira Code', monospace; color: var(--primary); font-weight: 600; }
        .param-req { color: var(--error); font-size: 0.7rem; font-weight: 700; margin-left: 0.25rem; }

        .code-block {
            background-color: #0d1117;
            padding: 1.25rem;
            border-radius: 12px;
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            color: #e6edf3;
            overflow-x: auto;
            border: 1px solid var(--border);
            line-height: 1.5;
        }

        .badge-auth { background-color: rgba(59, 130, 246, 0.1); color: var(--primary); font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.2); }

        @media (max-width: 1024px) {
            aside { display: none; }
            main { margin-left: 0; padding: 2rem; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <aside>
            <div class="logo">
                <div class="logo-icon"></div>
                VCF SYSTEM <span style="font-size: 0.6rem; opacity: 0.5; margin-left: 4px;">v1.0</span>
            </div>
            <nav>
                <h3>Overview</h3>
                <ul>
                    <li><a href="#getting-started" class="active">Introduction</a></li>
                    <li><a href="#authentication">Authentication</a></li>
                </ul>

                <h3>Core VCF Process</h3>
                <ul>
                    <li><a href="#vcf-step1">Step 1: Main Gate</a></li>
                    <li><a href="#vcf-step2">Step 2: Weighbridge In</a></li>
                    <li><a href="#vcf-step3">Step 3: Weighbridge Out</a></li>
                    <li><a href="#vcf-step4">Step 4: Exit Gate</a></li>
                    <li><a href="#vcf-rejection">Rejection Flow</a></li>
                </ul>

                <h3>Master Data</h3>
                <ul>
                    <li><a href="#master-users">Users</a></li>
                    <li><a href="#master-transporters">Transporters</a></li>
                    <li><a href="#master-drivers">Drivers</a></li>
                    <li><a href="#master-vehicles">Vehicles</a></li>
                </ul>
            </nav>
        </aside>

        <main>
            <header id="getting-started">
                <h4 style="color: var(--primary); text-transform: uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.2em; margin-bottom: 0.5rem;">Documentation</h4>
                <h1>Backend API Reference</h1>
                <p>Digital Vehicle Control Form (VCF) System for PT. Industri Nabati Lestari. Standardized endpoints for logistics tracking and security inspection.</p>
                
                <div style="margin-top: 3rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <div style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid var(--border);">
                        <h4 style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Base API URL</h4>
                        <code style="font-family: 'Fira Code'; color: var(--primary); font-weight: 600;">{{ url('/api') }}</code>
                    </div>
                    <div style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid var(--border);">
                        <h4 style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Environment</h4>
                        <span style="color: var(--success); font-weight: 700; font-size: 0.9rem;">● Production Ready</span>
                    </div>
                </div>
            </header>

            <section id="authentication">
                <h2 class="section-title">Authentication</h2>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Secure access using <strong>Laravel Sanctum</strong> tokens. Obtain a token via the login endpoint and include it in all subsequent requests.</p>
                <div class="code-block">Authorization: Bearer 1|AbCdEfG12345...</div>
            </section>

            <section id="vcf-step1">
                <h2 class="section-title">VCF Step 1: Main Gate</h2>
                
                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="path">/vcf</span>
                        <span class="badge-auth">Petugas</span>
                        <span class="endpoint-desc">Register new vehicle entry</span>
                    </div>
                    <div class="endpoint-body">
                        <div class="sub-title">Request Body Parameters</div>
                        <table class="params-table">
                            <thead><tr><th>Field</th><th>Type</th><th>Description</th></tr></thead>
                            <tbody>
                                <tr><td><span class="param-name">tanggal</span><span class="param-req">*</span></td><td>date</td><td>YYYY-MM-DD</td></tr>
                                <tr><td><span class="param-name">logistik_id</span><span class="param-req">*</span></td><td>int</td><td>ID from master logistik</td></tr>
                                <tr><td><span class="param-name">produk_id</span><span class="param-req">*</span></td><td>int</td><td>ID from master produk</td></tr>
                                <tr><td><span class="param-name">tipe_kegiatan</span><span class="param-req">*</span></td><td>string</td><td>loading_lokal, loading_export, etc.</td></tr>
                                <tr><td><span class="param-name">no_polisi</span><span class="param-req">*</span></td><td>string</td><td>Vehicle plate number</td></tr>
                                <tr><td><span class="param-name">jam_masuk</span><span class="param-req">*</span></td><td>string</td><td>HH:mm (24h format)</td></tr>
                                <tr><td><span class="param-name">kelengkapan_supir</span></td><td>array</td><td>Array of {item_id, nilai, keterangan}</td></tr>
                            </tbody>
                        </table>
                        <div class="sub-title">Response 201</div>
                        <div class="code-block">
{
    "message": "VCF Bagian 1 berhasil disimpan.",
    "data": { "id": 1, "nomor_urut": "00001", "status": "bagian1_selesai", ... }
}
                        </div>
                    </div>
                </div>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="path">/vcf/next-number</span>
                        <span class="endpoint-desc">Get predicted next sequence number</span>
                    </div>
                </div>
            </section>

            <section id="vcf-rejection">
                <h2 class="section-title">Rejection Mechanism</h2>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Security or Weighbridge officers can reject a VCF if discrepancies are found during inspection.</p>
                
                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method post">POST</span>
                        <span class="path">/vcf/{id}/reject</span>
                        <span class="badge-auth">Authorized User</span>
                        <span class="endpoint-desc">Reject a VCF with a reason</span>
                    </div>
                    <div class="endpoint-body">
                        <div class="sub-title">Payload</div>
                        <div class="code-block">{ "catatan_reject": "Ban kendaraan gundul/tidak layak." }</div>
                    </div>
                </div>
            </section>

            <section id="master-users">
                <h2 class="section-title">Master: Users</h2>
                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method get">GET</span>
                        <span class="path">/master/users</span>
                        <span class="badge-auth">Admin</span>
                    </div>
                    <div class="endpoint-body">
                        <div class="sub-title">Query Filters</div>
                        <p style="font-size: 0.8rem; color: var(--text-muted);">Supports <code class="param-name">search</code> (nama/username) and <code class="param-name">role</code> (admin/petugas).</p>
                    </div>
                </div>
            </section>

            <footer style="margin-top: 8rem; padding: 4rem 0; border-top: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-align: center;">
                &copy; {{ date('Y') }} PT. Industri Nabati Lestari · IT Department<br>
                <span style="opacity: 0.5; margin-top: 8px; display: block;">Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})</span>
            </footer>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('nav ul li a');
            const sections = document.querySelectorAll('section, header');

            window.addEventListener('scroll', () => {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    if (window.pageYOffset >= (sectionTop - 150)) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href').substring(1) === current) {
                        link.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
