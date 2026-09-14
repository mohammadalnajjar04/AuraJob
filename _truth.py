import io, subprocess, os, sys

P = r"C:\Projects\HirePeak\job-applicants.php"

lines = io.open(P, "r", encoding="utf-8", newline="").read().split("\n")

out = []
out.append("=== LINES 28-42 (physical on-disk) ===")
for i in range(28, 43):
    if i - 1 < len(lines):
        out.append("%03d|%r" % (i, lines[i - 1].rstrip("\r")))
    else:
        out.append("%03d|<missing>" % i)

out.append("")
out.append("=== php -l ===")
r = subprocess.run(
    [r"C:\php\php.exe", "-l", P],
    capture_output=True, text=True,
)
out.append("returncode=%d" % r.returncode)
out.append("stdout=%r" % r.stdout)
out.append("stderr=%r" % r.stderr)

out.append("")
out.append("=== '41|' (line 41 exactly, repr) ===")
if 40 < len(lines):
    out.append(repr(lines[40].rstrip("\r")))

dest = os.path.join(os.environ.get("TEMP", "."), "hirepeak_truth.txt")
io.open(dest, "w", encoding="utf-8", newline="").write("\n".join(out))
print("WROTE=%s" % dest)
