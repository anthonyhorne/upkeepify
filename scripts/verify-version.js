#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');

function readJson(relativePath) {
  return JSON.parse(fs.readFileSync(path.join(rootDir, relativePath), 'utf8'));
}

function readText(relativePath) {
  return fs.readFileSync(path.join(rootDir, relativePath), 'utf8');
}

function requireMatch(label, value, expected) {
  if (value !== expected) {
    throw new Error(`${label} is ${value}, expected ${expected}`);
  }
}

function matchRequired(label, content, pattern) {
  const match = content.match(pattern);
  if (!match) {
    throw new Error(`Could not find ${label}`);
  }

  return match[1];
}

function main() {
  const packageJson = readJson('package.json');
  const packageLock = readJson('package-lock.json');
  const pluginMain = readText('upkeepify.php');
  const readmeTxt = readText('readme.txt');

  const expected = packageJson.version;
  if (!/^\d+\.\d+\.\d+$/.test(expected)) {
    throw new Error(`package.json version must be x.y.z, got ${expected}`);
  }

  requireMatch('package-lock.json version', packageLock.version, expected);
  if (packageLock.packages && packageLock.packages['']) {
    requireMatch('package-lock root package version', packageLock.packages[''].version, expected);
  }

  requireMatch(
    'plugin header version',
    matchRequired('plugin header version', pluginMain, /\*\s+Version:\s+(\d+\.\d+\.\d+)/),
    expected
  );
  requireMatch(
    'UPKEEPIFY_VERSION',
    matchRequired('UPKEEPIFY_VERSION constant', pluginMain, /define\('UPKEEPIFY_VERSION',\s*'(\d+\.\d+\.\d+)'\);/),
    expected
  );
  requireMatch(
    'readme stable tag',
    matchRequired('readme stable tag', readmeTxt, /Stable tag:\s+(\d+\.\d+\.\d+)/),
    expected
  );

  console.log(`Version metadata is aligned at ${expected}.`);
}

main();
