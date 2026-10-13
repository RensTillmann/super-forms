#!/usr/bin/env python3
"""Super Forms privacy gate scanner (shared by local git hooks and the PR Action).

Scans added lines of unified diffs and free text (commit messages, PR title/body)
for customer data. Output never contains a matched denylist term or the denylist.

  privacy_scan.py diff    [--label L] < unified.diff
  privacy_scan.py text    --label "commit abc123 message" < message.txt
  privacy_scan.py range   BASE HEAD      # every commit message + full diff in BASE..HEAD
Denylist: env PRIVACY_DENYLIST (newline separated) if set, else the file in
SF_PRIVACY_DENYLIST_FILE (default ~/.config/sf-privacy/denylist.txt).
--require-denylist: exit 2 if the denylist is missing or empty (fail closed).
Exit: 0 clean, 1 findings, 2 configuration error.
"""
import os, re, sys, subprocess, argparse

BUILTIN = [
    ("email address", re.compile(r"[A-Za-z0-9._%+-]+@(?![A-Za-z0-9.-]*users\.noreply\.github\.com\b)[A-Za-z0-9.-]+\.[A-Za-z]{2,}")),
    ("helpdesk reference", re.compile(r"zen" r"desk", re.I)),
    ("support-case word", re.compile(r"\bti" r"ckets?\b", re.I)),
    ("case number (#3nnn)", re.compile(r"#3[0-9]{3}\b")),
    ("helpdesk case number", re.compile(r"\bZD[\s_-]*#?\s*[0-9]{3,}\b", re.I)),
]

def load_denylist(require):
    raw = os.environ.get("PRIVACY_DENYLIST")
    if raw is None:
        path = os.path.expanduser(os.environ.get("SF_PRIVACY_DENYLIST_FILE", "~/.config/sf-privacy/denylist.txt"))
        raw = open(path, encoding="utf-8").read() if os.path.exists(path) else ""
    terms = sorted({t.strip().lower() for t in raw.splitlines() if t.strip() and not t.startswith("#")}, key=len, reverse=True)
    if not terms:
        if require:
            print("privacy-gate: ERROR denylist is missing or empty (PRIVACY_DENYLIST secret not available?). Failing closed.")
            sys.exit(2)
        print("privacy-gate: WARNING no denylist loaded; only built-in patterns are checked.")
        return None
    return Deny(terms)

TOK = re.compile(r"[^\W_]+", re.U)

def norm(t):
    return tuple(TOK.findall(t.lower()))

class Deny:
    """Whole-token matching (case-insensitive): a term matches only as complete
    word(s), e.g. a name inside a longer identifier does not match. Fast for
    thousands of terms because it looks up token n-grams in a set."""
    def __init__(self, terms):
        self.set = {norm(t) for t in terms if norm(t)}
        self.maxn = min(8, max(len(k) for k in self.set))
    def search(self, text):
        toks = norm(text)
        for n in range(1, self.maxn + 1):
            for i in range(len(toks) - n + 1):
                if toks[i:i + n] in self.set:
                    return True
        return False

class Scanner:
    def __init__(self, deny):
        self.deny, self.hits, self.strip_comments = deny, 0, False
    def line(self, text, where):
        if self.deny is not None and self.deny.search(text):
            self.hits += 1; print(f"privacy-gate: Customer name found {where} (denylisted term)")
        for label, rx in BUILTIN:
            if rx.search(text):
                self.hits += 1; print(f"privacy-gate: {label} found {where}")
    def text(self, body, label):
        for n, l in enumerate(body.splitlines(), 1):
            if self.strip_comments and l.startswith("#"):
                continue  # git strips these comment lines from the final message
            self.line(l, f"{label}, line {n}")
    # Third-party bundled code: author names/emails there are not customer data.
    # gitleaks still scans these paths for secrets.
    SKIP = re.compile(r"(^|/)(vendor|node_modules)/|(^|/)composer\.lock$|\.min\.(js|css)$")
    def diff(self, body, prefix=""):
        f, n, in_hdr = "?", 0, False
        for l in body.splitlines():
            if l.startswith("diff --git "):
                in_hdr = True; continue
            if in_hdr and l.startswith("+++ "):
                f = l[4:]; f = f[2:] if f.startswith("b/") else f; in_hdr = False; continue
            if in_hdr:
                continue
            m = re.match(r"@@ -\d+(?:,\d+)? \+(\d+)", l)
            if m:
                n = int(m.group(1)); in_hdr = False; continue
            if l.startswith("+"):
                if not self.SKIP.search(f):
                    self.line(l[1:], f"{prefix}in {f}, line {n}")
                n += 1
            elif l.startswith(" "):
                n += 1
        # file names themselves
        for fm in re.finditer(r"^\+\+\+ b/(.*)$", body, re.M):
            self.line(fm.group(1), f"{prefix}in file path {fm.group(1)!s}" if self.deny is None or not self.deny.search(fm.group(1)) else f"{prefix}in a file path")

def git(*a):
    return subprocess.run(["git", *a], check=True, capture_output=True, text=True, errors="replace").stdout

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("mode", choices=["diff", "text", "range"])
    ap.add_argument("rest", nargs="*")
    ap.add_argument("--label", default="")
    ap.add_argument("--strip-comments", action="store_true")
    ap.add_argument("--require-denylist", action="store_true")
    a = ap.parse_args()
    s = Scanner(load_denylist(a.require_denylist)); s.strip_comments = a.strip_comments
    if a.mode == "diff":
        s.diff(sys.stdin.read(), a.label)
    elif a.mode == "text":
        s.text(sys.stdin.read(), a.label or "in text")
    else:
        base, head = a.rest
        shas = git("rev-list", "--reverse", f"{base}..{head}").split()
        for sha in shas:
            s.text(git("log", "-1", "--format=%B", sha), f"in commit {sha[:12]} message")
        if shas:
            s.diff(git("diff", "--no-color", "--no-ext-diff", "-U0", base, head))
        print(f"privacy-gate: scanned {len(shas)} commit message(s) and the diff {base[:12]}..{head[:12]}")
    if s.hits:
        print(f"privacy-gate: FAILED with {s.hits} finding(s). Remove them and try again.")
        sys.exit(1)
    print("privacy-gate: clean")

if __name__ == "__main__":
    main()
