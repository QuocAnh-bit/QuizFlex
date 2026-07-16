import { Howl } from 'howler'
import { Howl, Howler } from 'howler'

class AudioService {
    constructor() {
        this.sounds = {
            lobby: new Howl({
                src: ['/sounds/lobby.mp3'],
                loop: true,
                volume: 0.3
            }),

            countdown: new Howl({
                src: ['/sounds/countdown.mp3'],
                volume: 1
            }),

            correct: new Howl({
                src: ['/sounds/correct.mp3'],
                volume: 1
            }),

            wrong: new Howl({
                src: ['/sounds/wrong.mp3'],
                volume: 1
            }),

            finish: new Howl({
                src: ['/sounds/finish.mp3'],
                volume: 1
            })
        }
    }
playLobby() {
    this.sounds.lobby.stop()   // nếu đang phát thì dừng trước
    this.sounds.lobby.play()
}
stopLobby() {
    console.log("STOP LOBBY")
    Howler.stop()
}

stopAll() {
    Object.values(this.sounds).forEach(sound => sound.stop())
}
playCountdown() {
    this.sounds.countdown.stop()
    this.sounds.countdown.play()
}

playCorrect() {
    this.sounds.correct.stop()
    this.sounds.correct.play()
}

playWrong() {
    this.sounds.wrong.stop()
    this.sounds.wrong.play()
}

playFinish() {
    this.stopAll()
    this.sounds.finish.play()
}
}

export default new AudioService()