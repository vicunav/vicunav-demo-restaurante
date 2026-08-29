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
assert.equal(manifest.target.commit, '7b43e4508a13616ec976060dc33b4a1a4d01a1ac');
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
  assert.equal(row.status, 'approved-difference');
  assert.ok(row.difference);
  assert.equal(row.approval.authority, 'usuario');
  assert.match(row.approval.reference, /DEMO-REST-02E issue #19/);
}

const reportPath = path.join(path.dirname(manifestPath), manifest.report.json);
const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
assert.deepEqual(report.summary, {
  rows: 35,
  matched: 0,
  different: 0,
  approvedDifferences: 35,
});
assert.equal(
  crypto.createHash('sha256').update(fs.readFileSync(reportPath)).digest('hex'),
  manifest.report.jsonSha256,
);

const approvedSubstitutes = manifest.assets
	.filter(({ status }) => status === 'approved-substitute')
	.map(({ id }) => id)
	.sort();
assert.deepEqual(approvedSubstitutes, [
  'dolci-original',
  'hero-video',
  'history-original',
  'map-maracaibo',
  'map-zulia',
  'testimonial-avatars',
]);
for (const asset of manifest.assets.filter(({ status }) => status === 'approved-substitute')) {
  assert.ok(asset.substitute);
  assert.equal(asset.approval.authority, 'usuario');
  assert.ok(asset.approval.reference);
}

assert.match(baseline, /\/pedido\//);
assert.match(baseline, /\/privacidad\//);
assert.match(baseline, /gate estructural aprobado/);
assert.match(baseline, /no se convierte en dependencia/);

console.log('Contrato de baseline visual Bonasera válido.');
