/**
 * Build script for Upkeepify JS assets.
 * Minifies each JS file in js/ to js/*.min.js
 */

const esbuild = require('esbuild');
const fs      = require('fs');
const path    = require('path');

const jsDir  = path.join(__dirname, 'js');
const watch  = process.argv.includes('--watch');

const entries = fs.readdirSync(jsDir)
  .filter(f => f.endsWith('.js') && !f.endsWith('.min.js'))
  .map(f => ({
    entryPoints: [path.join(jsDir, f)],
    outfile:     path.join(jsDir, f.replace('.js', '.min.js')),
    minify:      true,
    bundle:      false,
  }));

if (watch) {
  Promise.all(entries.map(opts => esbuild.context(opts).then(ctx => ctx.watch())))
    .then(() => console.log('Watching for changes…'));
} else {
  Promise.all(entries.map(opts => esbuild.build(opts)))
    .then(() => {
      entries.forEach(({ outfile }) => {
        const size = (fs.statSync(outfile).size / 1024).toFixed(1);
        console.log(`  built ${path.basename(outfile)} (${size} KB)`);
      });
      console.log('Build complete.');
    })
    .catch(() => process.exit(1));
}
