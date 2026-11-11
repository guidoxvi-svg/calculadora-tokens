<?php
/* ============================================================
   CALCULADORA DE TOKENS -> PESOS COLOMBIANOS (COP)
   Stripchat, Chaturbate y Streamate
   ------------------------------------------------------------
   Edita los valores por defecto aquí. TODOS se pueden cambiar
   también desde el formulario sin tocar el código.
   ============================================================ */

$config = [
    'trm'               => 4000,   // 1 USD en COP (actualízala cada día)
    'porcentaje_monitor' => 20,    // % del NETO que se queda el monitor/colaborador
    'plataformas' => [
        // valor_token: valor de 1 token en USD ANTES de la retención
        // retencion:   % que se queda la página
        'stripchat'  => ['nombre' => 'Stripchat',  'valor_token' => 0.10, 'retencion' => 50],
        'chaturbate' => ['nombre' => 'Chaturbate', 'valor_token' => 0.10, 'retencion' => 50],
        'streamate'  => ['nombre' => 'Streamate',  'valor_token' => 0.10, 'retencion' => 50],
    ],
];

/* ---------- Utilidades ---------- */
function num($v, $def = 0.0) {
    $v = str_replace(',', '.', trim((string)$v));
    return is_numeric($v) ? max(0.0, (float)$v) : $def;
}
function pct($v, $def = 0.0) {
    return min(100.0, num($v, $def));
}
function cop($v) { return '$ ' . number_format($v, 0, ',', '.') . ' COP'; }
function usd($v) { return 'US$ ' . number_format($v, 2, '.', ','); }
function h($s)   { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- Valores actuales (POST o por defecto) ---------- */
$enviado = ($_SERVER['REQUEST_METHOD'] === 'POST');
$trm     = $enviado ? num($_POST['trm'] ?? 0, $config['trm']) : $config['trm'];
$pctMon  = $enviado ? pct($_POST['pct_monitor'] ?? 0, $config['porcentaje_monitor']) : $config['porcentaje_monitor'];

$filas = [];
$tot = ['tokens' => 0, 'bruto' => 0, 'retenido' => 0, 'neto' => 0, 'monitor' => 0, 'modelo' => 0];

foreach ($config['plataformas'] as $key => $p) {
    $tokens = $enviado ? num($_POST['tokens'][$key] ?? 0) : 0;
    $valor  = $enviado ? num($_POST['valor'][$key] ?? 0, $p['valor_token']) : $p['valor_token'];
    $ret    = $enviado ? pct($_POST['ret'][$key] ?? 0, $p['retencion']) : $p['retencion'];

    $bruto    = $tokens * $valor;            // USD antes de retención
    $retenido = $bruto * ($ret / 100);       // lo que se queda la página
    $neto     = $bruto - $retenido;          // lo que paga la página
    $monitor  = $neto * ($pctMon / 100);     // parte del colaborador
    $modelo   = $neto - $monitor;            // parte de la modelo

    $filas[$key] = compact('tokens', 'valor', 'ret', 'bruto', 'retenido', 'neto', 'monitor', 'modelo') + ['nombre' => $p['nombre']];

    $tot['tokens']   += $tokens;
    $tot['bruto']    += $bruto;
    $tot['retenido'] += $retenido;
    $tot['neto']     += $neto;
    $tot['monitor']  += $monitor;
    $tot['modelo']   += $modelo;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calculadora de Tokens a COP</title>
<style>
  :root{
    --bg:#f5f6fa; --card:#ffffff; --txt:#1c1e26; --muted:#6b7080; --line:#e3e5ee;
    --accent:#6c4cf1; --accent2:#18a06b; --warn:#d9534f;
  }
  @media (prefers-color-scheme: dark){
    :root{ --bg:#14151b; --card:#1d1f28; --txt:#eceef6; --muted:#9a9fb3; --line:#2c2f3d; }
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--txt);padding:16px}
  .wrap{max-width:980px;margin:0 auto}
  h1{font-size:1.5rem;margin:.2rem 0 1rem}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:16px}
  .grid{display:grid;gap:12px}
  .g2{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  label{display:block;font-size:.8rem;color:var(--muted);margin-bottom:4px}
  input{width:100%;padding:10px;border:1px solid var(--line);border-radius:8px;background:transparent;color:var(--txt);font-size:1rem}
  input:focus{outline:2px solid var(--accent);border-color:transparent}
  h2{font-size:1.05rem;margin:0 0 10px}
  .plat{border-top:1px dashed var(--line);padding-top:12px;margin-top:12px}
  .plat:first-of-type{border-top:0;margin-top:0;padding-top:0}
  .grid3{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
  button{background:var(--accent);color:#fff;border:0;padding:12px 22px;border-radius:10px;font-size:1rem;cursor:pointer;font-weight:600}
  button.sec{background:transparent;color:var(--muted);border:1px solid var(--line);margin-left:8px}
  .tablewrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:.92rem;min-width:640px}
  th,td{padding:9px 8px;text-align:right;border-bottom:1px solid var(--line);white-space:nowrap}
  th:first-child,td:first-child{text-align:left}
  th{color:var(--muted);font-weight:600;font-size:.78rem;text-transform:uppercase}
  tfoot td{font-weight:700;border-bottom:0}
  .big{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:14px}
  .kpi{border:1px solid var(--line);border-radius:12px;padding:14px}
  .kpi small{color:var(--muted);display:block;margin-bottom:4px}
  .kpi b{font-size:1.3rem}
  .kpi.mon b{color:var(--accent)} .kpi.mod b{color:var(--accent2)}
  .note{font-size:.8rem;color:var(--muted);line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
  <h1>🧮 Calculadora de Tokens → Pesos Colombianos</h1>

  <form method="post" autocomplete="off">
    <div class="card">
      <h2>Parámetros generales</h2>
      <div class="grid g2">
        <div>
          <label>TRM (1 USD en COP)</label>
          <input type="number" step="0.01" min="0" name="trm" value="<?= h($trm) ?>">
        </div>
        <div>
          <label>% del monitor / colaborador (sobre lo que paga la página)</label>
          <input type="number" step="0.01" min="0" max="100" name="pct_monitor" value="<?= h($pctMon) ?>">
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Tokens por página</h2>
      <?php foreach ($filas as $key => $f): ?>
      <div class="plat">
        <strong><?= h($f['nombre']) ?></strong>
        <div class="grid3" style="margin-top:8px">
          <div>
            <label>Tokens ganados</label>
            <input type="number" step="1" min="0" name="tokens[<?= h($key) ?>]" value="<?= h($f['tokens']) ?>">
          </div>
          <div>
            <label>Valor del token (USD)</label>
            <input type="number" step="0.0001" min="0" name="valor[<?= h($key) ?>]" value="<?= h($f['valor']) ?>">
          </div>
          <div>
            <label>% que retiene la página</label>
            <input type="number" step="0.01" min="0" max="100" name="ret[<?= h($key) ?>]" value="<?= h($f['ret']) ?>">
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:16px">
        <button type="submit">Calcular</button>
        <button type="button" class="sec" onclick="window.location.href=window.location.pathname">Limpiar</button>
      </div>
    </div>
  </form>

  <?php if ($enviado): ?>
  <div class="card">
    <h2>Resultado por página</h2>
    <div class="tablewrap">
      <table>
        <thead>
          <tr>
            <th>Página</th><th>Tokens</th><th>Bruto USD</th><th>Retiene página</th>
            <th>Neto USD</th><th>Monitor USD</th><th>Modelo USD</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $f): ?>
          <tr>
            <td><?= h($f['nombre']) ?></td>
            <td><?= number_format($f['tokens'], 0, ',', '.') ?></td>
            <td><?= usd($f['bruto']) ?></td>
            <td><?= usd($f['retenido']) ?> (<?= h($f['ret']) ?>%)</td>
            <td><?= usd($f['neto']) ?></td>
            <td><?= usd($f['monitor']) ?></td>
            <td><?= usd($f['modelo']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td>TOTAL</td>
            <td><?= number_format($tot['tokens'], 0, ',', '.') ?></td>
            <td><?= usd($tot['bruto']) ?></td>
            <td><?= usd($tot['retenido']) ?></td>
            <td><?= usd($tot['neto']) ?></td>
            <td><?= usd($tot['monitor']) ?></td>
            <td><?= usd($tot['modelo']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="tablewrap" style="margin-top:18px">
      <table>
        <thead>
          <tr><th>Página</th><th>Neto COP</th><th>Monitor COP</th><th>Modelo COP</th></tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $f): ?>
          <tr>
            <td><?= h($f['nombre']) ?></td>
            <td><?= cop($f['neto'] * $trm) ?></td>
            <td><?= cop($f['monitor'] * $trm) ?></td>
            <td><?= cop($f['modelo'] * $trm) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="big">
      <div class="kpi"><small>Total que pagan las páginas (COP)</small><b><?= cop($tot['neto'] * $trm) ?></b></div>
      <div class="kpi mon"><small>Total monitor / colaborador (<?= h($pctMon) ?>%)</small><b><?= cop($tot['monitor'] * $trm) ?></b></div>
      <div class="kpi mod"><small>Total modelo (<?= h(100 - $pctMon) ?>%)</small><b><?= cop($tot['modelo'] * $trm) ?></b></div>
    </div>
  </div>
  <?php endif; ?>

  <p class="note">
    Fórmula: <b>Bruto</b> = tokens × valor del token · <b>Neto</b> = Bruto − retención de la página ·
    <b>Monitor</b> = Neto × % monitor · <b>Modelo</b> = Neto − Monitor · <b>COP</b> = USD × TRM.<br>
    Los porcentajes y valores por defecto son editables y pueden cambiar según el contrato de cada modelo
    y las políticas de cada página; ajústalos a lo que realmente aplica en cada caso.
  </p>
</div>
</body>
</html>
