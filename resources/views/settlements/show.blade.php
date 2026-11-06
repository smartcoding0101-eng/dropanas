<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lote {{ $batch->batch_number }} — Dropana Bolivia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:#F4B400; --primary-dark:#D09800;
            --bg:#0F1117; --surface:#1A1D2E; --surface2:#242740;
            --border:rgba(255,255,255,0.08); --text:#E8E9F0; --text-muted:#8B90B0;
            --success:#22C55E; --warning:#EAB308; --danger:#EF4444; --info:#3B82F6;
            --radius:14px;
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .page { max-width:1100px; margin:0 auto; padding:32px 24px; }

        /* Header */
        .back-btn { display:inline-flex; align-items:center; gap:6px; color:var(--text-muted); text-decoration:none; font-size:.875rem; margin-bottom:24px; transition:color .2s; }
        .back-btn:hover { color:var(--primary); }
        .page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; gap:16px; flex-wrap:wrap; }
        .batch-number { font-family:monospace; font-size:.9rem; color:var(--primary); background:rgba(244,180,0,.1); padding:4px 12px; border-radius:6px; border:1px solid rgba(244,180,0,.2); }
        .page-title { font-size:1.5rem; font-weight:700; margin:8px 0 4px; }

        /* Status Timeline */
        .timeline { display:flex; align-items:center; gap:0; margin-bottom:28px; overflow-x:auto; }
        .timeline-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:100px; position:relative; }
        .timeline-step:not(:last-child)::after {
            content:''; position:absolute; top:16px; left:50%; width:100%; height:2px;
            background:var(--border);
        }
        .timeline-step.done::after { background:var(--success); }
        .timeline-dot {
            width:32px; height:32px; border-radius:50%; border:2px solid var(--border);
            background:var(--surface2); display:flex; align-items:center; justify-content:center;
            font-size:.9rem; z-index:1; transition:all .3s;
        }
        .timeline-step.done .timeline-dot   { border-color:var(--success); background:rgba(34,197,94,.15); }
        .timeline-step.active .timeline-dot { border-color:var(--primary); background:rgba(244,180,0,.15); animation:pulse 2s infinite; }
        .timeline-step.rejected .timeline-dot { border-color:var(--danger); background:rgba(239,68,68,.15); }
        @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(244,180,0,.4)} 50%{box-shadow:0 0 0 8px rgba(244,180,0,0)} }
        .timeline-label { font-size:.7rem; margin-top:8px; color:var(--text-muted); text-align:center; }
        .timeline-step.done .timeline-label   { color:var(--success); }
        .timeline-step.active .timeline-label { color:var(--primary); font-weight:600; }

        /* Grid */
        .info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:28px; }
        .info-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px 24px; }
        .info-card h4 { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:16px; }
        .info-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); font-size:.875rem; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:var(--text-muted); }
        .info-value { font-weight:500; text-align:right; }

        /* Buttons */
        .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:9px; font-size:.875rem; font-weight:600; cursor:pointer; border:none; transition:all .2s; text-decoration:none; }
        .btn-primary { background:var(--primary); color:#000; }
        .btn-ghost   { background:transparent; color:var(--text-muted); border:1px solid var(--border); }
        .btn-ghost:hover { border-color:var(--primary); color:var(--primary); }
        .btn-success { background:rgba(34,197,94,.15); color:var(--success); border:1px solid rgba(34,197,94,.3); }
        .btn-success:hover { background:rgba(34,197,94,.3); }
        .btn-info    { background:rgba(59,130,246,.15); color:#93C5FD; border:1px solid rgba(59,130,246,.3); }
        .btn-info:hover { background:rgba(59,130,246,.3); }
        .btn-danger  { background:rgba(239,68,68,.15); color:var(--danger); border:1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background:rgba(239,68,68,.3); }

        /* Badge */
        .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:600; }
        .badge-warning  { background:rgba(234,179,8,.15);  color:#FDE047; border:1px solid rgba(234,179,8,.3); }
        .badge-info     { background:rgba(59,130,246,.15); color:#93C5FD; border:1px solid rgba(59,130,246,.3); }
        .badge-success  { background:rgba(34,197,94,.15);  color:#86EFAC; border:1px solid rgba(34,197,94,.3); }
        .badge-danger   { background:rgba(239,68,68,.15);  color:#FCA5A5; border:1px solid rgba(239,68,68,.3); }

        /* Table */
        .table-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
        .table-header { padding:18px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        table { width:100%; border-collapse:collapse; }
        thead { background:var(--surface2); }
        th { padding:10px 16px; text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); }
        td { padding:12px 16px; border-top:1px solid var(--border); font-size:.875rem; }

        /* Receipt preview */
        .receipt-box { background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:16px; margin-top:12px; }
        .receipt-box img { max-width:100%; border-radius:8px; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:28px 32px; width:min(500px,95vw); position:relative; }
        .modal h2 { font-size:1.1rem; font-weight:700; margin-bottom:20px; }
        .form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
        .form-group label { font-size:.8rem; font-weight:600; color:var(--text-muted); }
        .form-group input,
        .form-group textarea { background:var(--surface2); border:1px solid var(--border); color:var(--text); border-radius:8px; padding:10px 14px; font-size:.875rem; font-family:inherit; }
        .form-group input:focus,
        .form-group textarea:focus { outline:none; border-color:var(--primary); }
        .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        .close-modal { position:absolute; top:16px; right:16px; background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:1.2rem; }
        .alert-error { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:.875rem; background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.3); color:#FCA5A5; }
    </style>
</head>
<body>
<div class="page">

    <a href="{{ route('settlements.index') }}" class="back-btn">← Volver a Liquidaciones</a>

    {{-- Flash --}}
    @if(session('success'))
        <div style="padding:14px 20px;border-radius:10px;margin-bottom:20px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86EFAC;">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert-error">❌ {{ $errors->first() }}</div>
    @endif

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <div class="batch-number">{{ $batch->batch_number }}</div>
            <div class="page-title">Lote de Liquidación</div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:6px;">
                <span class="badge {{ $batch->status_badge_class }}">{{ $batch->status_label }}</span>
                <span style="font-size:.8rem;color:var(--text-muted);">{{ $batch->bank_name }}</span>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if($batch->isPending())
                <form method="POST" action="{{ route('settlements.processing', $batch->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-info">🔄 Marcar En Proceso</button>
                </form>
                <button class="btn btn-danger" onclick="document.getElementById('modalReject').classList.add('open')">✖ Rechazar</button>
            @endif

            @if($batch->status === 'processing')
                <button class="btn btn-success" onclick="document.getElementById('modalComplete').classList.add('open')">
                    ✔ Completar Liquidación
                </button>
            @endif

            @if($batch->receipt_url)
                <a href="{{ $batch->receipt_url }}" target="_blank" class="btn btn-ghost">📄 Ver Comprobante</a>
            @endif
        </div>
    </div>

    {{-- Timeline de Estado --}}
    @php
        $steps = [
            ['key'=>'pending',    'label'=>'Pendiente',   'icon'=>'⏳'],
            ['key'=>'processing', 'label'=>'En Proceso',  'icon'=>'🔄'],
            ['key'=>'completed',  'label'=>'Completado',  'icon'=>'✅'],
        ];
        $order = ['pending'=>0,'processing'=>1,'completed'=>2,'rejected'=>3];
        $currentOrder = $order[$batch->status] ?? 0;
    @endphp
    <div class="timeline">
        @foreach($steps as $step)
            @php
                $stepOrder = $order[$step['key']] ?? 0;
                $isDone    = $stepOrder < $currentOrder && $batch->status !== 'rejected';
                $isActive  = $step['key'] === $batch->status;
                $isRejected= $batch->status === 'rejected' && $step['key'] === 'pending';
                $cls = $isDone ? 'done' : ($isActive ? 'active' : ($isRejected ? 'rejected' : ''));
            @endphp
            <div class="timeline-step {{ $cls }}">
                <div class="timeline-dot">{{ $step['icon'] }}</div>
                <div class="timeline-label">{{ $step['label'] }}</div>
            </div>
        @endforeach
        @if($batch->status === 'rejected')
            <div class="timeline-step rejected">
                <div class="timeline-dot">❌</div>
                <div class="timeline-label">Rechazado</div>
            </div>
        @endif
    </div>

    {{-- Info Grid --}}
    <div class="info-grid">

        {{-- Datos del lote --}}
        <div class="info-card">
            <h4>📋 Datos del Lote</h4>
            <div class="info-row">
                <span class="info-label">Banco</span>
                <span class="info-value">{{ $batch->bank_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Retiros</span>
                <span class="info-value">{{ $batch->total_withdrawals_count }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Monto Total</span>
                <span class="info-value" style="color:var(--primary);font-size:1.1rem;">
                    Bs. {{ number_format($batch->total_amount_bob, 2) }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Referencia Bancaria</span>
                <span class="info-value">{{ $batch->bank_reference_number ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Procesado por</span>
                <span class="info-value">{{ $batch->processedBy?->name ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha Creación</span>
                <span class="info-value">{{ $batch->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @if($batch->completed_at)
            <div class="info-row">
                <span class="info-label">Completado</span>
                <span class="info-value" style="color:var(--success);">{{ $batch->completed_at->format('d/m/Y H:i') }}</span>
            </div>
            @endif
        </div>

        {{-- Notas y Comprobante --}}
        <div class="info-card">
            <h4>📝 Notas y Comprobante</h4>
            @if($batch->notes)
                <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:16px;">{{ $batch->notes }}</p>
            @else
                <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:16px;font-style:italic;">Sin notas.</p>
            @endif

            @if($batch->receipt_url)
                <div class="receipt-box">
                    <p style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:8px;">Comprobante / Voucher</p>
                    @php $ext = pathinfo($batch->receipt_file_path, PATHINFO_EXTENSION); @endphp
                    @if(in_array(strtolower($ext), ['jpg','jpeg','png']))
                        <img src="{{ $batch->receipt_url }}" alt="Comprobante bancario">
                    @else
                        <a href="{{ $batch->receipt_url }}" target="_blank" class="btn btn-ghost" style="margin-top:4px;">
                            📄 Abrir PDF
                        </a>
                    @endif
                </div>
            @else
                <div class="receipt-box" style="text-align:center;color:var(--text-muted);font-size:.875rem;">
                    Sin comprobante adjunto.
                </div>
            @endif
        </div>

    </div>

    {{-- Tabla de transacciones del lote --}}
    <div class="table-card">
        <div class="table-header">
            <h3>💰 Retiros Incluidos ({{ $batch->walletTransactions->count() }})</h3>
            <span style="font-size:.8rem;color:var(--primary);font-weight:600;">
                Total: Bs. {{ number_format($batch->walletTransactions->sum(fn($t)=>abs($t->amount)), 2) }}
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Dropshipper / Usuario</th>
                        <th>Descripción</th>
                        <th>Monto Bs.</th>
                        <th>Saldo Post-TX</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batch->walletTransactions as $tx)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $tx->wallet?->user?->name ?? '—' }}</div>
                            <div style="font-size:.75rem;color:var(--text-muted);">{{ $tx->wallet?->user?->email ?? '' }}</div>
                        </td>
                        <td style="color:var(--text-muted);font-size:.8rem;">{{ $tx->description ?? '—' }}</td>
                        <td>
                            <strong style="color:var(--danger);">Bs. {{ number_format(abs($tx->amount), 2) }}</strong>
                        </td>
                        <td style="font-size:.8rem;color:var(--text-muted);">
                            Bs. {{ number_format($tx->balance_after, 2) }}
                        </td>
                        <td style="font-size:.75rem;color:var(--text-muted);">
                            {{ $tx->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);">
                            Sin transacciones en este lote.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ══ MODAL: Completar ══ --}}
<div class="modal-overlay" id="modalComplete">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalComplete').classList.remove('open')">✕</button>
        <h2>✅ Confirmar Liquidación Completada</h2>
        <form method="POST" action="{{ route('settlements.complete', $batch->id) }}" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="form-group">
                <label>N° Referencia Bancaria *</label>
                <input type="text" name="bank_reference_number" placeholder="Ej: BNB-TRX-20261028-00123" required>
            </div>
            <div class="form-group">
                <label>Comprobante / Voucher QR (PDF, JPG, PNG — máx. 5MB)</label>
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

{{-- ══ MODAL: Rechazar ══ --}}
<div class="modal-overlay" id="modalReject">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalReject').classList.remove('open')">✕</button>
        <h2>❌ Rechazar Lote {{ $batch->batch_number }}</h2>
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:16px;">Los retiros se liberarán para crear un nuevo lote.</p>
        <form method="POST" action="{{ route('settlements.reject', $batch->id) }}">
            @csrf @method('DELETE')
            <div class="form-group">
                <label>Motivo del rechazo *</label>
                <textarea name="reason" rows="3" placeholder="Indique el motivo..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalReject').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});
</script>
</body>
</html>
