<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidaciones Bancarias — Dropana Bolivia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:   #F4B400;
            --primary-dark: #D09800;
            --bg:        #0F1117;
            --surface:   #1A1D2E;
            --surface2:  #242740;
            --border:    rgba(255,255,255,0.08);
            --text:      #E8E9F0;
            --text-muted:#8B90B0;
            --success:   #22C55E;
            --warning:   #EAB308;
            --danger:    #EF4444;
            --info:      #3B82F6;
            --radius:    14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* ── Layout ── */
        .page { max-width: 1280px; margin: 0 auto; padding: 32px 24px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .page-title { font-size: 1.5rem; font-weight: 700; }
        .page-title span { color: var(--primary); }

        /* ── KPI Cards ── */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .kpi-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 24px;
            transition: transform .2s, box-shadow .2s;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.4); }
        .kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin-bottom: 8px; }
        .kpi-value { font-size: 1.6rem; font-weight: 700; }
        .kpi-value.gold { color: var(--primary); }
        .kpi-value.green { color: var(--success); }
        .kpi-value.blue { color: var(--info); }
        .kpi-sub { font-size: .75rem; color: var(--text-muted); margin-top: 4px; }

        /* ── Alert ── */
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-size: .9rem; display: flex; align-items: center; gap: 10px; }
        .alert-warning { background: rgba(234,179,8,.12); border: 1px solid rgba(234,179,8,.3); color: #FDE047; }
        .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #86EFAC; }
        .alert-error   { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3); color: #FCA5A5; }

        /* ── Filters ── */
        .filters-bar {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 16px 20px;
            display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
            margin-bottom: 20px;
        }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
        .filter-group select,
        .filter-group input  {
            background: var(--surface2); border: 1px solid var(--border);
            color: var(--text); border-radius: 8px; padding: 8px 12px;
            font-size: .875rem; font-family: inherit;
        }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 9px; font-size: .875rem; font-weight: 600; cursor: pointer; border: none; transition: all .2s; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #000; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 6px 12px; font-size: .8rem; border-radius: 7px; }
        .btn-success { background: rgba(34,197,94,.15); color: var(--success); border: 1px solid rgba(34,197,94,.3); }
        .btn-success:hover { background: rgba(34,197,94,.3); }
        .btn-danger { background: rgba(239,68,68,.15); color: var(--danger); border: 1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background: rgba(239,68,68,.3); }

        /* ── Table ── */
        .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .table-header h3 { font-size: 1rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--surface2); }
        th { padding: 12px 16px; text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); font-weight: 600; }
        td { padding: 14px 16px; border-top: 1px solid var(--border); font-size: .875rem; }
        tr:hover td { background: rgba(255,255,255,.03); }

        /* ── Badge ── */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .badge-warning  { background: rgba(234,179,8,.15);  color: #FDE047; border: 1px solid rgba(234,179,8,.3); }
        .badge-info     { background: rgba(59,130,246,.15); color: #93C5FD; border: 1px solid rgba(59,130,246,.3); }
        .badge-success  { background: rgba(34,197,94,.15);  color: #86EFAC; border: 1px solid rgba(34,197,94,.3); }
        .badge-danger   { background: rgba(239,68,68,.15);  color: #FCA5A5; border: 1px solid rgba(239,68,68,.3); }

        /* ── Bank Icons (unicode) ── */
        .bank-icon { font-size: 1.1rem; }

        /* ── Modal ── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background: var(--surface); border:1px solid var(--border); border-radius: 18px; padding:28px 32px; width:min(520px, 95vw); position:relative; }
        .modal h2 { font-size:1.1rem; font-weight:700; margin-bottom:20px; }
        .form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
        .form-group label { font-size:.8rem; font-weight:600; color:var(--text-muted); }
        .form-group select,
        .form-group input,
        .form-group textarea {
            background:var(--surface2); border:1px solid var(--border);
            color:var(--text); border-radius:8px; padding:10px 14px;
            font-size:.875rem; font-family:inherit; resize:vertical;
        }
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus { outline:none; border-color:var(--primary); }
        .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        .close-modal { position:absolute; top:16px; right:16px; background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:1.2rem; }

        /* ── Pagination ── */
        .pagination-wrap { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }

        /* ── Bank Grid ── */
        .bank-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-bottom: 28px; }
        .bank-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
            padding: 16px; text-align: center; cursor: pointer; transition: all .2s;
        }
        .bank-card:hover { border-color: var(--primary); background: var(--surface2); }
        .bank-card .bank-name { font-size: .8rem; font-weight: 600; margin-top: 8px; }
        .bank-card .bank-amount { font-size: .75rem; color: var(--text-muted); margin-top: 2px; }
        .bank-emoji { font-size: 1.8rem; }
    </style>
</head>
<body>

<div class="page">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="page-title">🏦 Liquidaciones <span>Bancarias Bolivia</span></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px;">Gestión de pagos y retiros por banco — Bs. BOB</div>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('modalNewBatch').classList.add('open')" id="btn-new-batch">
            + Nuevo Lote
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">⚠️ {{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">❌ {{ $errors->first('error') }}</div>
    @endif

    {{-- Alerta de retiros pendientes sin batch --}}
    @if($pendingCount > 0)
        <div class="alert alert-warning">
            💰 Hay <strong>{{ $pendingCount }}</strong> retiro(s) pendientes sin lote asignado. Crea un nuevo lote para procesarlos.
        </div>
    @endif

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Lotes</div>
            <div class="kpi-value gold">{{ $summary['total_batches'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Pendientes</div>
            <div class="kpi-value" style="color:var(--warning)">{{ $summary['pending_batches'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Completados</div>
            <div class="kpi-value green">{{ $summary['completed_batches'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Liquidado</div>
            <div class="kpi-value green">Bs. {{ number_format($summary['total_liquidated_bob'], 2) }}</div>
            <div class="kpi-sub">BOB acumulado</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Monto Pendiente</div>
            <div class="kpi-value" style="color:var(--warning)">Bs. {{ number_format($summary['pending_amount_bob'], 2) }}</div>
            <div class="kpi-sub">En proceso / Pendiente</div>
        </div>
    </div>

    {{-- Resumen por Banco --}}
    @if(!empty($summary['by_bank']) && $summary['by_bank']->isNotEmpty())
    <div class="bank-grid">
        @php
        $bankEmojis = ['BNB'=>'🏛️','Banco Unión'=>'🏦','BCP'=>'💳','Mercantil'=>'🔵','QR Simple'=>'📱'];
        @endphp
        @foreach($summary['by_bank'] as $bank => $data)
        <div class="bank-card" onclick="selectBank('{{ $bank }}')">
            <div class="bank-emoji">{{ $bankEmojis[$bank] ?? '🏦' }}</div>
            <div class="bank-name">{{ $bank }}</div>
            <div class="bank-amount">{{ $data['count'] }} lotes — Bs. {{ number_format($data['total_bob'], 2) }}</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" action="{{ route('settlements.index') }}" class="filters-bar">
        <div class="filter-group">
            <label>Banco</label>
            <select name="bank">
                <option value="">Todos</option>
                @foreach($banks as $key => $label)
                    <option value="{{ $key }}" @selected(request('bank') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label>Estado</label>
            <select name="status">
                <option value="">Todos</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label>Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="filter-group">
            <label>Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">🔍 Filtrar</button>
        <a href="{{ route('settlements.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
    </form>

    {{-- Tabla de Lotes --}}
    <div class="table-card">
        <div class="table-header">
            <h3>Lotes de Liquidación</h3>
            <span style="font-size:.8rem;color:var(--text-muted);">{{ $batches->total() }} registros</span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nro. Lote</th>
                        <th>Banco</th>
                        <th>Retiros</th>
                        <th>Total Bs.</th>
                        <th>Estado</th>
                        <th>Referencia Bancaria</th>
                        <th>Procesado por</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <code style="color:var(--primary);font-size:.8rem;">{{ $batch->batch_number }}</code>
                        </td>
                        <td>
                            {{ $bankEmojis[$batch->bank_name] ?? '🏦' }}
                            {{ $batch->bank_name }}
                        </td>
                        <td style="text-align:center;">
                            <strong>{{ $batch->total_withdrawals_count }}</strong>
                        </td>
                        <td>
                            <strong style="color:var(--primary);">Bs. {{ number_format($batch->total_amount_bob, 2) }}</strong>
                        </td>
                        <td>
                            <span class="badge {{ $batch->status_badge_class }}">
                                {{ $batch->status_label }}
                            </span>
                        </td>
                        <td style="color:var(--text-muted);font-size:.8rem;">
                            {{ $batch->bank_reference_number ?? '—' }}
                        </td>
                        <td style="font-size:.8rem;">
                            {{ $batch->processedBy?->name ?? '—' }}
                        </td>
                        <td style="font-size:.75rem;color:var(--text-muted);">
                            {{ $batch->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="{{ route('settlements.show', $batch->id) }}" class="btn btn-ghost btn-sm">👁</a>

                                @if($batch->isPending())
                                    <form method="POST" action="{{ route('settlements.processing', $batch->id) }}" style="display:inline;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm" style="background:rgba(59,130,246,.15);color:#93C5FD;border:1px solid rgba(59,130,246,.3);"
                                                onclick="return confirm('¿Marcar como en proceso?')">🔄</button>
                                    </form>

                                    <button class="btn btn-sm btn-danger"
                                            onclick="openRejectModal('{{ $batch->id }}', '{{ $batch->batch_number }}')">✖</button>
                                @endif

                                @if($batch->status === 'processing')
                                    <button class="btn btn-sm btn-success"
                                            onclick="openCompleteModal('{{ $batch->id }}', '{{ $batch->batch_number }}')">✔ Completar</button>
                                @endif

                                @if($batch->receipt_url)
                                    <a href="{{ $batch->receipt_url }}" target="_blank" class="btn btn-ghost btn-sm">📄</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                            No hay lotes de liquidación registrados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
        <div class="pagination-wrap">{{ $batches->links() }}</div>
        @endif
    </div>

</div>{{-- /page --}}

{{-- ══ MODAL: Nuevo Lote ══ --}}
<div class="modal-overlay" id="modalNewBatch">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalNewBatch').classList.remove('open')">✕</button>
        <h2>🏦 Crear Nuevo Lote de Liquidación</h2>
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:20px;">
            Se agruparán todos los retiros pendientes sin lote asignado.
        </p>
        <form method="POST" action="{{ route('settlements.store') }}" id="form-new-batch">
            @csrf
            <div class="form-group">
                <label>Banco Destino *</label>
                <select name="bank_name" id="select-bank-name" required>
                    <option value="">— Seleccionar banco —</option>
                    @foreach($banks as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Notas (opcional)</label>
                <textarea name="notes" rows="2" placeholder="Observaciones del lote..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalNewBatch').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Lote</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: Completar Lote ══ --}}
<div class="modal-overlay" id="modalComplete">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalComplete').classList.remove('open')">✕</button>
        <h2>✅ Completar Liquidación</h2>
        <form method="POST" id="form-complete" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="form-group">
                <label>N° Referencia Bancaria *</label>
                <input type="text" name="bank_reference_number" placeholder="Ej: BNB-TRX-20261028-00123" required>
            </div>
            <div class="form-group">
                <label>Comprobante / Voucher QR (PDF, JPG, PNG — máx 5MB)</label>
                <input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="form-group">
                <label>Notas adicionales</label>
                <textarea name="notes" rows="2" placeholder="Observaciones..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalComplete').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-success">Confirmar Pago</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: Rechazar Lote ══ --}}
<div class="modal-overlay" id="modalReject">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalReject').classList.remove('open')">✕</button>
        <h2>❌ Rechazar Lote</h2>
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:16px;">Los retiros serán liberados para un nuevo lote.</p>
        <form method="POST" id="form-reject">
            @csrf @method('DELETE')
            <div class="form-group">
                <label>Motivo del rechazo *</label>
                <textarea name="reason" rows="3" placeholder="Indique el motivo..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalReject').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar Lote</button>
            </div>
        </form>
    </div>
</div>

<script>
function selectBank(bank) {
    document.getElementById('select-bank-name').value = bank;
    document.getElementById('modalNewBatch').classList.add('open');
}

function openCompleteModal(id, batchNum) {
    document.getElementById('form-complete').action = `/settlements/${id}/complete`;
    document.querySelector('#modalComplete h2').textContent = `✅ Completar — ${batchNum}`;
    document.getElementById('modalComplete').classList.add('open');
}

function openRejectModal(id, batchNum) {
    document.getElementById('form-reject').action = `/settlements/${id}/reject`;
    document.querySelector('#modalReject h2').textContent = `❌ Rechazar — ${batchNum}`;
    document.getElementById('modalReject').classList.add('open');
}

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});
</script>
</body>
</html>
