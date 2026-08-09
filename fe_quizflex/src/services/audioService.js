import { Howl, Howler } from "howler";

class AudioService {
    constructor() {
        this.isFinished = false;

        this.sounds = {
            lobby: new Howl({
                src: ["/sounds/lobby.mp3"],
                loop: true,
                volume: 0.3,
            }),

            countdown: new Howl({
                src: ["/sounds/countdown.mp3"],
                volume: 1,
            }),

            correct: new Howl({
                src: ["/sounds/correct.mp3"],
                volume: 1,
            }),

            wrong: new Howl({
                src: ["/sounds/wrong.mp3"],
                volume: 1,
            }),

            finish: new Howl({
                src: ["/sounds/finish.mp3"],
                volume: 1,
            }),
        };
    }

    playLobby() {
        if (this.isFinished) return;

        this.sounds.lobby.stop();
        this.sounds.lobby.play();
    }

    stopLobby() {
        Howler.stop();
    }

    stopAll() {
        Object.values(this.sounds).forEach((sound) => {
            sound.stop();
        });
    }

    playCountdown() {
        if (this.isFinished) return;

        this.sounds.countdown.stop();
        this.sounds.countdown.play();
    }

    playCorrect() {
        if (this.isFinished) return;

        this.sounds.correct.stop();
        this.sounds.correct.play();
    }

    playWrong() {
        if (this.isFinished) return;

        this.sounds.wrong.stop();
        this.sounds.wrong.play();
    }
    
    playFinish() {
        this.stopAll()
        this.sounds.finish.stop()
        this.sounds.finish.play()
    }
}

export default new AudioService();