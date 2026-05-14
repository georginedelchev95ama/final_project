export function normalizeEnemies(data) {
    if (Array.isArray(data.enemies) && data.enemies.length > 0) {
        return data.enemies;
    }
    if (data.enemy) {
        return [data.enemy];
    }
    return [];
}

export function validateLevel(data) {
    if (!Array.isArray(data.walls) || data.walls.length === 0) {
        throw new Error('Missing wall data.');
    }
    if (data.walls[data.player.y]?.[data.player.x] !== 0) {
        throw new Error('Player starts inside a wall.');
    }
    if (data.walls[data.exit.y]?.[data.exit.x] !== 0) {
        throw new Error('Exit is inside a wall.');
    }

    for (const enemy of normalizeEnemies(data)) {
        if (data.walls[enemy.y]?.[enemy.x] !== 0) {
            throw new Error('An enemy is inside a wall.');
        }
    }
}

export function randomOpenPositions(data, count) {
    const blocked = new Set();
    blocked.add(`${data.player.x},${data.player.y}`);
    blocked.add(`${data.exit.x},${data.exit.y}`);
    normalizeEnemies(data).forEach(enemy => blocked.add(`${enemy.x},${enemy.y}`));

    const openCells = [];
    for (let y = 0; y < data.h; y += 1) {
        for (let x = 0; x < data.w; x += 1) {
            if (data.walls[y][x] === 0 && !blocked.has(`${x},${y}`)) {
                openCells.push({ x, y });
            }
        }
    }

    for (let i = openCells.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [openCells[i], openCells[j]] = [openCells[j], openCells[i]];
    }

    return openCells.slice(0, count);
}
