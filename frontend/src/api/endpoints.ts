// Все URL endpoint'ы для всех API-модулей.
export const ENDPOINTS = {
  auth: {
    login: '/sessions',
    current: '/sessions/current',
    register: '/users',
    logout: '/sessions/current',
    forgotPassword: '/auth/forgot-password', // not in backend yet
    changePassword: '/auth/change-password', // not in backend yet
  },
  templates: {
    list: '/templates',
    publicList: '/templates/public',
    byId: (id: string) => `/templates/${id}`,
    updateBody: (id: string) => `/templates/${id}/body`,
    updatePublicity: (id: string) => `/templates/${id}/publicity`,
    deactivate: (id: string) => `/templates/${id}/deactivation`,
  },
  renderRuns: {
    list: '/render-runs',
    create: '/render-runs',
  },
  benchmarkRuns: {
    list: '/benchmark-runs',
    create: '/benchmark-runs',
    success: (id: string) => `/benchmark-runs/${id}/success`,
    failure: (id: string) => `/benchmark-runs/${id}/failure`,
  },
  state: {
    save: '/state',
    byId: (id: string) => `/state/${id}`,
  },
}
