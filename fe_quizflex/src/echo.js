import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

let echoInstance = null

const tokenKey = 'quizflex_access_token'

const generateTabId = () => {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return 'tab-' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36)
}

// Tab ID độc lập cho từng tab trình duyệt (sử dụng sessionStorage - không chia sẻ giữa các tab)
let tabId = null
export const getTabId = () => {
  if (!tabId) {
    if (typeof sessionStorage !== 'undefined') {
      tabId = sessionStorage.getItem('quizflex_tab_id')
      if (!tabId) {
        tabId = generateTabId()
        sessionStorage.setItem('quizflex_tab_id', tabId)
      }
    } else {
      tabId = generateTabId()
    }
  }
  return tabId
}

const apiOrigin = () => {
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
  if (!apiBaseUrl.startsWith('http')) return ''

  try {
    return new URL(apiBaseUrl).origin
  } catch {
    return ''
  }
}

const reverbScheme = () => import.meta.env.VITE_REVERB_SCHEME || 'http'

export const createEcho = () => {
  const currentTabId = getTabId()

  const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    forceTLS: reverbScheme() === 'https',
    enabledTransports: ['ws', 'wss'],
    unavailableTimeout: 2000,
    maxReconnectionAttempts: 2,
    authEndpoint: `${apiOrigin()}/broadcasting/auth`,
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        const token = localStorage.getItem(tokenKey)
        const tabId = getTabId()

        fetch(`${apiOrigin()}/broadcasting/auth?tab_id=${encodeURIComponent(tabId)}`, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Tab-Id': tabId,
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
            tab_id: tabId,
          }),
        })
          .then(async (response) => {
            const data = await response.json().catch(() => ({}))
            if (!response.ok) throw data
            callback(false, data)
          })
          .catch((error) => callback(true, error))
      },
    }),
  })

  echo.connector?.pusher?.connection?.bind('state_change', (state) => {
    console.log('[realtime]', 'connection.state_change', new Date().toISOString(), state)
  })
  echo.connector?.pusher?.connection?.bind('error', (error) => {
    console.log('[realtime]', 'connection.error', new Date().toISOString(), error)
  })

  return echo
}

export const getEcho = () => {
  if (!echoInstance) {
    echoInstance = createEcho()
  }

  return echoInstance
}

export const disconnectEcho = () => {
  if (!echoInstance) return

  echoInstance.disconnect()
  echoInstance = null
}

export default getEcho
