#!/usr/bin/env node
/**
 * Auto-generated prohibited-language scanner runtime.
 * DO NOT EDIT MANUALLY. Regenerate from the private operator-side scanner source.
 *
 * Generated: 2026-06-10T03:07:40.872Z
 * Source of truth: private operator-side prohibited-language pattern catalogue.
 *
 * What this script does:
 *   Scans a single file's contents for private infrastructure / process /
 *   engineering-narration tokens that must never leak into public release
 *   bodies, customer-facing prose, or public source-tree files. Exits 1 with
 *   a structured failure message when any BLOCKER hit is found. WARN-severity
 *   hits are not surfaced (matching the fail-closed scanner contract).
 *
 * CLI:
 *   node leak-scan.cjs <file> [--audience body|release_body|repo_file]
 *
 *   --audience defaults to 'body' (the most permissive non-release surface).
 *   Pass 'release_body' for GitHub Release body content; 'repo_file' for
 *   tracked-file content checks.
 */
'use strict';

const LEAK_PATTERNS = [
	{ pattern: /f4d\.nl/i, category: "infra", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /@super-forms-updates/i, category: "infra", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /Wpup/i, category: "infra", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /MyCustomServer/i, category: "infra", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /wp\.(stable|beta|alpha|dev)\.super-forms\.com/i, category: "infra", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /release\/6\.3\.x/i, category: "internal_branch", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /release\/6\.4\.x/i, category: "internal_branch", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /lts\/6\.3\.x/i, category: "internal_branch", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /gitbook-docs/i, category: "internal_path", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /sf-scripts/i, category: "cc_inbox_cli", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /cc-inbox/i, category: "cc_inbox_brand", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /AGENTS\.md/i, category: "agent_vocabulary", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /^(?:#+\s*)?\*{0,2}Source commit/im, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /^(?:#+\s*)?\*{0,2}Provenance/im, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body"] },
	{ pattern: /^(?:#+\s*)?\*{0,2}Customer impact/im, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /^(?:#+\s*)?\*{0,2}Channel:/im, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /^(?:#+\s*)?\*{0,2}Customer ZIP:/im, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /^(?:#+\s*)?\*{0,2}Source branch:/im, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /byte-for-byte/i, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /md5 hash/i, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /sha256/i, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /backport from/i, category: "engineering_narration", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\/termux-home\//i, category: "internal_path", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bDieske\b/i, category: "agent_vocabulary", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bRens-(?:gated|decides|approves|commissions)\b/i, category: "gated_action", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bstore\/(?:repos|gmail|zendesk)\//i, category: "internal_path", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bstore\/kb\//i, category: "internal_path", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /(?:^|[\s"'`(])\.omp\//, category: "internal_path", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bfeature-verify-cli\b/i, category: "cc_inbox_cli", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bsource:workspace\b/i, category: "cc_inbox_cli", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bprocess-pending-issues\b/i, category: "cc_inbox_cli", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bcapabilities\.md\b/i, category: "internal_path", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bbun run review\b/i, category: "cc_inbox_cli", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bbun src\/scripts\//i, category: "cc_inbox_cli", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bpublish-release\.ts\b/i, category: "release_process", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bcc[_]inbox\b|\bccinbox\b/i, category: "cc_inbox_brand", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bdoctrine\b/i, category: "agent_vocabulary", severity: "WARN", audience: ["release_body"], allowlistPhrases: ["doctrine/instantiator"] },
	{ pattern: /\blocal:\/\/[A-Z0-9_]+\.md\b/, category: "internal_url", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\b(?:agent|memory|artifact|jobs|skill|rule|pi):\/\//i, category: "internal_url", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\.planning\//i, category: "internal_path", severity: "WARN", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bSF_[A-Z_]+_PLAN\b/, category: "agent_vocabulary", severity: "WARN", audience: ["release_body","body","repo_file"] },
	{ pattern: /--dry-run\s+--show-request\b/i, category: "release_process", severity: "WARN", audience: ["release_body","body","repo_file"] },
	{ pattern: /operator[\s-]password\b/i, category: "gated_action", severity: "WARN", audience: ["release_body","body","repo_file"] },
	{ pattern: /\b[0-9a-f]{16}\b/i, category: "customer_identifier", severity: "BLOCKER", audience: ["release_body","body","repo_file"] },
	{ pattern: /\bticket\s*#?\s*\d{4,7}\b/i, category: "customer_identifier", severity: "WARN", audience: ["release_body","body","repo_file"] },
];

const REPO_FILE_PATH_ALLOWLIST = [
	/^\.github\/workflows\/claude.*\.ya?ml$/i,
	/^\.github\/scripts\/leak-scan\.cjs$/i,
	/^\.mcp\/README\.md$/i,
	/^src\/react\/admin\/pages\/settings\/AiProviderPanel\.tsx$/,
	/^src\/includes\/automations\/actions\/class-action-ai-completion\.php$/,
	/^CLAUDE\.md$/,
	/^AGENTS\.md$/,
	/^docs\/CLAUDE\.(?:php|javascript|ui)\.md$/,
];

const WORD_BOUNDARY_TOKENS = ["v7","master","v6.4.200","v6.4.201","v6.4.x"];
const LITERAL_SUBSTRINGS = ["[v7 alpha]","[scaffold only]","[on master, unshipped]","[on stable, unshipped]","[not present anywhere]","[in public stable]","[in public beta only]","next/v7","the rewrite","react rewrite","react version"];

function escapeRegExp(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

const WORD_BOUNDARY_REGEXES = WORD_BOUNDARY_TOKENS.map((token) => ({
	token,
	regex: new RegExp(`\\b${escapeRegExp(token)}\\b`, 'i'),
}));

function scanForAudience(body, audience) {
	const hits = new Set();
	const lower = body.toLowerCase();
	for (const entry of LEAK_PATTERNS) {
		if (entry.severity !== 'BLOCKER') continue;
		if (!entry.audience.includes(audience)) continue;
		if (!entry.pattern.test(body)) continue;
		if (entry.allowlistPhrases && entry.allowlistPhrases.some((p) => lower.includes(p.toLowerCase()))) continue;
		hits.add(entry.pattern.toString());
	}
	return Array.from(hits);
}

function findProhibitedLanguageInBody(body) {
	const hits = new Set(scanForAudience(body, 'body'));
	for (const { token, regex } of WORD_BOUNDARY_REGEXES) {
		if (regex.test(body)) hits.add(token);
	}
	const lower = body.toLowerCase();
	for (const phrase of LITERAL_SUBSTRINGS) {
		if (lower.includes(phrase.toLowerCase())) hits.add(phrase);
	}
	return Array.from(hits);
}

function findProhibitedLanguageInReleaseBody(body) {
	return scanForAudience(body, 'release_body');
}

function findProhibitedLanguageInRepoFile(relativePath, content, privacy) {
	if (privacy === 'private' || privacy === 'internal') return [];
	for (const rx of REPO_FILE_PATH_ALLOWLIST) {
		if (rx.test(relativePath)) return [];
	}
	return scanForAudience(content, 'repo_file');
}

// ─── CLI entry ─────────────────────────────────────────────────────────────

function main() {
	const args = process.argv.slice(2);
	let file;
	let audience = 'body';
	for (let i = 0; i < args.length; i++) {
		const a = args[i];
		const next = args[i + 1];
		if (a === '--audience') {
			if (!next || !['body', 'release_body', 'repo_file'].includes(next)) {
				console.error('--audience must be one of: body, release_body, repo_file');
				process.exit(2);
			}
			audience = next;
			i++;
		} else if (a === '--help' || a === '-h') {
			console.log('Usage: node leak-scan.cjs <file> [--audience body|release_body|repo_file]');
			process.exit(0);
		} else if (a.startsWith('--')) {
			console.error(`unknown flag: ${a}`);
			process.exit(2);
		} else {
			file = a;
		}
	}
	if (!file) {
		console.error('usage: node leak-scan.cjs <file> [--audience body|release_body|repo_file]');
		process.exit(2);
	}
	const fs = require('node:fs');
	const path = require('node:path');
	let content;
	try {
		content = fs.readFileSync(file, 'utf8');
	} catch (err) {
		console.error(`[leak-scan] cannot read ${file}: ${err.message}`);
		process.exit(2);
	}
	let hits;
	if (audience === 'release_body') {
		hits = findProhibitedLanguageInReleaseBody(content);
	} else if (audience === 'repo_file') {
		hits = findProhibitedLanguageInRepoFile(path.relative(process.cwd(), file), content);
	} else {
		hits = findProhibitedLanguageInBody(content);
	}
	if (hits.length > 0) {
		console.error(`[leak-scan] ${file} (audience=${audience}) contains prohibited language:`);
		for (const h of hits) console.error(`  - ${h}`);
		process.exit(1);
	}
	console.log(`[leak-scan] ${file} (audience=${audience}) clean`);
	process.exit(0);
}

if (require.main === module) main();

module.exports = {
	LEAK_PATTERNS,
	findProhibitedLanguageInBody,
	findProhibitedLanguageInReleaseBody,
	findProhibitedLanguageInRepoFile,
};
