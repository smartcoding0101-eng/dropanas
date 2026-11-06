<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes & Analytics — Dropana Bolivia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary:#F4B400; --primary-dark:#D09800; --primary-faint:rgba(244,180,0,.08);
            --bg:#0A0C14; --surface:#111827; --surface2:#1F2937; --surface3:#374151;
            --border:rgba(255,255,255,0.06); --border2:rgba(255,255,255,0.12);
            --text:#F1F5F9; --text-muted:#94A3B8; --text-dim:#64748B;
            --success:#22C55E; --warning:#EAB308; --danger:#EF4444; --info:#3B82F6;
            --purple:#8B5CF6; --pink:#EC4899; --teal:#14B8A6;
            --radius:16px; --radius-sm:10px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; line-height: 1.5; }
        a { text-decoration: none; color: inherit; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--surface); }
        ::-webkit-scrollbar-thumb { background: var(--surface3); border-radius: 3px; }

        /* ── Top Nav ── */
        .topnav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,12,20,.85); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 0 32px; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1rem; }
        .nav-brand .logo { font-size: 1.4rem; }
        .nav-links { display: flex; gap: 4px; }
        .nav-link { padding: 6px 14px; border-radius: 8px; font-size: .8rem; font-weight: 500; color: var(--text-muted); transition: all .2s; }
        .nav-link:hover, .nav-link.active { background: var(--primary-faint); color: var(--primary); }
        .period-selector { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 4px; }
        .period-btn { padding: 5px 12px; border-radius: 7px; font-size: .75rem; font-weight: 600; cursor: pointer; border: none; background: transparent; color: var(--text-muted); transition: all .2s; }
        .period-btn.active { background: var(--primary); color: #000; }

        /* ── Layout ── */
        .page { max-width: 1400px; margin: 0 auto; padding: 28px 24px 48px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
        .page-title { font-size: 1.5rem; font-weight: 800; }
        .page-title span { color: var(--primary); }
        .page-subtitle { color: var(--text-muted); font-size: .85rem; margin-top: 2px; }

        /* ── KPI Cards ── */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .kpi-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 22px;
            position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s;
        }
        .kpi-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        }
        .kpi-card.gold::before    { background: linear-gradient(90deg, var(--primary), #FF8C00); }
        .kpi-card.green::before   { background: linear-gradient(90deg, var(--success), #16A34A); }
        .kpi-card.blue::before    { background: linear-gradient(90deg, var(--info), var(--purple)); }
        .kpi-card.purple::before  { background: linear-gradient(90deg, var(--purple), var(--pink)); }
        .kpi-card.teal::before    { background: linear-gradient(90deg, var(--teal), var(--info)); }
        .kpi-card.red::before     { background: linear-gradient(90deg, var(--danger), var(--pink)); }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.5); }
        .kpi-icon { font-size: 1.5rem; margin-bottom: 10px; display: block; }
        .kpi-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); margin-bottom: 6px; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
        .kpi-value.gold   { color: var(--primary); }
        .kpi-value.green  { color: var(--success); }
        .kpi-value.blue   { color: var(--info); }
        .kpi-value.purple { color: var(--purple); }
        .kpi-value.teal   { color: var(--teal); }
        .kpi-value.red    { color: var(--danger); }
        .kpi-growth { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; margin-top: 8px; }
        .kpi-growth.up   { background: rgba(34,197,94,.12); color: var(--success); }
        .kpi-growth.down { background: rgba(239,68,68,.12); color: var(--danger); }
        .kpi-growth.flat { background: rgba(148,163,184,.12); color: var(--text-muted); }

        /* ── Grid layouts ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grid-7-5 { display: grid; grid-template-columns: 7fr 5fr; gap: 20px; margin-bottom: 20px; }
        .col-span-2 { grid-column: span 2; }
        @media (max-width: 1024px) {
            .grid-2, .grid-3, .grid-7-5 { grid-template-columns: 1fr; }
            .col-span-2 { grid-column: span 1; }
        }

        /* ── Cards ── */
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .card-header { padding: 18px 22px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-size: .9rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 20px 22px; }
        .card-body.no-pad { padding: 0; }

        /* ── Charts ── */
        .chart-wrap { position: relative; }
        canvas { width: 100% !important; }

        /* ── Table ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .data-table thead { background: var(--surface2); }
        .data-table th { padding: 10px 14px; text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); font-weight: 600; white-space: nowrap; }
        .data-table td { padding: 11px 14px; border-top: 1px solid var(--border); }
        .data-table tr:hover td { background: rgba(255,255,255,.02); }
        .rank { font-size: .7rem; color: var(--text-dim); font-weight: 700; }
        .rank-1 { color: var(--primary); }
        .rank-2 { color: #C0C0C0; }
        .rank-3 { color: #CD7F32; }

        /* ── Progress bar ── */
        .bar-wrap { background: var(--surface2); border-radius: 4px; height: 6px; overflow: hidden; flex: 1; }
        .bar-fill { height: 100%; border-radius: 4px; transition: width .8s cubic-bezier(.4,0,.2,1); }

        /* ── Badge ── */
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; }
        .badge-success { background: rgba(34,197,94,.12); color: var(--success); }
        .badge-warning { background: rgba(234,179,8,.12); color: var(--warning); }
        .badge-danger  { background: rgba(239,68,68,.12); color: var(--danger); }
        .badge-info    { background: rgba(59,130,246,.12); color: var(--info); }
        .badge-muted   { background: rgba(148,163,184,.12); color: var(--text-muted); }

        /* ── Dept Map ── */
        .dept-list { display: flex; flex-direction: column; gap: 10px; }
        .dept-row { display: flex; align-items: center; gap: 10px; }
        .dept-name { font-size: .8rem; font-weight: 500; width: 110px; flex-shrink: 0; }
        .dept-count { font-size: .75rem; color: var(--text-muted); width: 36px; text-align: right; flex-shrink: 0; }
        .dept-bob { font-size: .75rem; color: var(--primary); font-weight: 600; width: 80px; text-align: right; flex-shrink: 0; }

        /* ── Wallet summary ── */
        .wallet-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 12px; }
        .wallet-box { background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; padding: 16px; text-align: center; }
        .wallet-box .wl { font-size: .68rem; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); margin-bottom: 6px; }
        .wallet-box .wv { font-size: 1.15rem; font-weight: 700; }

        /* ── Status donut labels ── */
        .donut-legend { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }
        .donut-item { display: flex; align-items: center; gap: 8px; font-size: .8rem; }
        .donut-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .donut-pct { font-weight: 700; margin-left: auto; }

        /* ── Carrier rating ── */
        .carrier-rate { display: flex; align-items: center; gap: 6px; }
        .carrier-bar { flex: 1; height: 4px; background: var(--surface2); border-radius: 4px; overflow: hidden; }
        .carrier-bar-fill { height: 100%; border-radius: 4px; }

        /* ── Animations ── */
        @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .kpi-card, .card { animation: fadeUp .4s ease both; }
        .kpi-card:nth-child(1) { animation-delay:.05s; } .kpi-card:nth-child(2) { animation-delay:.1s; }
        .kpi-card:nth-child(3) { animation-delay:.15s; } .kpi-card:nth-child(4) { animation-delay:.2s; }
        .kpi-card:nth-child(5) { animation-delay:.25s; } .kpi-card:nth-child(6) { animation-delay:.3s; }

        /* ── Live dot ── */
        .live-dot { width: 8px; height: 8px; background: var(--success); border-radius: 50%; animation: livePulse 2s infinite; display: inline-block; }
        @keyframes livePulse { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:.5;transform:scale(.8);} }

        /* ── Period selector form ── */
        .period-form { display: inline-flex; }
    </style>
</head>
<body>

{{-- Top Nav --}}
<nav class="topnav">
    <div class="nav-brand">
        <span class="logo">🍌</span>
        <span>Dropana <span style="color:var(--primary)">Analytics</span></span>
    </div>
    <div class="nav-links">
        <a href="{{ route('panel.admin') }}" class="nav-link">← Admin</a>
        <a href="{{ route('settlements.index') }}" class="nav-link">Liquidaciones</a>
        <a href="{{ route('reports.index') }}" class="nav-link active">Reportes</a>
    </div>
    <div class="period-selector">
        @foreach($periods as $key => $label)
            <button class="period-btn {{ $period === $key ? 'active' : '' }}"
                    onclick="changePeriod('{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>
</nav>

<div class="page">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="page-title">📊 Reportes & <span>Analytics</span></div>
            <div class="page-subtitle">
                <span class="live-dot"></span>
                Datos en vivo · Período: {{ $periods[$period] }}
                · Última actualización: {{ now()->format('H:i:s') }}
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <a href="{{ route('settlements.index') }}" style="padding:8px 16px;background:var(--surface);border:1px solid var(--border);border-radius:10px;font-size:.8rem;color:var(--text-muted);">
                🏦 Liquidaciones
            </a>
        </div>
    </div>

    {{-- ═══════════ KPI Cards ═══════════ --}}
    <div class="kpi-grid">

        <div class="kpi-card gold">
            <span class="kpi-icon">📦</span>
            <div class="kpi-label">Total Pedidos</div>
            <div class="kpi-value gold">{{ number_format($kpis['total_orders']) }}</div>
            @php $g = $kpis['orders_growth']; @endphp
            <div class="kpi-growth {{ $g > 0 ? 'up' : ($g < 0 ? 'down' : 'flat') }}">
                {{ $g >= 0 ? '▲' : '▼' }} {{ abs($g) }}% vs período anterior
            </div>
        </div>

        <div class="kpi-card green">
            <span class="kpi-icon">💰</span>
            <div class="kpi-label">Volumen Total</div>
            <div class="kpi-value green">Bs. {{ number_format($kpis['total_volume_bob'], 0) }}</div>
            @php $g2 = $kpis['volume_growth']; @endphp
            <div class="kpi-growth {{ $g2 > 0 ? 'up' : ($g2 < 0 ? 'down' : 'flat') }}">
                {{ $g2 >= 0 ? '▲' : '▼' }} {{ abs($g2) }}% vs período anterior
            </div>
        </div>

        <div class="kpi-card blue">
            <span class="kpi-icon">🏛️</span>
            <div class="kpi-label">Comisiones Plataforma</div>
            <div class="kpi-value blue">Bs. {{ number_format($kpis['platform_fees_bob'], 0) }}</div>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:8px;">Solo pedidos liquidados</div>
        </div>

        <div class="kpi-card teal">
            <span class="kpi-icon">✅</span>
            <div class="kpi-label">Tasa de Entrega</div>
            <div class="kpi-value teal">{{ $kpis['delivery_rate'] }}%</div>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:8px;">
                {{ number_format($kpis['delivered_orders']) }} entregados
            </div>
        </div>

        <div class="kpi-card purple">
            <span class="kpi-icon">🛒</span>
            <div class="kpi-label">Ticket Promedio</div>
            <div class="kpi-value purple">Bs. {{ number_format($kpis['avg_order_value_bob'], 2) }}</div>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:8px;">Por pedido COD</div>
        </div>

        <div class="kpi-card red">
            <span class="kpi-icon">↩️</span>
            <div class="kpi-label">Cancelados / Devueltos</div>
            <div class="kpi-value red">{{ number_format($kpis['cancelled_orders']) }}</div>
            @if($kpis['total_orders'] > 0)
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:8px;">
                {{ round(($kpis['cancelled_orders'] / $kpis['total_orders']) * 100, 1) }}% del total
            </div>
            @endif
        </div>

    </div>

    {{-- ═══════════ Gráfico de Línea + Estados ═══════════ --}}
    <div class="grid-7-5">

        {{-- Gráfico Serie Temporal --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">📈 Evolución Diaria (últimos 30 días)</div>
                <div style="display:flex;gap:8px;font-size:.72rem;">
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:3px;background:var(--primary);border-radius:2px;display:inline-block;"></span>Pedidos</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:3px;background:var(--success);border-radius:2px;display:inline-block;"></span>Volumen</span>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-wrap" style="height:240px;">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Donut Estados --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">🔵 Estado de Pedidos</div>
                <span style="font-size:.72rem;color:var(--text-muted);">{{ $periods[$period] }}</span>
            </div>
            <div class="card-body">
                <div class="chart-wrap" style="height:180px;">
                    <canvas id="donutChart"></canvas>
                </div>
                <div class="donut-legend" id="donutLegend"></div>
            </div>
        </div>

    </div>

    {{-- ═══════════ Por Departamento + Wallet ═══════════ --}}
    <div class="grid-7-5">

        {{-- Mapa de calor por departamento --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">🗺️ Distribución por Departamento Bolivia</div>
            </div>
            <div class="card-body">
                @php $maxDept = $byDepartment->max('orders_count') ?: 1; @endphp
                <div class="dept-list">
                @foreach($byDepartment as $dept)
                    @php $pct = round(($dept->orders_count / $maxDept) * 100); @endphp
                    <div class="dept-row">
                        <div class="dept-name">
                            @php
                            $flags = ['Santa Cruz'=>'🦅','La Paz'=>'⛰️','Cochabamba'=>'🌸','Oruro'=>'🎭','Sucre'=>'🏛️','Potosí'=>'⛏️','Tarija'=>'🍇','Beni'=>'🌿','Pando'=>'🐊'];
                            @endphp
                            {{ $flags[$dept->shipping_department] ?? '📍' }} {{ $dept->shipping_department }}
                        </div>
                        <div class="bar-wrap">
                            <div class="bar-fill" style="width:{{ $pct }}%;background:linear-gradient(90deg,var(--primary),#FF8C00);"></div>
                        </div>
                        <div class="dept-count">{{ $dept->orders_count }}</div>
                        <div class="dept-bob">Bs. {{ number_format($dept->volume_bob, 0) }}</div>
                    </div>
                @endforeach
                @if($byDepartment->isEmpty())
                    <p style="color:var(--text-muted);font-size:.85rem;text-align:center;padding:20px 0;">Sin datos para el período seleccionado.</p>
                @endif
                </div>
            </div>
        </div>

        {{-- Billeteras + Liquidaciones --}}
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">💳 Billeteras (BOB)</div>
                </div>
                <div class="card-body">
                    <div class="wallet-grid">
                        <div class="wallet-box">
                            <div class="wl">Disponible</div>
                            <div class="wv" style="color:var(--success)">Bs. {{ number_format($walletSummary['total_available_bob'], 0) }}</div>
                        </div>
                        <div class="wallet-box">
                            <div class="wl">Pendiente</div>
                            <div class="wv" style="color:var(--warning)">Bs. {{ number_format($walletSummary['total_pending_bob'], 0) }}</div>
                        </div>
                        <div class="wallet-box">
                            <div class="wl">Retirado</div>
                            <div class="wv" style="color:var(--text-muted)">Bs. {{ number_format($walletSummary['total_withdrawn_bob'], 0) }}</div>
                        </div>
                        <div class="wallet-box">
                            <div class="wl">Carteras Activas</div>
                            <div class="wv" style="color:var(--info)">{{ $walletSummary['active_wallets'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">🏦 Liquidaciones Bancarias</div>
                    <a href="{{ route('settlements.index') }}" style="font-size:.72rem;color:var(--primary);">Ver todas →</a>
                </div>
                <div class="card-body">
                    <div class="wallet-grid">
                        <div class="wallet-box">
                            <div class="wl">Total Liquidado</div>
                            <div class="wv" style="color:var(--success)">Bs. {{ number_format($settlementSummary['total_liquidated_bob'], 0) }}</div>
                        </div>
                        <div class="wallet-box">
                            <div class="wl">En Proceso</div>
                            <div class="wv" style="color:var(--warning)">Bs. {{ number_format($settlementSummary['pending_bob'], 0) }}</div>
                        </div>
                        <div class="wallet-box">
                            <div class="wl">Completados</div>
                            <div class="wv" style="color:var(--success)">{{ $settlementSummary['completed_count'] }}</div>
                        </div>
                        <div class="wallet-box">
                            <div class="wl">Pendientes</div>
                            <div class="wv" style="color:var(--warning)">{{ $settlementSummary['pending_count'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════ Top Dropshippers + Top Productos ═══════════ --}}
    <div class="grid-2">

        {{-- Top Dropshippers --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">🏆 Top Dropshippers</div>
                <span style="font-size:.72rem;color:var(--text-muted);">{{ $periods[$period] }}</span>
            </div>
            <div class="card-body no-pad">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Dropshipper</th>
                                <th>Pedidos</th>
                                <th>Volumen</th>
                                <th>Ganancia</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($topDropshippers as $i => $ds)
                            <tr>
                                <td><span class="rank {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) }}">
                                    {{ $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#'.($i+1))) }}
                                </span></td>
                                <td>
                                    <div style="font-weight:600;font-size:.82rem;">{{ $ds->name }}</div>
                                    <div style="font-size:.7rem;color:var(--text-muted);">{{ $ds->city }}</div>
                                </td>
                                <td><strong>{{ $ds->orders_count }}</strong></td>
                                <td style="color:var(--primary);font-weight:600;">Bs. {{ number_format($ds->volume_bob, 0) }}</td>
                                <td style="color:var(--success);font-size:.8rem;">Bs. {{ number_format($ds->profit_bob, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">Sin datos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top Productos --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">📦 Top Productos</div>
                <span style="font-size:.72rem;color:var(--text-muted);">{{ $periods[$period] }}</span>
            </div>
            <div class="card-body no-pad">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Unidades</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($topProducts as $i => $prod)
                            <tr>
                                <td><span class="rank {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) }}">
                                    {{ $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#'.($i+1))) }}
                                </span></td>
                                <td>
                                    <div style="font-weight:600;font-size:.82rem;">{{ Str::limit($prod->name, 28) }}</div>
                                    <div style="font-size:.7rem;color:var(--text-muted);">{{ $prod->in_orders }} órdenes</div>
                                </td>
                                <td><strong>{{ number_format($prod->units_sold) }}</strong> uds</td>
                                <td style="color:var(--primary);font-weight:600;">Bs. {{ number_format($prod->revenue_bob, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Sin datos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════ Rendimiento de Carriers ═══════════ --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">🚚 Rendimiento de Carriers — DEX Bolivia Express</div>
            <span style="font-size:.72rem;color:var(--text-muted);">{{ $periods[$period] }}</span>
        </div>
        <div class="card-body no-pad">
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Carrier</th>
                            <th>Total Pedidos</th>
                            <th>Entregados</th>
                            <th>Fallidos</th>
                            <th>Tasa Éxito</th>
                            <th>Fletes (Bs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($carrierPerformance as $carrier)
                        <tr>
                            <td><strong>{{ $carrier->name }}</strong></td>
                            <td>{{ $carrier->total_orders }}</td>
                            <td style="color:var(--success);">{{ $carrier->delivered }}</td>
                            <td style="color:var(--danger);">{{ $carrier->failed }}</td>
                            <td>
                                <div class="carrier-rate">
                                    <div class="carrier-bar">
                                        <div class="carrier-bar-fill" style="width:{{ $carrier->success_rate }}%;background:{{ $carrier->success_rate >= 80 ? 'var(--success)' : ($carrier->success_rate >= 60 ? 'var(--warning)' : 'var(--danger)') }};"></div>
                                    </div>
                                    <span style="font-weight:700;font-size:.8rem;color:{{ $carrier->success_rate >= 80 ? 'var(--success)' : ($carrier->success_rate >= 60 ? 'var(--warning)' : 'var(--danger)') }};">
                                        {{ $carrier->success_rate }}%
                                    </span>
                                </div>
                            </td>
                            <td style="color:var(--primary);">Bs. {{ number_format($carrier->shipping_fees_bob, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">Sin datos de carriers para el período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>{{-- /page --}}

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- JavaScript — Chart.js Charts                                   --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<script>
// ── Datos del servidor → JS ──────────────────────────────────────
const dailySeries = @json($dailySeries->values());
const statusDist  = @json($statusDistribution);

// Rellenar serie diaria con los últimos 30 días
function buildDailySeries() {
    const result = [];
    const today  = new Date();
    const map    = {};
    dailySeries.forEach(d => { map[d.date] = d; });

    for (let i = 29; i >= 0; i--) {
        const d   = new Date(today); d.setDate(d.getDate() - i);
        const key = d.toISOString().slice(0,10);
        result.push({
            date:            key,
            orders_count:    map[key]?.orders_count    ?? 0,
            volume_bob:      map[key]?.volume_bob       ?? 0,
            delivered_count: map[key]?.delivered_count  ?? 0,
        });
    }
    return result;
}

const series = buildDailySeries();
const labels = series.map(d => {
    const [y,m,day] = d.date.split('-');
    return `${day}/${m}`;
});

// ── Paletas de colores ──────────────────────────────────────────
const GOLD   = '#F4B400';
const GREEN  = '#22C55E';
const BLUE   = '#3B82F6';
const RED    = '#EF4444';
const PURPLE = '#8B5CF6';
const TEAL   = '#14B8A6';
const ORANGE = '#F97316';
const PINK   = '#EC4899';

Chart.defaults.color = '#94A3B8';
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size   = 11;

// ── 1. Gráfico de Línea — serie diaria ──────────────────────────
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Pedidos',
                data: series.map(d => d.orders_count),
                borderColor: GOLD,
                backgroundColor: 'rgba(244,180,0,.08)',
                fill: true, tension: .4, pointRadius: 0,
                pointHoverRadius: 5, pointHoverBackgroundColor: GOLD,
                yAxisID: 'y',
            },
            {
                label: 'Volumen (Bs.)',
                data: series.map(d => parseFloat(d.volume_bob)),
                borderColor: GREEN,
                backgroundColor: 'rgba(34,197,94,.06)',
                fill: true, tension: .4, pointRadius: 0,
                pointHoverRadius: 5, pointHoverBackgroundColor: GREEN,
                yAxisID: 'y1',
            },
        ],
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1F2937', borderColor: 'rgba(255,255,255,.1)',
                borderWidth: 1, padding: 12,
                callbacks: {
                    label: ctx => ctx.datasetIndex === 0
                        ? ` Pedidos: ${ctx.raw}`
                        : ` Volumen: Bs. ${Number(ctx.raw).toLocaleString('es-BO')}`
                }
            }
        },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { maxTicksLimit: 10 } },
            y:  { position: 'left',  grid: { color: 'rgba(255,255,255,.04)' }, beginAtZero: true },
            y1: { position: 'right', grid: { drawOnChartArea: false }, beginAtZero: true,
                  ticks: { callback: v => 'Bs.' + Number(v).toLocaleString('es-BO') } }
        }
    }
});

// ── 2. Donut — Estado de pedidos ────────────────────────────────
const statusColors = {
    'pending_confirmation': ORANGE, 'confirmed': BLUE,
    'ready_to_pack': PURPLE, 'dispatched': TEAL,
    'in_transit': '#60A5FA', 'delivered': GREEN,
    'settled': '#16A34A', 'cancelled': RED,
    'returned': PINK, 'failed_delivery': '#F97316',
};
const statusLabels = {
    'pending_confirmation': 'Pendiente', 'confirmed': 'Confirmado',
    'ready_to_pack': 'Listo a empacar', 'dispatched': 'Despachado',
    'in_transit': 'En tránsito', 'delivered': 'Entregado',
    'settled': 'Liquidado', 'cancelled': 'Cancelado',
    'returned': 'Devuelto', 'failed_delivery': 'Entrega fallida',
};

const donutColors = statusDist.map(d => statusColors[d.status] || '#64748B');
const donutLabels = statusDist.map(d => statusLabels[d.status] || d.status);
const donutData   = statusDist.map(d => d.count);
const donutTotal  = donutData.reduce((s,v) => s+v, 0);

new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: donutLabels,
        datasets: [{
            data: donutData,
            backgroundColor: donutColors,
            borderWidth: 0, hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1F2937', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1,
                callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} (${donutTotal > 0 ? Math.round(ctx.raw/donutTotal*100) : 0}%)` }
            }
        }
    }
});

// Leyenda del donut
const legend = document.getElementById('donutLegend');
statusDist.forEach((d, i) => {
    if (i >= 6) return; // máximo 6 items
    const pct = donutTotal > 0 ? Math.round(d.count / donutTotal * 100) : 0;
    legend.innerHTML += `
        <div class="donut-item">
            <span class="donut-dot" style="background:${donutColors[i]};"></span>
            <span style="color:#94A3B8;">${donutLabels[i]}</span>
            <span class="donut-pct" style="color:${donutColors[i]};">${pct}%</span>
        </div>`;
});

// ── Cambio de período ────────────────────────────────────────────
function changePeriod(p) {
    const url = new URL(window.location);
    url.searchParams.set('period', p);
    window.location = url.toString();
}

// ── Auto-refresh de KPIs cada 60s ───────────────────────────────
setInterval(async () => {
    try {
        const r = await fetch(`/reports/kpis?period={{ $period }}`);
        const d = await r.json();
        // Actualizar solo el timestamp (sin re-renderizar todo)
        document.querySelector('.page-subtitle').innerHTML =
            `<span class="live-dot"></span> Datos en vivo · Período: {{ $periods[$period] }} · Última actualización: ${new Date().toLocaleTimeString('es-BO')}`;
    } catch(e) { /* silencioso */ }
}, 60000);
</script>

</body>
</html>
