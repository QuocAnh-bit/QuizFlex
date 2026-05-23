import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_REVERB_APP_KEY || 'quizflex-key',
  cluster: import.meta.env.VITE_REVERB_APP_CLUSTER || 'mt1',
  wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
  wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
})

if (import.meta.env.DEV) {
  const connection = echo.connector?.pusher?.connection
  connection?.bind('connected', () => {
    console.info('[QuizFlex realtime] connected')
  })
  connection?.bind('unavailable', () => {
    console.warn('[QuizFlex realtime] unavailable. Is `php artisan reverb:start --host=0.0.0.0 --port=8080` running?')
  })
  connection?.bind('error', (error) => {
    console.warn('[QuizFlex realtime] connection error', error)
  })
}

export default echo
