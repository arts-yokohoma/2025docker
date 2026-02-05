/* assets/main.js */

// --- Audio Notification Logic (For Admin) ---
function initAudio() {
    if (sessionStorage.getItem("audio_enabled") === "true") {
        const overlay = document.getElementById('audioOverlay');
        if(overlay) overlay.style.display = 'none';
    }
}

function enableAudio() {
    const sound = document.getElementById('notifSound');
    if(sound) {
        sound.play().then(() => {
            sound.pause();
            sound.currentTime = 0;
            sessionStorage.setItem("audio_enabled", "true");
            document.getElementById('audioOverlay').style.display = 'none';
        }).catch(e => console.log("Audio Blocked: " + e));
    }
}

// --- Timer Logic (For Customer) ---
function startTimer(duration, displayId) {
    let timeLeft = duration;
    const timerElement = document.getElementById(displayId);
    
    if (!timerElement) return;

    function update() {
        if (timeLeft <= 0) {
            timerElement.innerHTML = "00:00";
            timerElement.style.color = "red";
            return;
        }
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        
        // Pad with leading zeros
        let mStr = minutes.toString().padStart(2, '0');
        let sStr = seconds.toString().padStart(2, '0');
        
        timerElement.innerHTML = mStr + ":" + sStr;
        timeLeft--;
    }
    
    update();
    setInterval(update, 1000);
}

// Run on load
document.addEventListener("DOMContentLoaded", function() {
    initAudio();
});