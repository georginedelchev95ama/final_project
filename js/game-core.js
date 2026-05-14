import { normalizeEnemies, randomOpenPositions, validateLevel } from './level-utils.js';
import { bfsNextStep } from './pathfinding.js';

export function createGameController(dom, currentUser) {
    const state = {
        currentUser,
        currentLevel: null,
        game: null,
        pressedKeys: {},
        animationId: null,
    };

    function isWall(x, y) {
        const game = state.game;

        if (!game) return true;
        if (x < 0 || y < 0 || x >= game.width || y >= game.height) return true;

        return game.walls[y][x] === 1;
    }

    function updateHud(user) {
        if (!user) return;

        state.currentUser = { ...state.currentUser, ...user };

        if (dom.hudPoints) dom.hudPoints.textContent = state.currentUser.points ?? 0;
        if (dom.hudTitle) dom.hudTitle.textContent = state.currentUser.title ?? 'New Player';
    }

    function showMessage(text, type = 'info') {
        if (!dom.messageBox) return;

        dom.messageBox.textContent = text;
        dom.messageBox.dataset.type = type;
    }

    function showOverlay(text, className = '') {
        if (!dom.overlay) return;

        dom.overlay.textContent = text;
        dom.overlay.className = `game-overlay ${className}`.trim();
    }

    function hideOverlay() {
        if (!dom.overlay) return;

        dom.overlay.textContent = '';
        dom.overlay.className = 'game-overlay hidden';
    }

    function allKeysCollected() {
        if (!state.game) return false;
        return state.game.keys.every((key) => key.collected);
    }

    function collectKeys() {
        if (!state.game) return;

        state.game.keys.forEach((key) => {
            if (
                !key.collected &&
                key.x === state.game.player.x &&
                key.y === state.game.player.y
            ) {
                key.collected = true;
                state.game.keysCollected += 1;
                showMessage('Key collected.', 'success');
            }
        });
    }

    async function saveResult({ won, mode }) {
        if (!state.game || !state.currentLevel) return null;

        const payload = {
            level_id: state.currentLevel.id,
            time_ms: Math.max(0, state.game.finalTimeMs ?? (Date.now() - state.game.startTime)),
            moves: state.game.moves,
            won: won ? '1' : '0',
            keys_collected: state.game.keysCollected,
            mode,
        };

        if (typeof window.saveScoreFromPage === 'function') {
            return window.saveScoreFromPage(payload);
        }

        return null;
    }

    function triggerCaughtEffect() {
        if (!dom.canvas) return;

        dom.canvas.classList.add('caught');
        showOverlay('CAUGHT', 'danger');

        setTimeout(() => {
            dom.canvas.classList.remove('caught');
        }, 900);
    }

    async function runCountdown() {
        if (!state.game) return;

        state.game.countdownActive = true;

        const levelLabel =
            state.currentLevel?.name ||
            (state.currentLevel?.difficulty ? `LEVEL ${state.currentLevel.difficulty}` : 'GET READY');

        showOverlay(levelLabel, 'countdown');
        await new Promise((resolve) => setTimeout(resolve, 1500));

        for (const step of ['3', '2', '1', 'GO']) {
            showOverlay(step, 'countdown');
            await new Promise((resolve) => setTimeout(resolve, step === 'GO' ? 500 : 800));
        }

        hideOverlay();
        state.game.countdownActive = false;
        state.game.started = true;
        state.game.startTime = Date.now();
    }

    async function startLevel(levelData) {
        state.currentLevel = levelData;
        const data = levelData.data;

        validateLevel(data);

        const tileSize = Math.floor(
            Math.min(dom.canvas.width / data.w, dom.canvas.height / data.h)
        );

        const enemies = normalizeEnemies(data);
        const keyCount = Array.isArray(data.keys) ? data.keys.length : 0;
        const randomKeys = randomOpenPositions(data, keyCount);

        state.game = {
            tileSize,
            width: data.w,
            height: data.h,
            walls: data.walls,
            player: { ...data.player },
            enemies: enemies.map((enemy) => ({ ...enemy })),
            exit: { ...data.exit },
            keys: randomKeys.map((key) => ({ ...key, collected: false })),
            keysCollected: 0,
            moves: 0,
            started: false,
            finished: false,
            countdownActive: false,
            startTime: Date.now(),
            finalTimeMs: null,
            enemyTick: 0,
        };

        if (dom.startBtn) dom.startBtn.disabled = true;

        await runCountdown();
        showMessage('Go.', 'success');
    }

    function finishCurrentFrame() {
        if (!state.game) return;

        if (state.game.finalTimeMs === null) {
            state.game.finalTimeMs = Date.now() - state.game.startTime;
        }

        state.game.finished = true;

        if (dom.startBtn) dom.startBtn.disabled = false;
    }

    function movePlayer(dx, dy) {
        if (!state.game || state.game.finished || state.game.countdownActive) return;

        const nextX = state.game.player.x + dx;
        const nextY = state.game.player.y + dy;

        if (isWall(nextX, nextY)) return;

        state.game.player.x = nextX;
        state.game.player.y = nextY;
        state.game.moves += 1;

        collectKeys();
    }

    function moveEnemies() {
        if (!state.game || state.game.finished || state.game.countdownActive) return;

        for (const enemy of state.game.enemies) {
            const next = bfsNextStep(
                enemy.x,
                enemy.y,
                state.game.player.x,
                state.game.player.y,
                isWall
            );

            if (next) {
                enemy.x = next.x;
                enemy.y = next.y;
            }
        }
    }

    function playerCaught() {
        if (!state.game) return false;

        return state.game.enemies.some(
            (enemy) => enemy.x === state.game.player.x && enemy.y === state.game.player.y
        );
    }

    function playerExited() {
        if (!state.game) return false;

        return (
            state.game.player.x === state.game.exit.x &&
            state.game.player.y === state.game.exit.y &&
            allKeysCollected()
        );
    }

    function gameLoop(draw, onFrame) {
        const loop = async () => {
            const game = state.game;

            if (game && !game.finished) {
                if (!game.countdownActive) {
                    if (state.pressedKeys['ArrowUp'] || state.pressedKeys.w || state.pressedKeys.W) {
                        movePlayer(0, -1);
                        state.pressedKeys = {};
                    } else if (state.pressedKeys['ArrowDown'] || state.pressedKeys.s || state.pressedKeys.S) {
                        movePlayer(0, 1);
                        state.pressedKeys = {};
                    } else if (state.pressedKeys['ArrowLeft'] || state.pressedKeys.a || state.pressedKeys.A) {
                        movePlayer(-1, 0);
                        state.pressedKeys = {};
                    } else if (state.pressedKeys['ArrowRight'] || state.pressedKeys.d || state.pressedKeys.D) {
                        movePlayer(1, 0);
                        state.pressedKeys = {};
                    }
                }

                game.enemyTick += 1;
                const difficulty = state.currentLevel?.difficulty || 1;
                const enemySpeed = Math.max(22 - difficulty * 2, 6);

                if (game.enemyTick % enemySpeed === 0) {
                    moveEnemies();
                }
            }

            if (typeof onFrame === 'function') {
                onFrame();
            }

            draw(state.game, state.currentLevel, allKeysCollected());
            state.animationId = requestAnimationFrame(loop);
        };

        if (state.animationId) {
            cancelAnimationFrame(state.animationId);
        }

        loop();
    }

    return {
        state,
        updateHud,
        showMessage,
        showOverlay,
        hideOverlay,
        saveResult,
        triggerCaughtEffect,
        startLevel,
        finishCurrentFrame,
        playerCaught,
        playerExited,
        gameLoop,
    };
}