import type { Template } from '@/types'

const store: Template[] = [
  {
    id: 'tpl-1',
    name: 'Hello World (Handlebars)',
    description: 'Simple greeting template',
    engineId: 'handlebars',
    code: '<h1>Hello, {{name}}!</h1>',
    json: '{"name":"World"}',
    isPublic: true,
    ownerId: 'mock-1',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tpl-2',
    name: 'Welcome (Pug)',
    description: 'Welcome page template',
    engineId: 'pug',
    code: 'h1 Hello #{name}',
    json: '{"name":"World"}',
    isPublic: false,
    ownerId: 'mock-1',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tpl-3',
    name: 'User Card (Handlebars)',
    description: 'Profile card with conditionals',
    engineId: 'handlebars',
    code: '<div class="card">\n  <h2>{{user.name}}</h2>\n  {{#if user.bio}}<p>{{user.bio}}</p>{{/if}}\n</div>',
    json: '{"user":{"name":"Alice","bio":"Frontend engineer"}}',
    isPublic: true,
    ownerId: 'mock-2',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tpl-4',
    name: 'Item List (Pug)',
    description: 'Loop over items with Pug',
    engineId: 'pug',
    code: 'ul\n  each item in items\n    li= item.name',
    json: '{"items":[{"name":"Alpha"},{"name":"Beta"},{"name":"Gamma"}]}',
    isPublic: true,
    ownerId: 'mock-2',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tpl-5',
    name: 'Table Report (EJS)',
    description: 'Render a table from array data',
    engineId: 'ejs',
    code: '<table>\n<% rows.forEach(row => { %>\n  <tr><td><%= row.label %></td><td><%= row.value %></td></tr>\n<% }) %>\n</table>',
    json: '{"rows":[{"label":"Revenue","value":"$12,000"},{"label":"Costs","value":"$8,500"},{"label":"Profit","value":"$3,500"}]}',
    isPublic: true,
    ownerId: 'mock-2',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 'tpl-6',
    name: 'Email Footer (EJS)',
    description: 'Reusable email footer snippet',
    engineId: 'ejs',
    code: '<footer>\n  <p>&copy; <%= year %> <%= company %>. All rights reserved.</p>\n</footer>',
    json: '{"year":2026,"company":"Acme Corp"}',
    isPublic: true,
    ownerId: 'mock-3',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
]

let nextId = 7

function delay() {
  return new Promise(r => setTimeout(r, 100 + Math.random() * 100))
}

export const templatesMock = {
  async getMyTemplates(): Promise<Template[]> {
    await delay()
    return store.filter(t => t.ownerId === 'mock-1')
  },

  async getPublicTemplates(): Promise<Template[]> {
    await delay()
    return store.filter(t => t.isPublic)
  },

  async getTemplate(id: string): Promise<Template> {
    await delay()
    const tpl = store.find(t => t.id === id)
    if (!tpl) throw new Error(`Template ${id} not found`)
    return tpl
  },

  async createTemplate(data: Omit<Template, 'id' | 'ownerId' | 'createdAt' | 'updatedAt'>): Promise<Template> {
    await delay()
    const now = new Date().toISOString()
    const tpl: Template = {
      ...data,
      id: `tpl-${nextId++}`,
      ownerId: 'mock-1',
      createdAt: now,
      updatedAt: now,
    }
    store.push(tpl)
    return tpl
  },

  async updateTemplate(id: string, data: Partial<Template>): Promise<Template> {
    await delay()
    const idx = store.findIndex(t => t.id === id)
    if (idx === -1) throw new Error(`Template ${id} not found`)
    const updated = { ...store[idx]!, ...data, updatedAt: new Date().toISOString() } as Template
    store[idx] = updated
    return updated
  },

  async deleteTemplate(id: string): Promise<void> {
    await delay()
    const idx = store.findIndex(t => t.id === id)
    if (idx !== -1) store.splice(idx, 1)
  },

  async cloneTemplate(id: string): Promise<Template> {
    await delay()
    const source = store.find(t => t.id === id)
    if (!source) throw new Error(`Template ${id} not found`)
    const now = new Date().toISOString()
    const clone: Template = {
      ...source,
      id: `tpl-${nextId++}`,
      name: `${source.name} (copy)`,
      isPublic: false,
      createdAt: now,
      updatedAt: now,
    }
    store.push(clone)
    return clone
  },
}
