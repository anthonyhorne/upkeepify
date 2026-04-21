#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const packageJsonPath = path.join(rootDir, 'package.json');
const packageLockPath = path.join(rootDir, 'package-lock.json');
const pluginMainPath = path.join(rootDir, 'upkeepify.php');
const readmeTxtPath = path.join(rootDir, 'readme.txt');

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function writeJson(filePath, value) {
  fs.writeFileSync(filePath, `${JSON.stringify(value, null, 2)}\n`);
}

function readText(filePath) {
  return fs.readFileSync(filePath, 'utf8');
}

function writeText(filePath, value) {
  fs.writeFileSync(filePath, value);
}

function parseVersion(version) {
  const match = /^(\d+)\.(\d+)\.(\d+)$/.exec(version);
  if (!match) {
    throw new Error(`Unsupported version format: ${version}`);
  }

  return match.slice(1).map(Number);
}

function formatVersion(parts) {
  return parts.join('.');
}

function incrementVersion(currentVersion, releaseType) {
  const [major, minor, patch] = parseVersion(currentVersion);

  switch (releaseType) {
    case 'major':
      return formatVersion([major + 1, 0, 0]);
    case 'minor':
      return formatVersion([major, minor + 1, 0]);
    case 'patch':
      return formatVersion([major, minor, patch + 1]);
    default:
      throw new Error(`Unknown release type: ${releaseType}`);
  }
}

function replaceRequired(content, searchValue, replaceValue, fileLabel) {
  const nextContent = content.replace(searchValue, replaceValue);
  if (nextContent === content) {
    throw new Error(`Failed to update ${fileLabel}`);
  }

  return nextContent;
}

function updatePluginMain(filePath, nextVersion) {
  let content = readText(filePath);
  content = replaceRequired(
    content,
    /(\*\s+Version:\s+)(\d+\.\d+\.\d+)/,
    `$1${nextVersion}`,
    'plugin header version'
  );
  content = replaceRequired(
    content,
    /(define\('UPKEEPIFY_VERSION',\s*')(\d+\.\d+\.\d+)('\);)/,
    `$1${nextVersion}$3`,
    'UPKEEPIFY_VERSION constant'
  );
  writeText(filePath, content);
}

function updateReadmeTxt(filePath, nextVersion) {
  const content = readText(filePath);
  const nextContent = replaceRequired(
    content,
    /(Stable tag:\s+)(\d+\.\d+\.\d+)/,
    `$1${nextVersion}`,
    'readme stable tag'
  );
  writeText(filePath, nextContent);
}

function main() {
  const releaseType = process.argv[2];
  if (!['major', 'minor', 'patch'].includes(releaseType)) {
    console.error('Usage: node scripts/bump-version.js <major|minor|patch>');
    process.exit(1);
  }

  const packageJson = readJson(packageJsonPath);
  const currentVersion = packageJson.version;
  const nextVersion = incrementVersion(currentVersion, releaseType);

  packageJson.version = nextVersion;
  writeJson(packageJsonPath, packageJson);

  const packageLock = readJson(packageLockPath);
  packageLock.version = nextVersion;
  if (packageLock.packages && packageLock.packages['']) {
    packageLock.packages[''].version = nextVersion;
  }
  writeJson(packageLockPath, packageLock);

  updatePluginMain(pluginMainPath, nextVersion);
  updateReadmeTxt(readmeTxtPath, nextVersion);

  console.log(`Bumped version: ${currentVersion} -> ${nextVersion}`);
  console.log('Updated: package.json, package-lock.json, upkeepify.php, readme.txt');
  console.log('Next: update docs/changelog.md, README.md changelog notes, and readme.txt upgrade notes if needed.');
}

main();
