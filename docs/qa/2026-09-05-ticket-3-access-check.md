# Ticket #3 QA: check Agent access from the WordPress settings page

Tested commits: the PR head (recorded in the PR description) re-ran the full checklist; the first
pass ran against 6d0a5c7 plus the working tree that became the second commit. Environment: WordPress
Playground CLI 3.1.52 (WASM PHP 8.1, WordPress 7.1, SQLite), port 18843, synthetic site
"Andy Chat QA (synthetic)", synthetic embed id `qa0synthetic0embed0id0000000000a`. No production
site, no Andy account and no real Agent were touched. No chat message was sent. The only requests that
reached Andy were the public `widget.js` download and public `GET /api/chatbot/<synthetic id>` lookups,
from `curl` and from the browser.

How the site was driven: the plugin ZIP built by `bin/build-zip.sh` was uploaded through WordPress's
own `update.php?action=upload-plugin` handler and activated from the Plugins screen, both through the
admin session with `curl`. Settings → Andy Chat was exercised in the T3 built-in browser. Controlled
responses were produced by replacing `window.fetch` in the page for one click at a time; the live
scenarios used the untouched `fetch`.

## Real endpoint contract (read-only, curl, 2026-09-05)

`GET https://app.andypartner.com/api/chatbot/qa0synthetic0embed0id0000000000a` with no `Origin`,
`Origin: https://example.com` and `Origin: http://localhost:18843` all answer:

```
HTTP/2 403
content-type: application/json
{"error":"Origin not allowed for this chatbot"}
```

with no `Access-Control-Allow-Origin` header. Source (`apps/web/src/lib/cors.ts`,
`apps/web/src/app/api/chatbot/[id]/route.ts` in the read-only Andy checkout): an unknown or malformed
id makes the Allowed Origins lookup fail, and that path returns the same `corsErrorResponse` as an
origin outside a non-empty Allowed Origins list. A browser sees both as an opaque CORS failure. A
deleted Agent answers a readable 404 with CORS headers; an allowed origin gets 200 with a `chatbot`
object. The plugin copy therefore never claims a proven origin rejection: it names both causes and the
origin to add.

## Checklist

```gherkin
Feature: Check Agent access from the WordPress settings page

  Scenario: Live check against the real endpoint with the synthetic id (EN and ES)              PASS
    Given Settings → Andy Chat with the synthetic id in the field
    When the administrator clicks "Check access from this site"
    Then the region shows "Checking qa0synthetic… from http://127.0.0.1:18843…" with aria-busy=true
      and the button is disabled while the request runs
    And the final notice is notice-warning: "Andy answered but did not let this browser read the
      reply. That happens when the embed id does not exist, or when the Agent restricts Allowed
      Origins and this site is not on the list. If the embed id matches the Andy App exactly, open
      your Agent in Andy, go to Settings → Security → Allowed Origins, add http://127.0.0.1:18843
      and check again."
    And the browser made exactly two requests: the CORS GET, then the no-cors probe

  Scenario: Success only on a real supported response                                            PASS
    Given fetch answers 200 {"chatbot":{"id":"agent0success0id","name":"QA Agent (synthetic)"}}
    Then notice-success: "Success: Andy answered from http://127.0.0.1:18843 with the configuration
      of the Agent "QA Agent (synthetic)", so this site is allowed to load it. Chats still need an
      active Andy plan."
    Given fetch answers 200 {"ok":true} (no chatbot object)
    Then notice-error "Andy answered with HTTP 200 instead of the Agent's configuration…", no success

  Scenario: Readable failures are described accurately                                           PASS
    Given fetch answers 404 {"error":"…not found"}
    Then "Andy answered that no Agent with embed id agent0deleted0id exists. Copy the id again…"
    Given fetch answers 500
    Then "Andy answered with HTTP 500 instead of the Agent's configuration. Try again in a few
      minutes. This is not an Allowed Origins problem."

  Scenario: Opaque failure is separated from a network failure                                   PASS
    Given the CORS fetch rejects with TypeError and the no-cors probe resolves
    Then the blocked notice above (Andy reachable, reply unreadable)
    Given both the CORS fetch and the no-cors probe reject
    Then notice-error "Could not reach app.andypartner.com from this browser. Check your internet
      connection or a content blocker. This says nothing about Allowed Origins."

  Scenario: Invalid id never leaves the browser                                                  PASS
    When the field holds "ab" and the button is clicked
    Then "Enter a valid embed id before checking: letters, digits, hyphens and underscores only."
    And fetch was called 0 times

  Scenario: Changing the id cannot keep a stale success                                          PASS
    Given a success notice is showing for agent0aaa
    When the administrator types in the id field
    Then the region is emptied and hidden immediately
    Given a slow request for agent0slow is in flight and the id is edited to agent0other
    When the slow success lands
    Then the region stays empty and hidden
    Given click 1 (agent0first, slow success) then the id is edited and click 2 (agent0second, 404)
    When the 404 lands and later the slow success lands
    Then the region shows the 404 for agent0second both times; the stale success is dropped

  Scenario: Spanish copy from the bundled catalog                                                PASS
    Given site language es_ES (core es_ES.mo mounted, no andy-chat language pack)
    Then the row is "Comprobar acceso", the button "Comprobar el acceso desde este sitio",
      the description "Pide a Andy la configuración pública…", the checking line
      "Comprobando … desde http://127.0.0.1:18843…", and the live/success/404/500/network/invalid
      notices render in Spanish (see the transcript below)

  Scenario: Accessibility                                                                        PASS
    Then the result region is <div role="status" aria-live="polite"> and gets aria-busy="true"
      while a check runs; the trigger is a native <button type="button">; notices carry their
      meaning in text ("Success:", "Could not reach…"), not only in colour

  Scenario: No proxy, no new endpoint, no telemetry, no entitlement gate                          PASS
    Then the only URL the script requests is ANDY_CHAT_API_URL + "/chatbot/<id>" (the endpoint the
      widget itself uses); no PHP handler, REST route or AJAX action was added; saving the synthetic
      id with the widget on succeeded ("Settings saved. The Andy widget is on for every public page.")
      without any Andy request from the server

  Scenario: Fully rendered bubble from a controlled realistic public config (not live proof)      PASS
    Given the home page with the widget on (real https://app.andypartner.com/widget.js, 95502 bytes)
    And the real endpoint answered the synthetic id with the opaque 403, so nothing rendered
    When fetch is replaced to answer /api/chatbot/<synthetic id> with a realistic 200 body shaped
      like route.ts (name "QA Agent (synthetic)", appearance, behavior, faqs) and widget.js is run
      again with window.AndyChatbotLoaded reset
    Then #andy-chatbot-container appears with a shadow root and a visible 50×50 "Open chat" button
      at the bottom right; clicking it opens the panel with H1 "QA Agent (synthetic)", the headline,
      the FAQ list and the message input; localStorage stays empty; no message was sent
    And this proves the plugin's enqueue contract feeds the real bundle to a rendered bubble; it is
      not proof against a live Agent, which still needs an authorized public test Agent

  Scenario: Translation catalogs regenerated with WP-CLI                                          PASS
    Given languages/andy-chat-es_ES.po is the hand-maintained source
    Then wp i18n make-pot/make-mo/make-php (WP-CLI 2.12.0 inside Playground, pinned headers)
      regenerated the committed POT, MO and PHP catalogs; msgfmt --check --check-format and msgcmp
      pass locally with 47 translated messages; CI repeats the same steps

  Scenario: Plugin Check and CI                                                                   see PR
    CI runs PHP lint, catalog regeneration and diff, ZIP build, unpack and Plugin Check on the ZIP.
```

## Spanish transcript (controlled and live, site language es_ES)

```
live  : notice-warning  Andy respondió, pero no dejó que este navegador leyera la respuesta. Eso ocurre
        cuando el embed id no existe o cuando el Agente restringe los Orígenes Permitidos y este sitio
        no está en la lista. Si el embed id coincide exactamente con el de la App de Andy, abre tu
        Agente en Andy, ve a Ajustes → Seguridad → Orígenes Permitidos, añade http://127.0.0.1:18843
        y vuelve a comprobar.
200   : notice-success  Correcto: Andy respondió desde http://127.0.0.1:18843 con la configuración del
        Agente "Agente QA (sintético)", así que este sitio tiene permiso para cargarlo. Los chats
        siguen necesitando un plan activo de Andy.
404   : notice-error    Andy respondió que no existe ningún Agente con el embed id agent0deleted0id.
        Vuelve a copiar el id desde la pestaña Instalación de tu Agente.
500   : notice-error    Andy respondió con HTTP 500 en lugar de la configuración del Agente. Inténtalo
        de nuevo en unos minutos. No es un problema de Orígenes Permitidos.
net   : notice-error    No se pudo conectar con app.andypartner.com desde este navegador. Revisa tu
        conexión a internet o un bloqueador de contenido. Esto no dice nada sobre los Orígenes Permitidos.
"ab"  : notice-error    Introduce un embed id válido antes de comprobar: solo letras, dígitos, guiones
        y guiones bajos.
```

## Not covered here

- No authorized public test Agent exists, so the success and readable-404 paths were exercised with
  controlled responses only. The live path proves the opaque 403 and the no-cors probe against the
  real service. First real-Agent proof stays an open integration gap.
- The check runs from the wp-admin origin. When `home_url()` differs from the admin origin the copy
  adds "Visitors use <origin>, so add that origin as well."; that branch was read, not exercised,
  because Playground serves both from one origin.
- Playground runs PHP in WASM with SQLite; CI's Plugin Check runs in wp-env with MySQL.
