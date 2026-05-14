function drawEmoji(ctx, text, x, y, size) {
    ctx.font = `${size}px Arial`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, x, y);
}

export function drawGame(ctx, canvas, game, currentLevel, allKeysCollected) {
    if (!game) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const tile = game.tileSize;
    const offsetX = Math.floor((canvas.width - game.width * tile) / 2);
    const offsetY = Math.floor((canvas.height - game.height * tile) / 2);

    for (let y = 0; y < game.height; y += 1) {
        for (let x = 0; x < game.width; x += 1) {
            const blocked = game.walls[y][x] === 1;
            ctx.fillStyle = blocked ? '#22304a' : '#0f172a';
            ctx.fillRect(offsetX + x * tile, offsetY + y * tile, tile, tile);
            ctx.strokeStyle = blocked ? '#334155' : '#1f2937';
            ctx.strokeRect(offsetX + x * tile, offsetY + y * tile, tile, tile);
        }
    }

    ctx.fillStyle = allKeysCollected ? '#15803d' : '#475569';
    ctx.fillRect(offsetX + game.exit.x * tile, offsetY + game.exit.y * tile, tile, tile);
    drawEmoji(ctx, '🚪', offsetX + game.exit.x * tile + tile / 2, offsetY + game.exit.y * tile + tile / 2, tile * 0.7);

    for (const key of game.keys) {
        if (!key.collected) {
            drawEmoji(ctx, '🔑', offsetX + key.x * tile + tile / 2, offsetY + key.y * tile + tile / 2, tile * 0.72);
        }
    }

    drawEmoji(ctx, '🏃', offsetX + game.player.x * tile + tile / 2, offsetY + game.player.y * tile + tile / 2, tile * 0.78);

    const faces = ['👾', '👹', '🤖', '😈', '👻'];
    game.enemies.forEach((enemy, index) => {
        drawEmoji(ctx, faces[index % faces.length], offsetX + enemy.x * tile + tile / 2, offsetY + enemy.y * tile + tile / 2, tile * 0.72);
    });

    if (currentLevel?.name) {
        ctx.fillStyle = 'rgba(255,255,255,0.08)';
        ctx.fillRect(18, 16, 220, 42);
        ctx.fillStyle = '#dbeafe';
        ctx.font = '18px Arial';
        ctx.textAlign = 'left';
        ctx.fillText(currentLevel.name, 30, 42);
    }
}
