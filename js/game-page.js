import { api } from './api.js';
import { drawGame } from './render.js';
import { createGameController } from './game-core.js';

const mode = window.GAME_MODE || 'practice';

window.saveScoreFromPage = (payload) => api.saveScore(payload);

const dom = {
    canvas: document.getElementById('gameCanvas'),
    ctx: document.getElementById('gameCanvas').getContext('2d'),
    levelSelect: document.getElementById('levelSelect'),
    startBtn: document.getElementById('startBtn'),
    messageBox: document.getElementById('message'),
    statsBox: document.getElementById('stats'),
    overlay: document.getElementById('overlay'),
    hudPoints: document.getElementById('hudPoints'),
    hudTitle: document.getElementById('hudTitle'),
};

const currentUser = JSON.parse(document.getElementById('currentUserData').textContent);
const controller = createGameController(dom, currentUser);

const state = {
    levels: [],
    challengeLives: 3,
    challengeIndex: 0,
    waitingForRestartAfterGame: false,
    transitioning: false,
};

function renderLives() {
    const hudLives = document.getElementById('hudLives');
    if (hudLives) {
        hudLives.textContent = String(state.challengeLives);
    }
}

function updateStats() {
    const game = controller.state.game;
    const currentLevel = controller.state.currentLevel;

    if (!game || !currentLevel) {
        dom.statsBox.innerHTML = '';
        return;
    }

    const keysLeft = game.keys.filter((key) => !key.collected).length;
    const elapsedMs = game.finalTimeMs ?? (game.started ? Date.now() - game.startTime : 0);
    const seconds = (elapsedMs / 1000).toFixed(2);

    const lines = [
        `<div><strong>Level:</strong> ${currentLevel.name}</div>`,
        `<div><strong>Keys left:</strong> ${keysLeft}</div>`,
        `<div><strong>Moves:</strong> ${game.moves}</div>`,
        `<div><strong>Time:</strong> ${seconds}s</div>`,
        `<div><strong>Enemies:</strong> ${game.enemies.length}</div>`,
    ];

    if (mode === 'challenge') {
        lines.push(`<div><strong>Run stage:</strong> ${state.challengeIndex + 1} / ${state.levels.length}</div>`);
        lines.push(`<div><strong>Lives:</strong> ${state.challengeLives}</div>`);
    }

    dom.statsBox.innerHTML = lines.join('');
}

async function loadInitialData() {
    const levelsData = await api.loadLevels();
    state.levels = levelsData.levels || [];

    if (mode === 'practice' && dom.levelSelect) {
        dom.levelSelect.innerHTML = '';

        state.levels.forEach((level) => {
            const option = document.createElement('option');
            option.value = level.id;
            option.textContent = `${level.name} (Difficulty ${level.difficulty})`;
            dom.levelSelect.appendChild(option);
        });
    }

    if (mode === 'challenge') {
        state.challengeLives = 3;
        renderLives();
    }
}

async function startPractice() {
    const levelId = dom.levelSelect?.value;

    if (!levelId) {
        controller.showMessage('Choose a level first.', 'error');
        return;
    }

    const levelData = await api.loadLevel(levelId);
    controller.showMessage(`Get ready for ${levelData.name}...`, 'info');
    await controller.startLevel(levelData);
}

async function startChallenge() {
    if (!state.levels.length) {
        throw new Error('No levels found.');
    }

    if (state.waitingForRestartAfterGame) {
        state.challengeIndex = 0;
        state.challengeLives = 3;
        state.waitingForRestartAfterGame = false;
        renderLives();
        controller.hideOverlay();
    }

    if (state.challengeIndex < 0 || state.challengeIndex >= state.levels.length) {
        state.challengeIndex = 0;
    }

    const levelInfo = state.levels[state.challengeIndex];
    const levelData = await api.loadLevel(levelInfo.id);

    controller.showMessage(`Get ready for ${levelData.name}...`, 'info');
    await controller.startLevel(levelData);
}

async function handleWin() {
    if (state.transitioning) return;
    state.transitioning = true;

    controller.finishCurrentFrame();
    controller.showOverlay('ESCAPED', 'success');

    let result = null;

    try {
        result = await controller.saveResult({
            won: true,
            mode,
        });

        const hudUser =
            result?.user ||
            (result && (result.points !== undefined || result.title !== undefined) ? result : null);

        if (hudUser) {
            controller.updateHud(hudUser);
        } else if (result?.points_earned && controller.state.currentUser) {
            controller.updateHud({
                ...controller.state.currentUser,
                points: Number(controller.state.currentUser.points || 0) + Number(result.points_earned || 0),
            });
        }

        if (mode === 'practice') {
            controller.showMessage(
                result
                    ? `Level completed. +${result.points_earned ?? 0} points. Press Start Level to try another level.`
                    : 'Level completed. Press Start Level to try another level.',
                'success'
            );
            return;
        }

        state.challengeLives += 1;
        renderLives();

        state.challengeIndex += 1;

        const lastLevelCompleted = state.challengeIndex >= state.levels.length;

        if (lastLevelCompleted) {
            state.challengeIndex = 0;
            state.challengeLives = 3;
            renderLives();

            controller.showMessage(
                result
                    ? `Challenge complete. +${result.points_earned ?? 0} points. Press Start Run to begin again from Level 1.`
                    : 'Challenge complete. Press Start Run to begin again from Level 1.',
                'success'
            );
        } else {
            controller.showMessage(
                result
                    ? `Level cleared. +${result.points_earned ?? 0} points. Bonus life awarded. Press Start Run for the next level.`
                    : 'Level cleared. Bonus life awarded. Press Start Run for the next level.',
                'success'
            );
        }
    } catch (error) {
        console.error('Could not save win result:', error);
        controller.showMessage('Level completed, but score could not be saved.', 'error');
    } finally {
        state.transitioning = false;
    }
}

async function handleLose() {
    if (state.transitioning) return;
    state.transitioning = true;

    controller.finishCurrentFrame();
    controller.triggerCaughtEffect();

    let result = null;

    try {
        result = await controller.saveResult({
            won: false,
            mode,
        });

        const hudUser =
            result?.user ||
            (result && (result.points !== undefined || result.title !== undefined) ? result : null);

        if (hudUser) {
            controller.updateHud(hudUser);
        }

        if (mode === 'practice') {
            controller.showMessage('You were caught. Practice round over.', 'error');
            return;
        }

        state.challengeLives -= 1;
        renderLives();

        if (state.challengeLives <= 0) {
            state.challengeLives = 0;
            state.waitingForRestartAfterGame = true;
            renderLives();
            controller.showOverlay('GAME OVER', 'danger');
            controller.showMessage('No lives left. Press Start Run to begin again from Level 1.', 'error');
            return;
        }

        controller.showMessage('You were caught. Press Start Run to retry this level.', 'error');
    } catch (error) {
        console.error('Could not save lose result:', error);
        controller.showMessage('You were caught, but score could not be saved.', 'error');
    } finally {
        state.transitioning = false;
    }
}

function bindControls() {
    dom.startBtn.addEventListener('click', async () => {
        try {
            if (mode === 'practice') {
                await startPractice();
            } else {
                await startChallenge();
            }
        } catch (error) {
            console.error(error);
            controller.showMessage(error.message || 'Could not start game.', 'error');
        }
    });

    document.addEventListener('keydown', (event) => {
        const blocked = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', ' '];

        if (blocked.includes(event.key)) {
            event.preventDefault();
        }

        controller.state.pressedKeys[event.key] = true;
    });

    document.addEventListener('keyup', (event) => {
        delete controller.state.pressedKeys[event.key];
    });
}

controller.gameLoop(
    (game, currentLevel, allKeysCollected) => {
        drawGame(dom.ctx, dom.canvas, game, currentLevel, allKeysCollected);
        updateStats();
    },
    async () => {
        const game = controller.state.game;

        if (!game || game.finished || game.countdownActive || state.transitioning) {
            return;
        }

        if (controller.playerCaught()) {
            handleLose();
            return;
        }

        if (controller.playerExited()) {
            handleWin();
        }
    }
);

(async function init() {
    controller.updateHud(currentUser);
    await loadInitialData();
    renderLives();

    if (mode === 'practice') {
        controller.showMessage('Choose a level and press Start Level.', 'info');
    } else {
        controller.showMessage('Press Start Run when ready.', 'info');
    }

    bindControls();
})().catch((error) => {
    console.error(error);
    controller.showMessage('Could not initialise the game page.', 'error');
});