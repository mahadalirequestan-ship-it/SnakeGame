// Canvas setup
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const scoreElement = document.getElementById('score');
const bestScoreElement = document.getElementById('bestScore');
const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');
const clearBtn = document.getElementById('clearBtn');
const gameOverModal = document.getElementById('gameOverModal');
const finalScoreElement = document.getElementById('finalScore');
const playerNameInput = document.getElementById('playerName');

// Game configuration
const API_URL = 'api.php'; // PHP backend URL
const gridSize = 20;
const tileCount = canvas.width / gridSize;

// Adjust canvas for mobile
function adjustCanvasForMobile() {
    const isMobile = window.innerWidth <= 768;
    if (isMobile) {
        const maxWidth = Math.min(window.innerWidth - 40, 600);
        canvas.style.width = maxWidth + 'px';
        canvas.style.height = maxWidth + 'px';
    } else {
        canvas.style.width = '600px';
        canvas.style.height = '600px';
    }
}

window.addEventListener('resize', adjustCanvasForMobile);
adjustCanvasForMobile();

// Game state
let snake = [{x: 10, y: 10}];
let velocityX = 1;
let velocityY = 0;
let nextVelocityX = 1;
let nextVelocityY = 0;
let food = {x: 15, y: 15};
let score = 0;
let bestScore = 0;
let gameLoop = null;
let isPaused = false;
let gameSpeed = 120;
let gameStarted = false;

// Initialize game
function init() {
    loadHighScores();
    drawGame();
}

// Main game loop
function drawGame() {
    if (!gameStarted) return;
    
    // Update velocity from buffered input
    velocityX = nextVelocityX;
    velocityY = nextVelocityY;
    
    // Clear canvas with gradient
    const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
    gradient.addColorStop(0, '#050818');
    gradient.addColorStop(1, '#0a0e27');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Draw grid
    ctx.strokeStyle = 'rgba(57, 255, 20, 0.05)';
    ctx.lineWidth = 1;
    for (let i = 0; i < tileCount; i++) {
        ctx.beginPath();
        ctx.moveTo(i * gridSize, 0);
        ctx.lineTo(i * gridSize, canvas.height);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(0, i * gridSize);
        ctx.lineTo(canvas.width, i * gridSize);
        ctx.stroke();
    }
    
    // Move snake
    const head = {x: snake[0].x + velocityX, y: snake[0].y + velocityY};
    
    // Check wall collision
    if (head.x < 0 || head.x >= tileCount || head.y < 0 || head.y >= tileCount) {
        gameOver();
        return;
    }
    
    // Check self collision
    for (let segment of snake) {
        if (head.x === segment.x && head.y === segment.y) {
            gameOver();
            return;
        }
    }
    
    snake.unshift(head);
    
    // Check food collision
    if (head.x === food.x && head.y === food.y) {
        score++;
        scoreElement.textContent = score;
        
        // Update best score
        if (score > bestScore) {
            bestScore = score;
            bestScoreElement.textContent = bestScore;
        }
        
        placeFood();
        playEatSound();
        
        // Increase speed gradually
        if (score % 3 === 0 && gameSpeed > 60) {
            gameSpeed = Math.max(60, gameSpeed - 8);
            clearInterval(gameLoop);
            gameLoop = setInterval(drawGame, gameSpeed);
        }
    } else {
        snake.pop();
    }
    
    // Draw snake with enhanced effects
    snake.forEach((segment, index) => {
        const isHead = index === 0;
        
        // Glow effect
        ctx.shadowBlur = isHead ? 20 : 10;
        ctx.shadowColor = 'rgba(57, 255, 20, 0.9)';
        
        // Snake body gradient
        const segmentGradient = ctx.createRadialGradient(
            segment.x * gridSize + gridSize / 2,
            segment.y * gridSize + gridSize / 2,
            0,
            segment.x * gridSize + gridSize / 2,
            segment.y * gridSize + gridSize / 2,
            gridSize / 2
        );
        
        if (isHead) {
            segmentGradient.addColorStop(0, '#50ff30');
            segmentGradient.addColorStop(0.7, '#39ff14');
            segmentGradient.addColorStop(1, '#2acc0a');
        } else {
            const alpha = 1 - (index / snake.length) * 0.4;
            segmentGradient.addColorStop(0, `rgba(80, 255, 48, ${alpha})`);
            segmentGradient.addColorStop(0.7, `rgba(57, 255, 20, ${alpha})`);
            segmentGradient.addColorStop(1, `rgba(42, 204, 10, ${alpha * 0.8})`);
        }
        
        ctx.fillStyle = segmentGradient;
        
        // Rounded corners for segments
        const x = segment.x * gridSize + 1;
        const y = segment.y * gridSize + 1;
        const size = gridSize - 2;
        const radius = 4;
        
        ctx.beginPath();
        ctx.roundRect(x, y, size, size, radius);
        ctx.fill();
        
        // Head details with direction
        if (isHead) {
            ctx.shadowBlur = 0;
            ctx.fillStyle = '#0a0e27';
            const eyeSize = 4;
            const eyeOffset = 5;
            
            // Eyes based on direction
            if (velocityY === -1) { // Up
                ctx.fillRect(segment.x * gridSize + eyeOffset, segment.y * gridSize + 4, eyeSize, eyeSize);
                ctx.fillRect(segment.x * gridSize + gridSize - eyeOffset - eyeSize, segment.y * gridSize + 4, eyeSize, eyeSize);
            } else if (velocityY === 1) { // Down
                ctx.fillRect(segment.x * gridSize + eyeOffset, segment.y * gridSize + gridSize - 8, eyeSize, eyeSize);
                ctx.fillRect(segment.x * gridSize + gridSize - eyeOffset - eyeSize, segment.y * gridSize + gridSize - 8, eyeSize, eyeSize);
            } else if (velocityX === -1) { // Left
                ctx.fillRect(segment.x * gridSize + 4, segment.y * gridSize + eyeOffset, eyeSize, eyeSize);
                ctx.fillRect(segment.x * gridSize + 4, segment.y * gridSize + gridSize - eyeOffset - eyeSize, eyeSize, eyeSize);
            } else { // Right
                ctx.fillRect(segment.x * gridSize + gridSize - 8, segment.y * gridSize + eyeOffset, eyeSize, eyeSize);
                ctx.fillRect(segment.x * gridSize + gridSize - 8, segment.y * gridSize + gridSize - eyeOffset - eyeSize, eyeSize, eyeSize);
            }
        }
    });
    
    // Draw food with pulsing animation
    const pulseSize = Math.sin(Date.now() / 200) * 2 + gridSize / 2 - 2;
    ctx.shadowBlur = 25;
    ctx.shadowColor = 'rgba(255, 16, 240, 1)';
    
    const foodGradient = ctx.createRadialGradient(
        food.x * gridSize + gridSize / 2,
        food.y * gridSize + gridSize / 2,
        0,
        food.x * gridSize + gridSize / 2,
        food.y * gridSize + gridSize / 2,
        pulseSize
    );
    foodGradient.addColorStop(0, '#ff10f0');
    foodGradient.addColorStop(0.5, '#ff6ff0');
    foodGradient.addColorStop(1, '#cc0dc0');
    
    ctx.fillStyle = foodGradient;
    ctx.beginPath();
    ctx.arc(
        food.x * gridSize + gridSize / 2,
        food.y * gridSize + gridSize / 2,
        pulseSize,
        0,
        Math.PI * 2
    );
    ctx.fill();
    
    // Food sparkle effect
    ctx.shadowBlur = 0;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
    ctx.beginPath();
    ctx.arc(
        food.x * gridSize + gridSize / 2 - 3,
        food.y * gridSize + gridSize / 2 - 3,
        2,
        0,
        Math.PI * 2
    );
    ctx.fill();
}

// Place food in random position
function placeFood() {
    do {
        food.x = Math.floor(Math.random() * tileCount);
        food.y = Math.floor(Math.random() * tileCount);
    } while (snake.some(segment => segment.x === food.x && segment.y === food.y));
}

// Game over handler
function gameOver() {
    clearInterval(gameLoop);
    gameLoop = null;
    gameStarted = false;
    finalScoreElement.textContent = score;
    gameOverModal.style.display = 'flex';
    playerNameInput.focus();
    pauseBtn.disabled = true;
    
    playGameOverSound();
}

// Start new game
function startGame() {
    if (gameLoop) {
        clearInterval(gameLoop);
    }
    
    snake = [{x: 10, y: 10}];
    velocityX = 1;
    velocityY = 0;
    nextVelocityX = 1;
    nextVelocityY = 0;
    score = 0;
    gameSpeed = 120;
    isPaused = false;
    gameStarted = true;
    scoreElement.textContent = score;
    placeFood();
    
    gameLoop = setInterval(drawGame, gameSpeed);
    startBtn.textContent = 'Qaytadan';
    pauseBtn.textContent = 'Pauza';
    pauseBtn.disabled = false;
}

// Toggle pause
function togglePause() {
    if (!gameStarted || !gameLoop && !isPaused) return;
    
    isPaused = !isPaused;
    if (isPaused) {
        clearInterval(gameLoop);
        gameLoop = null;
        pauseBtn.textContent = 'Davom etish';
    } else {
        gameLoop = setInterval(drawGame, gameSpeed);
        pauseBtn.textContent = 'Pauza';
    }
}

// Close modal
function closeModal() {
    gameOverModal.style.display = 'none';
    playerNameInput.value = '';
}

// Sound effects
function playEatSound() {
    // Vibrate on mobile
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
    
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 800;
    oscillator.type = 'sine';
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.1);
}

function playGameOverSound() {
    // Vibrate on mobile
    if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]);
    }
    
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 200;
    oscillator.type = 'sawtooth';
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.5);
}

// API functions
async function saveScore() {
    const name = playerNameInput.value.trim() || 'Anonim';
    
    const formData = new FormData();
    formData.append('action', 'save_score');
    formData.append('score', score);
    formData.append('name', name);
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            loadHighScores();
            closeModal();
        } else {
            alert('Xatolik: ' + (data.error || 'Noma\'lum xatolik'));
        }
    } catch (error) {
        console.error('Error saving score:', error);
        alert('Server bilan bog\'lanishda xatolik!');
    }
}

async function loadHighScores() {
    try {
        const response = await fetch(API_URL + '?action=get_scores');
        const data = await response.json();
        
        if (data.success) {
            const scores = data.scores || [];
            
            // Update best score
            if (scores.length > 0) {
                bestScore = scores[0].score;
                bestScoreElement.textContent = bestScore;
            }
            
            const scoreList = document.getElementById('scoreList');
            if (scores.length === 0) {
                scoreList.innerHTML = `
                    <li class="score-item">
                        <span class="score-rank">#</span>
                        <span class="score-name">Hali rekordlar yo'q</span>
                        <span class="score-points">-</span>
                    </li>
                `;
            } else {
                scoreList.innerHTML = scores.map((s, i) => `
                    <li class="score-item">
                        <span class="score-rank">#${i + 1}</span>
                        <span class="score-name">${s.name}</span>
                        <span class="score-points">${s.score} ball</span>
                    </li>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading scores:', error);
    }
}

async function clearScores() {
    if (!confirm('Barcha rekordlarni o\'chirmoqchimisiz?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clear_scores');
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            bestScore = 0;
            bestScoreElement.textContent = 0;
            loadHighScores();
        }
    } catch (error) {
        console.error('Error clearing scores:', error);
    }
}

// Event listeners
startBtn.addEventListener('click', startGame);
pauseBtn.addEventListener('click', togglePause);
clearBtn.addEventListener('click', clearScores);

// Mobile touch controls
const mobileControls = document.getElementById('mobileControls');
if (mobileControls) {
    const dpadButtons = mobileControls.querySelectorAll('.dpad-btn');
    dpadButtons.forEach(btn => {
        btn.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const direction = btn.getAttribute('data-direction');
            handleDirectionChange(direction);
        });
        
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const direction = btn.getAttribute('data-direction');
            handleDirectionChange(direction);
        });
    });
}

// Swipe detection for mobile
let touchStartX = 0;
let touchStartY = 0;
let touchEndX = 0;
let touchEndY = 0;

canvas.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    touchStartY = e.changedTouches[0].screenY;
}, { passive: true });

canvas.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    touchEndY = e.changedTouches[0].screenY;
    handleSwipe();
}, { passive: true });

function handleSwipe() {
    if (!gameStarted) return;
    
    const deltaX = touchEndX - touchStartX;
    const deltaY = touchEndY - touchStartY;
    const minSwipeDistance = 30;
    
    if (Math.abs(deltaX) > Math.abs(deltaY)) {
        // Horizontal swipe
        if (Math.abs(deltaX) > minSwipeDistance) {
            if (deltaX > 0) {
                handleDirectionChange('right');
            } else {
                handleDirectionChange('left');
            }
        }
    } else {
        // Vertical swipe
        if (Math.abs(deltaY) > minSwipeDistance) {
            if (deltaY > 0) {
                handleDirectionChange('down');
            } else {
                handleDirectionChange('up');
            }
        }
    }
}

function handleDirectionChange(direction) {
    if (!gameStarted) return;
    
    switch(direction) {
        case 'up':
            if (velocityY !== 1) {
                nextVelocityX = 0;
                nextVelocityY = -1;
            }
            break;
        case 'down':
            if (velocityY !== -1) {
                nextVelocityX = 0;
                nextVelocityY = 1;
            }
            break;
        case 'left':
            if (velocityX !== 1) {
                nextVelocityX = -1;
                nextVelocityY = 0;
            }
            break;
        case 'right':
            if (velocityX !== -1) {
                nextVelocityX = 1;
                nextVelocityY = 0;
            }
            break;
    }
}

// Keyboard controls
document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && gameOverModal.style.display === 'flex') {
        saveScore();
        return;
    }
    
    if (!gameStarted) return;
    
    switch(e.key) {
        case 'ArrowUp':
        case 'w':
        case 'W':
            handleDirectionChange('up');
            e.preventDefault();
            break;
        case 'ArrowDown':
        case 's':
        case 'S':
            handleDirectionChange('down');
            e.preventDefault();
            break;
        case 'ArrowLeft':
        case 'a':
        case 'A':
            handleDirectionChange('left');
            e.preventDefault();
            break;
        case 'ArrowRight':
        case 'd':
        case 'D':
            handleDirectionChange('right');
            e.preventDefault();
            break;
        case ' ':
            togglePause();
            e.preventDefault();
            break;
    }
});

// Prevent zoom on double tap for mobile
document.addEventListener('touchend', (e) => {
    const now = new Date().getTime();
    const timeSince = now - lastTap;
    
    if (timeSince < 600 && timeSince > 0) {
        e.preventDefault();
    }
    
    lastTap = now;
}, { passive: false });

let lastTap = 0;

// Initialize on page load
init();