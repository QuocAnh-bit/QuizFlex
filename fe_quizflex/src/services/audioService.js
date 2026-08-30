import { Howl, Howler } from "howler";

class AudioService {
    constructor() {
        this.isFinished = false;
        this.isActive = false;
        const savedMuted = localStorage.getItem("quizflex_audio_muted") === "true";
        this.isMuted = savedMuted;
        Howler.mute(this.isMuted);

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

        this.handleInteraction = this.handleInteraction.bind(this);
    }

    setupInteractionListener() {
        if (typeof window === "undefined") return;
        this.removeInteractionListener();
        window.addEventListener("click", this.handleInteraction, { once: true });
        window.addEventListener("keydown", this.handleInteraction, { once: true });
    }

    removeInteractionListener() {
        if (typeof window === "undefined") return;
        window.removeEventListener("click", this.handleInteraction);
        window.removeEventListener("keydown", this.handleInteraction);
    }

    handleInteraction() {
        if (this.isActive && !this.isMuted && !this.isFinished) {
            if (this.sounds.lobby && !this.sounds.lobby.playing()) {
                this.playLobby();
            }
        }
    }

    toggleMute() {
        this.isMuted = !this.isMuted;
        localStorage.setItem("quizflex_audio_muted", String(this.isMuted));
        Howler.mute(this.isMuted);
        if (this.isMuted) {
            this.stopAll();
        } else if (this.isActive && !this.isFinished) {
            this.playLobby();
        }
        return this.isMuted;
    }

    setMuted(muted) {
        this.isMuted = Boolean(muted);
        localStorage.setItem("quizflex_audio_muted", String(this.isMuted));
        Howler.mute(this.isMuted);
        if (this.isMuted) {
            this.stopAll();
        } else if (this.isActive && !this.isFinished) {
            this.playLobby();
        }
    }

    playLobby() {
        this.isActive = true;
        this.isFinished = false;
        if (this.isMuted) return;

        if (this.sounds.lobby && this.sounds.lobby.playing()) {
            return;
        }

        try {
            Howler.stop();
            this.sounds.lobby.play();
            if (Howler.ctx && Howler.ctx.state === "suspended") {
                Howler.ctx.resume();
            }
        } catch (e) {
            console.warn("Lobby audio autoplay blocked", e);
            this.setupInteractionListener();
        }
    }

    stopLobby() {
        if (this.sounds.lobby) {
            this.sounds.lobby.stop();
        }
    }

    stopAll() {
        this.isActive = false;
        this.removeInteractionListener();
        Howler.stop();
        Object.values(this.sounds).forEach((sound) => {
            if (sound) sound.stop();
        });
    }

    playCountdown() {
        if (!this.isActive || this.isFinished || this.isMuted) return;

        this.sounds.countdown.stop();
        this.sounds.countdown.play();
    }

    playCorrect() {
        if (!this.isActive || this.isFinished || this.isMuted) return;

        this.sounds.correct.stop();
        this.sounds.correct.play();
    }

    playWrong() {
        if (!this.isActive || this.isFinished || this.isMuted) return;

        this.sounds.wrong.stop();
        this.sounds.wrong.play();
    }

    playFinish() {
        this.isFinished = true;
        Howler.stop();
        Object.values(this.sounds).forEach((sound) => {
            if (sound) sound.stop();
        });
        if (!this.isMuted && this.sounds.finish) {
            this.sounds.finish.stop();
            this.sounds.finish.play();
        }
    }
}

export default new AudioService();
