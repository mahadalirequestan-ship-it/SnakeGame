<?php
session_start();

// High scores management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save_score') {
        $score = (int)$_POST['score'];
        $name = htmlspecialchars($_POST['name'] ?? 'Anonim');
        
        if (!isset($_SESSION['high_scores'])) {
            $_SESSION['high_scores'] = [];
        }
        
        $_SESSION['high_scores'][] = [
            'name' => $name,
            'score' => $score,
            'date' => date('Y-m-d H:i')
        ];
        
        usort($_SESSION['high_scores'], function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $_SESSION['high_scores'] = array_slice($_SESSION['high_scores'], 0, 10);
        
        echo json_encode(['success' => true, 'scores' => $_SESSION['high_scores']]);
        exit;
    }
    
    if ($_POST['action'] === 'get_scores') {
        echo json_encode($_SESSION['high_scores'] ?? []);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snake - Klassik O'yin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #39ff14;
            --neon-pink: #ff10f0;
            --neon-blue: #00f0ff;
            --dark-bg: #0a0e27;
            --grid-color: #1a1f3a;
            --snake-color: #39ff14;
            --food-color: #ff10f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Orbitron', monospace;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #0f1629 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--neon-green);
            overflow: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 2px,
                    rgba(57, 255, 20, 0.03) 2px,
                    rgba(57, 255, 20, 0.03) 4px
                );
            pointer-events: none;
            animation: scanlines 8s linear infinite;
        }
        
        @keyframes scanlines {
            0% { transform: translateY(0); }
            100% { transform: translateY(50px); }
        }
        
        .container {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 20px;
        }
        
        h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 8px;
            text-shadow: 
                0 0 10px var(--neon-green),
                0 0 20px var(--neon-green),
                0 0 30px var(--neon-green),
                0 0 40px var(--neon-green);
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from {
                text-shadow: 
                    0 0 10px var(--neon-green),
                    0 0 20px var(--neon-green),
                    0 0 30px var(--neon-green);
            }
            to {
                text-shadow: 
                    0 0 20px var(--neon-green),
                    0 0 30px var(--neon-green),
                    0 0 40px var(--neon-green),
                    0 0 50px var(--neon-green);
            }
        }
        
        .subtitle {
            font-family: 'Courier Prime', monospace;
            font-size: 1.2rem;
            color: var(--neon-blue);
            margin-bottom: 30px;
            text-shadow: 0 0 10px var(--neon-blue);
        }
        
        .game-wrapper {
            display: inline-block;
            position: relative;
            padding: 20px;
            background: rgba(10, 14, 39, 0.8);
            border: 3px solid var(--neon-green);
            border-radius: 15px;
            box-shadow: 
                0 0 20px var(--neon-green),
                inset 0 0 20px rgba(57, 255, 20, 0.1);
        }
        
        #gameCanvas {
            border: 2px solid var(--neon-blue);
            background: #050818;
            box-shadow: 
                0 0 30px rgba(0, 240, 255, 0.3),
                inset 0 0 30px rgba(0, 240, 255, 0.05);
            border-radius: 8px;
        }
        
        .controls {
            margin-top: 30px;
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .score-display {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--neon-pink);
            text-shadow: 0 0 10px var(--neon-pink);
            padding: 15px 30px;
            background: rgba(255, 16, 240, 0.1);
            border: 2px solid var(--neon-pink);
            border-radius: 10px;
            min-width: 200px;
        }
        
        .btn {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            padding: 15px 35px;
            border: 2px solid var(--neon-green);
            background: transparent;
            color: var(--neon-green);
            cursor: pointer;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(57, 255, 20, 0.3);
        }
        
        .btn:hover {
            background: var(--neon-green);
            color: #0a0e27;
            box-shadow: 0 0 30px var(--neon-green);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn:disabled:hover {
            background: transparent;
            transform: none;
        }
        
        .btn.pause {
            border-color: var(--neon-blue);
            color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.3);
        }
        
        .btn.pause:hover {
            background: var(--neon-blue);
            color: #0a0e27;
            box-shadow: 0 0 30px var(--neon-blue);
        }
        
        .high-scores {
            margin-top: 40px;
            padding: 25px;
            background: rgba(10, 14, 39, 0.8);
            border: 2px solid var(--neon-pink);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(255, 16, 240, 0.3);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .high-scores h2 {
            font-size: 2rem;
            color: var(--neon-pink);
            margin-bottom: 20px;
            text-shadow: 0 0 10px var(--neon-pink);
        }
        
        .score-list {
            list-style: none;
        }
        
        .score-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            margin: 8px 0;
            background: rgba(255, 16, 240, 0.05);
            border: 1px solid rgba(255, 16, 240, 0.3);
            border-radius: 8px;
            font-family: 'Courier Prime', monospace;
            transition: all 0.3s ease;
        }
        
        .score-item:hover {
            background: rgba(255, 16, 240, 0.15);
            border-color: var(--neon-pink);
            transform: translateX(5px);
        }
        
        .score-rank {
            color: var(--neon-blue);
            font-weight: 700;
            margin-right: 15px;
        }
        
        .score-name {
            color: var(--neon-green);
            flex-grow: 1;
            text-align: left;
        }
        
        .score-points {
            color: var(--neon-pink);
            font-weight: 700;
        }
        
        .game-over-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            padding: 40px;
            border-radius: 20px;
            border: 3px solid var(--neon-pink);
            box-shadow: 0 0 40px var(--neon-pink);
            text-align: center;
            max-width: 500px;
        }
        
        .modal-content h2 {
            font-size: 3rem;
            color: var(--neon-pink);
            margin-bottom: 20px;
            text-shadow: 0 0 20px var(--neon-pink);
        }
        
        .modal-content input {
            font-family: 'Courier Prime', monospace;
            font-size: 1.2rem;
            padding: 15px;
            margin: 20px 0;
            background: rgba(255, 16, 240, 0.1);
            border: 2px solid var(--neon-pink);
            border-radius: 10px;
            color: var(--neon-green);
            width: 100%;
            text-align: center;
        }
        
        .modal-content input:focus {
            outline: none;
            box-shadow: 0 0 20px var(--neon-pink);
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
                letter-spacing: 4px;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SNAKE</h1>
        <div class="subtitle">[ Retro Neon Edition ]</div>
        
        <div class="game-wrapper">
            <canvas id="gameCanvas" width="600" height="600"></canvas>
        </div>
        
        <div class="controls">
            <div class="score-display">
                Score: <span id="score">0</span>
            </div>
            <button class="btn" id="startBtn">Boshlash</button>
            <button class="btn pause" id="pauseBtn">Pauza</button>
        </div>
        
        <div class="high-scores">
            <h2>🏆 Top 10</h2>
            <ul class="score-list" id="scoreList">
                <li class="score-item">
                    <span class="score-rank">#</span>
                    <span class="score-name">Hali rekordlar yo'q</span>
                    <span class="score-points">-</span>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="game-over-modal" id="gameOverModal">
        <div class="modal-content">
            <h2>O'YIN TUGADI!</h2>
            <p style="font-size: 1.5rem; color: var(--neon-green); margin: 20px 0;">
                Sizning balingiz: <span id="finalScore">0</span>
            </p>
            <input type="text" id="playerName" placeholder="Ismingizni kiriting" maxlength="20">
            <button class="btn" onclick="saveScore()">Saqlash</button>
            <button class="btn pause" onclick="closeModal()">Yopish</button>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const scoreElement = document.getElementById('score');
        const startBtn = document.getElementById('startBtn');
        const pauseBtn = document.getElementById('pauseBtn');
        const gameOverModal = document.getElementById('gameOverModal');
        const finalScoreElement = document.getElementById('finalScore');
        const playerNameInput = document.getElementById('playerName');
        
        const gridSize = 20;
        const tileCount = canvas.width / gridSize;
        
        let snake = [{x: 10, y: 10}];
        let velocityX = 1;
        let velocityY = 0;
        let nextVelocityX = 1;
        let nextVelocityY = 0;
        let food = {x: 15, y: 15};
        let score = 0;
        let gameLoop = null;
        let isPaused = false;
        let gameSpeed = 120;
        let gameStarted = false;
        
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
                placeFood();
                
                // Play sound effect (browser beep)
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
        
        function playEatSound() {
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
        
        function placeFood() {
            do {
                food.x = Math.floor(Math.random() * tileCount);
                food.y = Math.floor(Math.random() * tileCount);
            } while (snake.some(segment => segment.x === food.x && segment.y === food.y));
        }
        
        function gameOver() {
            clearInterval(gameLoop);
            gameLoop = null;
            gameStarted = false;
            finalScoreElement.textContent = score;
            gameOverModal.style.display = 'flex';
            playerNameInput.focus();
            pauseBtn.disabled = true;
            
            // Play game over sound
            playGameOverSound();
        }
        
        function playGameOverSound() {
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
        
        function closeModal() {
            gameOverModal.style.display = 'none';
            playerNameInput.value = '';
        }
        
        async function saveScore() {
            const name = playerNameInput.value.trim() || 'Anonim';
            
            const formData = new FormData();
            formData.append('action', 'save_score');
            formData.append('score', score);
            formData.append('name', name);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    loadHighScores();
                    closeModal();
                }
            } catch (error) {
                console.error('Error saving score:', error);
            }
        }
        
        async function loadHighScores() {
            const formData = new FormData();
            formData.append('action', 'get_scores');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const scores = await response.json();
                
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
            } catch (error) {
                console.error('Error loading scores:', error);
            }
        }
        
        // Event listeners
        startBtn.addEventListener('click', startGame);
        pauseBtn.addEventListener('click', togglePause);
        
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
                    if (velocityY !== 1) {
                        nextVelocityX = 0;
                        nextVelocityY = -1;
                    }
                    e.preventDefault();
                    break;
                case 'ArrowDown':
                case 's':
                case 'S':
                    if (velocityY !== -1) {
                        nextVelocityX = 0;
                        nextVelocityY = 1;
                    }
                    e.preventDefault();
                    break;
                case 'ArrowLeft':
                case 'a':
                case 'A':
                    if (velocityX !== 1) {
                        nextVelocityX = -1;
                        nextVelocityY = 0;
                    }
                    e.preventDefault();
                    break;
                case 'ArrowRight':
                case 'd':
                case 'D':
                    if (velocityX !== -1) {
                        nextVelocityX = 1;
                        nextVelocityY = 0;
                    }
                    e.preventDefault();
                    break;
                case ' ':
                    togglePause();
                    e.preventDefault();
                    break;
            }
        });
        
        // Initial draw
        drawGame();
        loadHighScores();
    </script>
</body>
</html>