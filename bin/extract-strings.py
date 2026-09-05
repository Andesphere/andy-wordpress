#!/usr/bin/env python3
"""Extracts translatable strings from the plugin's PHP into languages/andy-chat.pot."""
import re, sys, pathlib, datetime

root = pathlib.Path(__file__).resolve().parent.parent
funcs = r"(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x|esc_html_x)"
call = re.compile(funcs + r"\(\s*'((?:[^'\\]|\\.)*)'\s*,\s*'andy-chat'\s*\)", re.S)
comment = re.compile(r"/\*\s*translators:\s*(.*?)\*/", re.S)

entries = {}
for php in sorted(root.glob("*.php")) + sorted((root / "includes").glob("*.php")):
    text = php.read_text()
    for m in call.finditer(text):
        msgid = m.group(1).replace("\\'", "'")
        line = text.count("\n", 0, m.start()) + 1
        note = None
        before = text[max(0, m.start() - 400):m.start()]
        cm = list(comment.finditer(before))
        if cm and "translators" in before[-400:] and before.rfind("*/") > before.rfind(";"):
            note = " ".join(cm[-1].group(1).split())
        entries.setdefault(msgid, {"refs": [], "note": note})
        entries[msgid]["refs"].append(f"{php.relative_to(root)}:{line}")

def po_escape(s):
    return s.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n")

out = [
    'msgid ""',
    'msgstr ""',
    '"Project-Id-Version: Andy Chat 0.1.0\\n"',
    '"Report-Msgid-Bugs-To: https://github.com/Andesphere/andy-wordpress/issues\\n"',
    '"MIME-Version: 1.0\\n"',
    '"Content-Type: text/plain; charset=UTF-8\\n"',
    '"Content-Transfer-Encoding: 8bit\\n"',
    '"X-Domain: andy-chat\\n"',
    "",
]
for msgid, meta in entries.items():
    if meta["note"]:
        out.append(f"#. translators: {meta['note']}")
    out.append("#: " + " ".join(meta["refs"]))
    out.append(f'msgid "{po_escape(msgid)}"')
    out.append('msgstr ""')
    out.append("")

(root / "languages" / "andy-chat.pot").write_text("\n".join(out))
print(f"{len(entries)} strings")
for k in entries:
    print("-", k)
