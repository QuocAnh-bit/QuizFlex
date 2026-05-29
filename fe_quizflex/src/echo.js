import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

let echoInstance = null

const tokenKey = 'quizflex_access_token'

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

export const createEcho = () => new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  forceTLS: reverbScheme() === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: `${apiOrigin()}/broadcasting/auth`,
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      const token = localStorage.getItem(tokenKey)

      fetch(`${apiOrigin()}/broadcasting/auth`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({
          socket_id: socketId,
          channel_name: channel.name,
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
