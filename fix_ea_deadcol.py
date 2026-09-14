import io, subprocess

P = r"C:\Projects\HirePeak\employer-application.php"
PHP = r"C:\php\php.exe"
TMP = r"C:\Users\USER\AppData\Local\Temp\opencode"
OUT_EV = TMP + r"\ea_fix_evidence.txt"

DEAD = "                a.attachments_cv,"

# --- 1) byte-accurate read, preserving original line endings ---
raw = io.open(P, "r", encoding="utf-8-sig", newline="").read()
parts = raw.split("\n")                       # keep \r on lines; \r\n stays split on \n
buf = []

# --- 2) stats before ---
buf.append("BEFORE_TOTAL_LINES=%d" % len(parts))
hits = [i for i, ln in enumerate(parts) if ln.rstrip("\r") == DEAD]
buf.append("BEFORE_DEAD_LINE_INDEXES=%s" % (hits,))
for i in hits:
    buf.append("DEAD_LINE_%d=%r" % (i, parts[i]))

# --- 3) apply the single-line removal (exact match, first hit only) ---
changed = False
if len(hits) == 1:
    idx = hits[0]
    new_parts = parts[:idx] + parts[idx + 1:]
    io.open(P, "w", encoding="utf-8", newline="").write("".join(new_parts))
    changed = True
    buf.append("FIX_APPLIED=yes idx=%d" % idx)
else:
    buf.append("FIX_APPLIED=no hits=%d" % len(hits))

# --- 4) re-lint via php.exe (byte channel) ---
r = subprocess.run([PHP, "-l", P], capture_output=True, text=True)
buf.append("AFTER_LINT_EXIT=%d" % r.returncode)
buf.append("AFTER_LINT_OUT=%s" % (r.stdout or "").strip())
buf.append("AFTER_LINT_ERR=%s" % (r.stderr or "").strip())

# --- 5) re-repr lines 35-50 after the change ---
lines2 = io.open(P, "r", encoding="utf-8-sig", newline="").read().split("\n")
buf.append("=== AFTER lines 35-50 ===")
for i in range(34, 50):
    if i < len(lines2):
        buf.append("%03d|%r" % (i + 1, lines2[i].rstrip("\r")))
    else:
        buf.append("%03d|<EOF>" % (i + 1))

io.open(OUT_EV, "w", encoding="utf-8", newline="\r\n").write("\r\n".join(buf))
print("FIX_SCRIPT_RAN")
