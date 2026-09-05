#!/usr/bin/env python3
"""Writes languages/andy-chat-es_ES.po and compiles languages/andy-chat-es_ES.mo (no gettext tools needed)."""
import pathlib, re, struct

root = pathlib.Path(__file__).resolve().parent.parent
lang = root / "languages"

ES = {
    "Andy Chat": "Andy Chat",
    "Before you enable the widget": "Antes de activar el widget",
    "Connect your Agent": "Conecta tu Agente",
    "Embed id": "Embed id",
    "Widget": "Widget",
    "Settings": "Ajustes",
    "When the widget is on, every public page of this site loads a script from %s, a service run by Andesphere. Nothing is sent while the widget is off.":
        "Con el widget activado, cada página pública de este sitio carga un script desde %s, un servicio operado por Andesphere. Con el widget desactivado no se envía nada.",
    "On page load the visitor's browser requests the widget script and your Agent's public configuration. Andy receives the visitor's IP address, browser details and this site's address as part of that request.":
        "Al cargar la página, el navegador del visitante solicita el script del widget y la configuración pública de tu Agente. Andy recibe la dirección IP del visitante, datos del navegador y la dirección de este sitio como parte de esa solicitud.",
    "When a visitor writes in the chat, the message text and a temporary session id are sent to Andy so your Agent can answer. If your Agent asks for a name and email, those are sent too.":
        "Cuando un visitante escribe en el chat, el texto del mensaje y un id de sesión temporal se envían a Andy para que tu Agente pueda responder. Si tu Agente pide nombre y email, también se envían.",
    "Andy keeps conversations while the Agent and Workspace exist, or until you delete them in Andy. Andy may keep technical logs for security and debugging. This plugin stores nothing about visitors and sends no analytics of its own.":
        "Andy conserva las conversaciones mientras existan el Agente y el Workspace, o hasta que las elimines en Andy. Andy puede conservar registros técnicos por seguridad y depuración. Este plugin no guarda nada sobre los visitantes y no envía analíticas propias.",
    "Turning the widget off, or deactivating this plugin, stops the script on the next page load. It does not delete conversations already stored in Andy; manage those from the Andy App.":
        "Desactivar el widget, o desactivar este plugin, detiene el script en la siguiente carga de página. No elimina las conversaciones ya guardadas en Andy; gestiónalas desde la App de Andy.",
    "Read the %1$s, the %2$s and the %3$s before enabling the widget.":
        "Lee la %1$s, los %2$s y la %3$s antes de activar el widget.",
    "Andy privacy policy": "política de privacidad de Andy",
    "terms of service": "términos del servicio",
    "data retention and deletion policy": "política de retención y eliminación de datos",
    "In the Andy App open your Agent, go to Installation and pick the HTML / Vanilla JavaScript tab. The value assigned to ANDY_CHATBOT_ID in that snippet is the public embed id. It is not a secret and no API key is needed.":
        "En la App de Andy abre tu Agente, entra en Instalación y elige la pestaña HTML / Vanilla JavaScript. El valor asignado a ANDY_CHATBOT_ID en ese fragmento es el embed id público. No es un secreto y no hace falta ninguna API key.",
    "No Andy account yet? %s": "¿Aún no tienes cuenta en Andy? %s",
    "Create your Andy account": "Crea tu cuenta de Andy",
    "If your Agent restricts Allowed Origins in Andy, that list must include this site: %s":
        "Si tu Agente restringe los Orígenes Permitidos (Allowed Origins) en Andy, esa lista debe incluir este sitio: %s",
    "Letters, digits, hyphens and underscores only. Example: k2f9x0w4d7mqzn1vp8yc3rh6t5ejab0g":
        "Solo letras, dígitos, guiones y guiones bajos. Ejemplo: k2f9x0w4d7mqzn1vp8yc3rh6t5ejab0g",
    "Show the Andy widget on every public page": "Mostrar el widget de Andy en todas las páginas públicas",
    "Leave this off until you have read the disclosure above. Activating the plugin alone never loads the widget.":
        "Déjalo desactivado hasta haber leído el aviso de arriba. Activar el plugin por sí solo nunca carga el widget.",
    "You do not have permission to change Andy Chat settings.": "No tienes permiso para cambiar los ajustes de Andy Chat.",
    "The Andy widget is on. Visitors see it on every public page.": "El widget de Andy está activado. Los visitantes lo ven en todas las páginas públicas.",
    "The Andy widget is off. Paste your embed id to get started.": "El widget de Andy está desactivado. Pega tu embed id para empezar.",
    "The Andy widget is off. Turn it on below when you are ready.": "El widget de Andy está desactivado. Actívalo abajo cuando estés listo.",
    "Save settings": "Guardar ajustes",
    "Enter your Agent's embed id before enabling the widget. The widget stays off.":
        "Introduce el embed id de tu Agente antes de activar el widget. El widget sigue desactivado.",
    "That embed id is not valid. Copy it from the Installation tab of your Agent in the Andy App: it only contains letters, digits, hyphens and underscores.":
        "Ese embed id no es válido. Cópialo desde la pestaña Instalación de tu Agente en la App de Andy: solo contiene letras, dígitos, guiones y guiones bajos.",
    "Settings saved. The Andy widget is on for every public page.": "Ajustes guardados. El widget de Andy está activado en todas las páginas públicas.",
    "Settings saved. The Andy widget is off.": "Ajustes guardados. El widget de Andy está desactivado.",
}

pot = (lang / "andy-chat.pot").read_text()
pot_ids = re.findall(r'^msgid "((?:[^"\\]|\\.)*)"', pot, re.M)
pot_ids = [i.encode().decode("unicode_escape").encode("latin-1").decode("utf-8") for i in pot_ids if i]
missing = [i for i in pot_ids if i not in ES]
extra = [i for i in ES if i not in pot_ids]
if missing or extra:
    raise SystemExit(f"POT/ES mismatch. missing={missing} extra={extra}")

def esc(s):
    return s.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n")

header = (
    "Project-Id-Version: Andy Chat 0.1.0\n"
    "Language: es_ES\n"
    "MIME-Version: 1.0\n"
    "Content-Type: text/plain; charset=UTF-8\n"
    "Content-Transfer-Encoding: 8bit\n"
    "Plural-Forms: nplurals=2; plural=(n != 1);\n"
    "X-Domain: andy-chat\n"
)
po = ['msgid ""', 'msgstr ""'] + [f'"{esc(l)}\\n"' for l in header.strip("\n").split("\n")] + [""]
for msgid in pot_ids:
    po += [f'msgid "{esc(msgid)}"', f'msgstr "{esc(ES[msgid])}"', ""]
(lang / "andy-chat-es_ES.po").write_text("\n".join(po))

# Compile GNU .mo (little endian, revision 0).
pairs = [("", header)] + [(k, ES[k]) for k in pot_ids]
pairs.sort(key=lambda p: p[0].encode())
ids = b""; strs = b""; offsets = []
for k, v in pairs:
    kb, vb = k.encode(), v.encode()
    offsets.append((len(ids), len(kb), len(strs), len(vb)))
    ids += kb + b"\0"; strs += vb + b"\0"
n = len(pairs)
keystart = 7 * 4 + 16 * n
valuestart = keystart + len(ids)
koffsets = []; voffsets = []
for o1, l1, o2, l2 in offsets:
    koffsets += [l1, o1 + keystart]; voffsets += [l2, o2 + valuestart]
mo = struct.pack("<Iiiiiii", 0x950412DE, 0, n, 7 * 4, 7 * 4 + n * 8, 0, 0)
mo += struct.pack("<" + "i" * len(koffsets), *koffsets)
mo += struct.pack("<" + "i" * len(voffsets), *voffsets)
mo += ids + strs
(lang / "andy-chat-es_ES.mo").write_bytes(mo)

# WordPress 6.5+ prefers the PHP catalog when present; ship it too.
def php_str(s):
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"

php_entries = ",\n".join(f"    {php_str(k)} => {php_str(v)}" for k, v in pairs if k)
(lang / "andy-chat-es_ES.l10n.php").write_text(
    "<?php\nreturn [\n"
    "  'project-id-version' => 'Andy Chat 0.1.0',\n"
    "  'language' => 'es_ES',\n"
    "  'plural-forms' => 'nplurals=2; plural=(n != 1);',\n"
    "  'x-domain' => 'andy-chat',\n"
    "  'messages' => [\n" + php_entries + "\n  ],\n];\n"
)
print(f"wrote {n - 1} translations")
