import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const manifestPath = path.join(root, 'docs/visual/migration-manifest.json');
const manifestText = fs.readFileSync(manifestPath, 'utf8');
const manifest = JSON.parse(manifestText);
const qa = JSON.parse(fs.readFileSync(path.join(root, 'config/qa.json'), 'utf8'));
const baseline = fs.readFileSync(path.join(root, 'docs/visual/baseline-bonasera.md'), 'utf8');

assert.equal(manifest.schemaVersion, 1);
assert.equal(manifest.impact, 'paridad-1-1');
assert.equal(manifest.source.commit, '1e1f62787e088c0ca9701500e764802499d1b253');
assert.equal(manifest.target.commit, '737d027f78ad301b4e0c80c2b316e131a1b807a5');
assert.notEqual(manifest.environment.browserVersion, 'auto');
assert.doesNotMatch(manifestText, /\/Users\/|[A-Za-z]:\\Users\\/);
assert.doesNotMatch(manifestText, /"(?:password|cookie|nonce|accessToken|refreshToken)"\s*:/i);

const expectedViewports = qa.viewports.map(({ width, height }) => `${width}x${height}`);
assert.deepEqual(
  manifest.viewports.map(({ width, height }) => `${width}x${height}`),
  expectedViewports,
);

const expectedSurfaces = [
  'home',
  'menu',
  'pizza-builder',
  'cart-empty',
  'checkout',
  'reservations',
  'saved-pizzas',
];
assert.deepEqual(manifest.surfaces.map(({ id }) => id), expectedSurfaces);

const expectedEvidence = new Set();
for (const surface of manifest.surfaces) {
  assert.deepEqual(surface.states, ['default']);
  for (const viewport of surface.viewports) {
    expectedEvidence.add(`${surface.id}|default|${viewport}`);
  }
}
assert.equal(expectedEvidence.size, 35);

const actualEvidence = new Set(
  manifest.evidence.map(({ surface, state, viewport }) => `${surface}|${state}|${viewport}`),
);
assert.equal(actualEvidence.size, manifest.evidence.length);
assert.deepEqual(actualEvidence, expectedEvidence);

for (const row of manifest.evidence) {
  for (const field of [
    'sourceCapture',
    'targetCapture',
    'comparisonCapture',
    'overlayCapture',
    'diffCapture',
  ]) {
    assert.ok(!path.isAbsolute(row[field]));
    assert.ok(!row[field].split(/[\\/]+/).includes('..'));

    const evidencePath = path.join(path.dirname(manifestPath), row[field]);
    assert.ok(fs.statSync(evidencePath).isFile());
    const hashField = `${field}Sha256`;
    assert.equal(
      crypto.createHash('sha256').update(fs.readFileSync(evidencePath)).digest('hex'),
      row[hashField],
    );
  }
  assert.equal(row.status, 'different');
}

const reportPath = path.join(path.dirname(manifestPath), manifest.report.json);
const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
assert.deepEqual(report.summary, {
  rows: 35,
  matched: 0,
  different: 35,
  approvedDifferences: 0,
});
assert.equal(
  crypto.createHash('sha256').update(fs.readFileSync(reportPath)).digest('hex'),
  manifest.report.jsonSha256,
);

const missingAssets = manifest.assets
  .filter(({ status }) => status === 'missing')
  .map(({ id }) => id)
  .sort();
assert.deepEqual(missingAssets, [
  'dolci-original',
  'hero-video',
  'history-original',
  'map-maracaibo',
  'map-zulia',
  'testimonial-avatars',
]);

assert.match(baseline, /\/pedido\//);
assert.match(baseline, /\/privacidad\//);
assert.match(baseline, /gate estructural aprobado/);
assert.match(baseline, /no se convierte en dependencia/);

console.log('Contrato de baseline visual Bonasera válido.');
