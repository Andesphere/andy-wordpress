import { expect, test } from 'bun:test'
import { runInNewContext } from 'node:vm'

const script = await Bun.file(new URL('../assets/access-check.js', import.meta.url)).text()

// Only the browser/network boundary is doubled; the shipped access-check runs unchanged.
class Element {
	children: Element[] = []
	value = 'agent_123'
	disabled = false
	className = ''
	private text = ''
	private listeners: Record<string, () => void> = {}
	set textContent(value: string) { this.text = value; this.children = [] }
	get textContent() { return this.text + this.children.map(child => child.textContent).join('') }
	appendChild(child: Element) { this.children.push(child) }
	addEventListener(event: string, listener: () => void) { this.listeners[event] = listener }
	emit(event: string) { this.listeners[event]?.() }
}

const fixture = (enabled = false, savedId = 'agent_123') => {
	const input = new Element()
	const button = new Element()
	const output = new Element()
	const timers: Array<() => void> = []
	const requests: Array<{ url: string; options: RequestInit }> = []
	let answer: (options: RequestInit) => Promise<unknown> = async () => ({ ok: true, status: 200, json: async () => ({ chatbot: { id: 'agent_123', name: 'Aurora' } }) })
	const text = { invalidId: 'INVALID', checking: 'Checking %1$s from %2$s', success: 'Allowed %1$s from %2$s', successOtherOrigin: 'Public %s untested',
		widgetOff: 'SAVED_OFF', widgetOn: 'SAVED_ON', unsavedId: 'UNSAVED_ID', noId: 'NO_ID', unchecked: 'UNCHECKED', notFound: 'NOT_FOUND %s', unexpected: 'HTTP %s',
		blocked: 'BLOCKED_OR_UNKNOWN', addOrigin: 'Review %s', siteOriginDiffers: 'Also %s', network: 'NETWORK', timeout: 'TIMEOUT %s', internal: 'INTERNAL' }
	const config = { savedId, enabled, endpoint: 'https://app.andypartner.com/api/chatbot/', query: '?source=wordpress-plugin&plugin_version=0.1.2',
		siteOrigin: 'https://public.example', pattern: '^[A-Za-z0-9_-]{3,100}$', text }
	runInNewContext(script, {
		window: { andyChatAccess: config, location: { origin: 'https://admin.example' }, AbortController,
			setTimeout: (callback: () => void) => { timers.push(callback); return timers.length }, clearTimeout: () => {} },
		document: { getElementById: (id: string) => ({ andy_chat_embed_id: input, 'andy-chat-check-access': button, 'andy-chat-access-result': output })[id], createElement: () => new Element() },
		fetch: (url: string, options: RequestInit) => { requests.push({ url, options }); return answer(options) },
		console,
	})
	return { input, button, output, requests, config, timers, setAnswer: (next: typeof answer) => { answer = next } }
}
const settle = () => new Promise(resolve => setImmediate(resolve))

for (const [enabled, savedId, state] of [[false, 'agent_123', 'SAVED_OFF'], [true, 'agent_123', 'SAVED_ON'], [true, 'another_saved_agent', 'UNSAVED_ID']] as const) {
	test(`access eligibility reports ${state} without changing saved settings`, async () => {
		const f = fixture(enabled, savedId)
		f.button.emit('click')
		expect(f.button.disabled).toBe(true)
		await settle()
		expect(f.output.textContent).toBe(`Allowed Aurora from https://admin.example Public https://public.example untested ${state}`)
		expect(f.requests).toHaveLength(1)
		expect(f.requests[0]).toMatchObject({ url: 'https://app.andypartner.com/api/chatbot/agent_123?source=wordpress-plugin&plugin_version=0.1.2', options: { mode: 'cors', credentials: 'omit', cache: 'no-store' } })
		expect(f.config).toMatchObject({ enabled, savedId })
		expect(f.input.value).toBe('agent_123')
		expect(f.button.disabled).toBe(false)
	})
}

test('editing an ID cancels its request and a late reply cannot replace the unchecked state', async () => {
	const f = fixture()
	let complete: ((value: unknown) => void) | undefined
	f.setAnswer(async () => new Promise(resolve => { complete = resolve }))
	f.button.emit('click')
	f.input.value = 'next_agent'
	f.input.emit('input')
	expect(f.requests[0].options.signal?.aborted).toBe(true)
	expect(f.output.textContent).toBe('UNCHECKED')
	complete!({ ok: true, status: 200, json: async () => ({ chatbot: { id: 'agent_123' } }) })
	await settle()
	expect(f.output.textContent).toBe('UNCHECKED')
	f.input.value = ''
	f.input.emit('input')
	expect(f.output.textContent).toBe('NO_ID')
})

test('failed access has a distinct result and never changes saved enablement', async () => {
	const f = fixture(true)
	f.setAnswer(async () => ({ ok: false, status: 404, json: async () => ({}) }))
	f.button.emit('click')
	await settle()
	expect(f.output.textContent).toBe('NOT_FOUND agent_123')
	expect(f.config.enabled).toBe(true)
	expect(f.output.children[0].className).toBe('notice notice-error inline')
})

test('timeout cancels the owned request and invalid IDs make no request', async () => {
	const f = fixture()
	f.input.value = 'bad id!'
	f.button.emit('click')
	expect(f.requests).toEqual([])
	expect(f.output.textContent).toBe('INVALID')
	f.input.value = 'agent_123'
	f.setAnswer(async () => new Promise(() => {}))
	f.button.emit('click')
	f.timers[0]()
	expect(f.output.textContent).toBe('TIMEOUT 15')
	expect(f.requests[0].options.signal?.aborted).toBe(true)
	expect(f.button.disabled).toBe(false)
})
