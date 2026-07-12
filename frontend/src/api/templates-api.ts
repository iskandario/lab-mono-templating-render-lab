import type { Template } from '@/types'
import { http } from './http-client'
import { ENDPOINTS } from './endpoints'

// Backend field names differ from frontend: templateId→id, engineType→engineId, templateBody→code
interface BackendTemplate {
  templateId: string
  ownerId: string
  name: string
  engineType: string
  templateBody: string
  isPublic: boolean
  isActive: boolean
  createdAt: string
  updatedAt: string
}

function fromBackend(bt: BackendTemplate): Template {
  return {
    id: bt.templateId,
    ownerId: bt.ownerId,
    name: bt.name,
    engineId: bt.engineType,
    code: bt.templateBody,
    isPublic: bt.isPublic,
    createdAt: bt.createdAt,
    updatedAt: bt.updatedAt,
  }
}

export async function getMyTemplates(): Promise<Template[]> {
  const res = await http.get<{ items: BackendTemplate[] }>(`${ENDPOINTS.templates.list}?isActive=true`)
  return res.items.map(fromBackend)
}

export async function getPublicTemplates(): Promise<Template[]> {
  const res = await http.get<{ items: BackendTemplate[] }>(ENDPOINTS.templates.publicList)
  return res.items.map(fromBackend)
}

export async function getTemplate(id: string): Promise<Template> {
  const bt = await http.get<BackendTemplate>(ENDPOINTS.templates.byId(id))
  return fromBackend(bt)
}

export async function createTemplate(
  data: Omit<Template, 'id' | 'ownerId' | 'createdAt' | 'updatedAt'>,
): Promise<Template> {
  const bt = await http.post<BackendTemplate>(ENDPOINTS.templates.list, {
    name: data.name,
    engineType: data.engineId,
    templateBody: data.code,
    isPublic: data.isPublic,
  })
  return fromBackend(bt)
}

export async function updateTemplate(id: string, data: Partial<Template>): Promise<Template> {
  if (data.code !== undefined) {
    await http.put<{ templateId: string; updatedAt: string }>(ENDPOINTS.templates.updateBody(id), {
      templateBody: data.code,
    })
  }

  if (data.isPublic !== undefined) {
    await http.put<{ templateId: string; isPublic: boolean; updatedAt: string }>(ENDPOINTS.templates.updatePublicity(id), {
      isPublic: data.isPublic,
    })
  }

  return getTemplate(id)
}

export async function deleteTemplate(id: string): Promise<void> {
  // Backend uses deactivation instead of hard delete
  await http.post<unknown>(ENDPOINTS.templates.deactivate(id), {})
}

export async function cloneTemplate(sourceOrId: Template | string): Promise<Template> {
  // No clone endpoint. If the caller already has a public template from /templates/public,
  // use that snapshot because GET /templates/{id} is owner-scoped on the backend.
  const source = typeof sourceOrId === 'string' ? await getTemplate(sourceOrId) : sourceOrId
  return createTemplate({
    name: `${source.name} (copy)`,
    engineId: source.engineId,
    code: source.code,
    isPublic: false,
    description: source.description,
  })
}
